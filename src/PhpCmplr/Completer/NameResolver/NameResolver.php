<?php

namespace PhpCmplr\Completer\NameResolver;

use PhpParser\ErrorHandler;
use PhpParser\NodeVisitor\NameResolver as PhpParserNameResolver;
use PhpParser\NodeTraverser;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;

use PhpCmplr\Completer\ComponentTrait;
use PhpCmplr\Completer\Runnable;
use PhpCmplr\Completer\Parser\Parser;

class NameResolver extends PhpParserNameResolver implements Runnable, NameResolverInterface
{
    use ComponentTrait;

    /**
     * @var NameResolverComponentInterface[]
     */
    private $components;

    /**
     * @var Name
     */
    private $currentClass;

    /**
     * @var Name
     */
    private $parentClass;

    public function beforeTraverse(array $nodes) {
        parent::beforeTraverse($nodes);
        $this->currentClass = null;
        $this->parentClass = null;
        $this->errorHandler = new ErrorHandler\Collecting();
    }

    public function resolveClassName(Name $name)
    {
        $resolved = parent::resolveClassName($name);

        if ($name->isUnqualified()) {
            if ($this->currentClass !== null && in_array(strtolower($name->toString()), array('self', 'static'))) {
                $resolved = $this->currentClass;
            } elseif ($this->parentClass !== null && strtolower($name->toString()) === 'parent') {
                $resolved = $this->parentClass;
            }
        }

        $name->setAttribute('resolved', $resolved);

        return $name;
    }

    public function resolveOtherName(Name $name, $type)
    {
        /** @var Name */
        $resolved = parent::resolveOtherName($name, $type);
        if (!$resolved->isFullyQualified()) {
            $resolved = new FullyQualified($resolved);
        }
        $name->setAttribute('resolved', $resolved);

        return $name;
    }

    protected function addNamespacedName(Node $node)
    {
        if (null !== $this->namespace) {
            $namespacedName = Name::concat($this->namespace, $node->name);
        } else {
            $namespacedName = new Name($node->name);
        }
        $node->setAttribute('namespacedName', new FullyQualified($namespacedName->parts));
    }

    public function enterNode(Node $node)
    {
        $result = parent::enterNode($node);

        if ($node instanceof Stmt\Class_) {
            $this->currentClass = $node->getAttribute('namespacedName');
            $this->parentClass = null;
            if ($node->extends !== null) {
                $this->parentClass = $node->extends->getAttribute('resolved');
            }
        } elseif ($node instanceof Stmt\Interface_) {
            $this->currentClass = $node->getAttribute('namespacedName');
            $this->parentClass = null;
        }

        foreach ($this->components as $component) {
            $component->enterNode($node, $this);
        }

        return $result;
    }

    public function leaveNode(Node $node)
    {
        $result = parent::leaveNode($node);

        if ($node instanceof Stmt\Class_) {
            $this->currentClass = null;
            $this->parentClass = null;
        }

        return $result;
    }

    protected function doRun()
    {
        $this->components = $this->container->getByTag('name_resolver');
        foreach ($this->components as $component) {
            $component->run();
        }
        /** @var Parser */
        $parser = $this->container->get('parser');
        $traverser = new NodeTraverser();
        $traverser->addVisitor($this);
        $traverser->traverse($parser->getNodes());
    }
}
