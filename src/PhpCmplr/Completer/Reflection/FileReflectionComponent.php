<?php

namespace PhpCmplr\Completer\Reflection;

use PhpLenientParser\Node;
use PhpLenientParser\Node\Name;
use PhpLenientParser\Node\Name\Relative;
use PhpLenientParser\Node\Name\FullyQualified;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\NodeTraverser;
use PhpLenientParser\NodeVisitorAbstract;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\NodeVisitorComponent;
use PhpCmplr\Completer\OffsetLocation;
use PhpCmplr\Completer\Parser\DocTag\Type;

class FileReflectionComponent extends NodeVisitorComponent implements ReflectionComponentInterface
{
    /**
     * @var ClassLike[]
     */
    private $classes = [];

    /**
     * @var Function_[]
     */
    private $functions = [];

    /**
     * @var Consts_[]
     */
    private $consts = [];

    /**
     * @var string
     */
    private $path;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->path = $this->container->get('file')->getPath();
    }

    /**
     * @param Name|string $type
     *
     * @return Type
     */
    private function getType($type)
    {
        return Type::fromString(Type::nameToString($type));
    }

    /**
     * @param Element $element
     * @param Node    $node
     */
    protected function init(Element $element, Node $node)
    {
        $element->setName($node->hasAttribute('namespacedName')
            ? Type::nameToString($node->getAttribute('namespacedName'))
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
            $param = new Param();
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
     * @param ClassLike                      $class  Class member is defined in.
     *
     * @return 
     */
    protected function processMember($member, Node $node, ClassLike $class)
    {
        $member->setAccessibility(
            $node->isPrivate() ? ClassLike::M_PRIVATE : (
            $node->isProtected() ? ClassLike::M_PROTECTED :
            ClassLike::M_PUBLIC));

        $member->setStatic($node->isStatic());
        $member->setClass($class);
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
                    $const = new ClassConst();
                    $this->init($const, $constNode);
                    $const->setClass($class);
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
                    $this->processMember($property, $child, $class);
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
                $this->processMember($method, $child, $class);
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
        foreach ($node->stmts as $child) {
            if ($child instanceof Stmt\TraitUse) {
                foreach ($child->traits as $trait) {
                    $class->addTrait(Type::nameToString($trait));
                }

                foreach ($child->adaptations as $adaptation) {
                    if ($adaptation instanceof Stmt\TraitUseAdaptation\Precedence) {
                        $insteadOf = new TraitInsteadOf();
                        $insteadOf->setTrait(Type::nameToString($adaptation->trait));
                        $insteadOf->setMethod($adaptation->method);
                        foreach ($adaptation->insteadof as $insteadOfNode) {
                            $insteadOf->addInsteadOf(Type::nameToString($insteadOfNode));
                        }
                        $class->addTraitInsteadOf($insteadOf);
                    } elseif ($adaptation instanceof Stmt\TraitUseAdaptation\Alias) {
                        $alias = new TraitAlias();
                        $alias->setTrait(Type::nameToString($adaptation->trait));
                        $alias->setMethod($adaptation->method);
                        $alias->setNewName($adaptation->newName);
                        $alias->setNewAccessibility(
                            $adaptation->newModifier === Stmt\Class_::MODIFIER_PRIVATE ? ClassLike::M_PRIVATE : (
                            $adaptation->newModifier === Stmt\Class_::MODIFIER_PROTECTED ? ClassLike::M_PROTECTED : (
                            $adaptation->newModifier === Stmt\Class_::MODIFIER_PUBLIC ? ClassLike::M_PUBLIC :
                            null)));
                        $class->addTraitAlias($alias);
                    }
                }
            }
        }
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
        $class->setExtends(Type::nameToString($node->extends));
        foreach ($node->implements as $implements) {
            $class->addImplements(Type::nameToString($implements));
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
            $interface->addExtends(Type::nameToString($extends));
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
            $this->functions[strtolower($function->getName())] = $function;
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;

        } elseif ($node instanceof Stmt\Class_) {
            $class = new Class_();
            $this->processClass($class, $node);
            $this->classes[strtolower($class->getName())] = $class;
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;

        } elseif ($node instanceof Stmt\Interface_) {
            $interface = new Interface_();
            $this->processInterface($interface, $node);
            $this->classes[strtolower($interface->getName())] = $interface;
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;

        } elseif ($node instanceof Stmt\Trait_) {
            $trait = new Trait_();
            $this->processTrait($trait, $node);
            $this->classes[strtolower($trait->getName())] = $trait;
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

    public function findClass($fullyQualifiedName)
    {
        $this->run();
        $fullyQualifiedName = strtolower($fullyQualifiedName);
        return empty($this->classes[$fullyQualifiedName]) ? [] : [$this->classes[$fullyQualifiedName]];
    }

    public function findFunction($fullyQualifiedName)
    {
        $this->run();
        $fullyQualifiedName = strtolower($fullyQualifiedName);
        return empty($this->functions[$fullyQualifiedName]) ? [] : [$this->functions[$fullyQualifiedName]];
    }

    public function findConst($fullyQualifiedName)
    {
        $this->run();
        return empty($this->consts[$fullyQualifiedName]) ? [] : [$this->consts[$fullyQualifiedName]];
    }

    /**
     * Get all classes, interfaces and traits from this file.
     *
     * @return ClassLike[]
     */
    public function getClasses()
    {
        $this->run();
        return array_values($this->classes);
    }

    /**
     * Get all functions from this file.
     *
     * @return Function_[]
     */
    public function getFunctions()
    {
        $this->run();
        return array_values($this->functions);
    }

    /**
     * Get all non-class consts from this file.
     *
     * @return Const_[]
     */
    public function getConsts()
    {
        $this->run();
        return array_values($this->consts);
    }

    protected function doRun()
    {
        $this->container->get('name_resolver')->run();
        parent::doRun();
    }
}
