<?php

namespace PhpCmplr\Completer\TypeInferrer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Comment;

use PhpCmplr\Completer\NodeTraverserComponent;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Parser\ParserInterface;
use PhpCmplr\Completer\Runnable;

class TypeInferrer extends NodeTraverserComponent implements TypeInferrerInterface
{
    public function getType($offset)
    {
        $this->run();
        /** @var ParserInterface */
        $parser = $this->container->get('parser');
        $nodes = $parser->getNodesAtOffset($offset);

        /** @var Node|null */
        $node = null;
        if (count($nodes) > 0) {
            $node = $nodes[0];
            if ($node instanceof Name) {
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
        /** @var Runnable */
        $nameResolver = $this->container->get('name_resolver');
        $nameResolver->run();
        parent::doRun();
    }
}
