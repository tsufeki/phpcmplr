<?php

namespace PhpCmplr\Completer;

use PhpLenientParser\Node;
use PhpLenientParser\NodeVisitor;

/**
 * NodeTraverserComponent and NodeVisitor in one.
 */
class NodeVisitorComponent extends NodeTraverserComponent implements NodeVisitor
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->addVisitor($this);
    }

    public function beforeTraverse(array $nodes) {}
    public function enterNode(Node $node) {}
    public function leaveNode(Node $node) {}
    public function afterTraverse(array $nodes) {}
}
