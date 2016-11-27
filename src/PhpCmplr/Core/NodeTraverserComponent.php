<?php

namespace PhpCmplr\Core;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;

/**
 * Component which runs some visitors on the node tree.
 */
class NodeTraverserComponent extends Component
{
    /**
     * @var NodeTraverser
     */
    private $traverser;

    /**
     * @var NodeVisitor[]
     */
    private $visitors;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->traverser = new NodeTraverser();
        $this->visitors = [];
    }

    /**
     * @param NodeVisitor $visitor
     *
     * @return $this;
     */
    public function addVisitor(NodeVisitor $visitor)
    {
        $this->visitors[] = $visitor;
        $this->traverser->addVisitor($visitor);

        return $this;
    }

    /**
     * @return NodeVisitor[]
     */
    public function getVisitors()
    {
        return $this->visitors;
    }

    protected function doRun()
    {
        $this->traverser->traverse($this->container->get('parser')->getNodes());
    }
}
