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

        return $function;
    }

    public function enterNode(Node $node) {
        if ($node instanceof Stmt\Function_) {
            $function = new Function_();
            $this->processFunction($function, $node);
            $this->functions[] = $function;
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
        // TODO: class-likes
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
}
