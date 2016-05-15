<?php

namespace PhpCmplr\Completer\Reflection;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\Relative;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

use PhpCmplr\Completer\OffsetLocation;
use PhpCmplr\Completer\Parser\DocTag\Type;

class FileReflectionNodeVisitor extends NodeVisitorAbstract
{
    /**
     * @var ClassLike[]
     */
    protected $classes = [];

    /**
     * @var Function_[]
     */
    protected $functions = [];

    /**
     * @var Consts_[]
     */
    protected $consts = [];

    /**
     * @var string
     */
    protected $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @param Name|string $name
     *
     * @return string
     */
    private function nameToString($name)
    {
        if (empty($name)) {
            return null;
        }

        if (is_string($name)) {
            return $name;
        }

        if ($name instanceof FullyQualified) {
            return '\\' . $name->toString();
        }

        if ($name instanceof Name && $name->hasAttribute('resolved')) {
            return $this->nameToString($name->getAttribute('resolved'));
        }

        if ($name instanceof Relative) {
            return 'namespace\\' . $name->toString();
        }

        return $name->toString();
    }

    /**
     * @param Name|string $type
     *
     * @return Type
     */
    protected function getType($type)
    {
        return Type::fromString($this->nameToString($type));
    }

    /**
     * @param Element $element
     * @param Node    $node
     */
    protected function init(Element $element, Node $node)
    {
        $element->setName($node->hasAttribute('namespacedName')
            ? $this->nameToString($node->getAttribute('namespacedName'))
            : $node->name);
        if ($node->hasAttribute('startFilePos')) {
            $element->setLocation(new OffsetLocation($this->path, $node->getAttribute('startFilePos')));
        }
    }

    /**
     * @param Function_                       $function
     * @param Stmt\Function_|Stmt\ClassMethod $node
     */
    protected function processFunction(Function_ $function, $node)
    {
        $this->init($function, $node);
        $function->setReturnByRef($node->byRef);
        $function->setReturnType($this->getType($node->returnType));

        $annotations = [];
        if ($node->hasAttribute('annotations')) {
            $annotations = $node->getAttribute('annotations');
        }

        $docReturnType = Type::mixed_();
        if (!empty($annotations['return'])) {
            $docReturnType = $annotations['return'][count($annotations['return']) - 1]->getType();
        }
        $function->setDocReturnType($docReturnType);

        $paramDocTypes = [];
        if (!empty($annotations['param'])) {
            foreach ($annotations['param'] as $paramTag) {
                $paramDocTypes[$paramTag->getIdentifier()] = $paramTag->getType();
            }
        }

        foreach ($node->params as $paramNode) {
            $param = new namespace\Param();
            $param->setName('$' . $paramNode->name);
            $param->setByRef($paramNode->byRef);
            $param->setOptional($paramNode->default !== null);
            $param->setVariadic($paramNode->variadic);
            $param->setTypeHint($this->getType($paramNode->type));
            $param->setDocType(empty($paramDocTypes[$param->getName()])
                ? Type::mixed_()
                : $paramDocTypes[$param->getName()]);
            // TODO: variadic parameter type hints?

            $function->addParam($param);
        }
    }

    /**
     * @param Method|Property                $member
     * @param Stmt\ClassMethod|Stmt\Property $node
     *
     * @return 
     */
    protected function processMember($member, Node $node)
    {
        $member->setAccessibility(
            $node->isPrivate() ? ClassLike::M_PRIVATE : (
            $node->isProtected() ? ClassLike::M_PROTECTED :
            ClassLike::M_PUBLIC));

        $member->setStatic($node->isStatic());
    }

    /**
     * @param ClassLike      $class
     * @param Stmt\ClassLike $node
     */
    protected function processClassLike(ClassLike $class, Stmt\ClassLike $node)
    {
        $this->init($class, $node);

        foreach ($node->stmts as $child) {
            if ($child instanceof Stmt\ClassConst) {
                foreach ($child->consts as $constNode) {
                    $const = new Const_();
                    $this->init($const, $constNode);
                    $class->addConst($const);
                }
            } elseif ($child instanceof Stmt\Property) {
                $annotations = [];
                if ($child->hasAttribute('annotations')) {
                    $annotations = $child->getAttribute('annotations');
                }

                $docTypes = [];
                if (!empty($annotations['var'])) {
                    foreach ($annotations['var'] as $varTag) {
                        $docTypes[$varTag->getIdentifier()] = $varTag->getType();
                    }
                }

                foreach ($child->props as $propertyNode) {
                    $property = new Property();
                    $this->init($property, $propertyNode);
                    $property->setName('$' . $property->getName());
                    $this->processMember($property, $child);
                    $type = Type::mixed_();
                    if (!empty($docTypes[$property->getName()])) {
                        $type = $docTypes[$property->getName()];
                    } elseif (count($child->props) === 1 && !empty($docTypes[null])) {
                        $type = $docTypes[null];
                    }
                    $property->setType($type);
                    $class->addProperty($property);
                }
            } elseif ($child instanceof Stmt\ClassMethod) {
                $method = new Method();
                $this->processFunction($method, $child);
                $this->processMember($method, $child);
                $method->setAbstract($child->isAbstract());
                $method->setFinal($child->isFinal());
                $class->addMethod($method);
            }
        }
    }

    /**
     * @param Class_|Trait_           $class
     * @param Stmt\Class_|Stmt\Trait_ $node
     */
    protected function processUsedTraits(ClassLike $class, Stmt\ClassLike $node)
    {
        // TODO
    }

    /**
     * @param Class_      $class
     * @param Stmt\Class_ $node
     */
    protected function processClass(Class_ $class, Stmt\Class_ $node)
    {
        $this->processClassLike($class, $node);
        $class->setAbstract($node->isAbstract());
        $class->setFinal($node->isFinal());
        $class->setExtends($this->nameToString($node->extends));
        foreach ($node->implements as $implements) {
            $class->addImplements($this->nameToString($implements));
        }
        $this->processUsedTraits($class, $node);
    }

    /**
     * @param Interface_      $interface
     * @param Stmt\Interface_ $node
     */
    protected function processInterface(Interface_ $interface, Stmt\Interface_ $node)
    {
        $this->processClassLike($interface, $node);
        foreach ($node->extends as $extends) {
            $interface->addExtends($this->nameToString($extends));
        }
    }

    /**
     * @param Trait_      $trait
     * @param Stmt\Trait_ $node
     */
    protected function processTrait(Trait_ $trait, Stmt\Trait_ $node)
    {
        $this->processClassLike($trait, $node);
        $this->processUsedTraits($trait, $node);
    }

    public function enterNode(Node $node) {
        if ($node instanceof Stmt\Function_) {
            $function = new Function_();
            $this->processFunction($function, $node);
            $this->functions[$function->getName()] = $function;
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof Stmt\Class_) {
            $class = new Class_();
            $this->processClass($class, $node);
            $this->classes[$class->getName()] = $class;
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof Stmt\Interface_) {
            $interface = new Interface_();
            $this->processInterface($interface, $node);
            $this->classes[$interface->getName()] = $interface;
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof Stmt\Trait_) {
            $trait = new Trait_();
            $this->processTrait($trait, $node);
            $this->classes[$trait->getName()] = $trait;
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof Stmt\Const_) {
            foreach ($node->consts as $constNode) {
                $const = new Const_();
                $this->init($const, $constNode);
                $this->consts[$const->getName()] = $const;
            }
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
        // TODO: variables
    }

    /**
     * @return ClassLike[]
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @return Function_[]
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * @return Consts_[]
     */
    public function getConsts()
    {
        return $this->consts;
    }
}
