<?php

namespace PhpCmplr\Completer\TypeInferrer;

use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Name;
use PhpLenientParser\Node\Identifier;
use PhpLenientParser\Comment;

use PhpCmplr\Completer\NodeTraverserComponent;
use PhpCmplr\Completer\Type\Type;

class TypeInferrer extends NodeTraverserComponent implements TypeInferrerInterface
{
    public function getType($offset)
    {
        $this->run();
        $nodes = $this->container->get('parser')->getNodesAtOffset($offset);

        $node = null;
        if (count($nodes) > 0) {
            $node = $nodes[0];
            if ($node instanceof Name || $node instanceof Identifier) {
                $node = count($nodes) > 1 ? $nodes[1] : null;
            }
        }
        if ($node instanceof Comment) {
            $node = null;
        }

        $type = null;
        if ($node instanceof Expr) {
            $type = $node->getAttribute('type');
        }

        return $type;
    }

    protected function doRun()
    {
        $visitors = $this->container->getByTag('typeinfer.visitor');
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
        $this->container->get('name_resolver')->run();
        parent::doRun();
    }
}
