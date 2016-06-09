<?php

namespace PhpCmplr\Completer\TypeInferrer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Comment;

use PhpCmplr\Completer\NodeVisitorComponent;
use PhpCmplr\Completer\Parser\DocTag\Type;

class TypeInferrerComponent extends NodeVisitorComponent implements TypeInferrerComponentInterface
{
    public function leaveNode(Node $node)
    {
        if (!($node instanceof Expr)) {
            return;
        }

        $type = null;

        if ($node instanceof Expr\Assign || $node instanceof Expr\AssignRef) {
            $type = $node->expr->getAttribute('type');

        } elseif ($node instanceof Expr\Ternary) {
            $exprType1 = $node->if === null ? $node->cond->getAttribute('type') : $node->if->getAttribute('type');
            $exprType2 = $node->else->getAttribute('type');
            $type = Type::alternatives([$exprType1, $exprType2]);

        } elseif ($node instanceof Expr\BinaryOp\Coalesce) {
            $exprType1 = $node->left->getAttribute('type');
            $exprType2 = $node->right->getAttribute('type');
            $type = Type::alternatives([$exprType1, $exprType2]);

        } elseif ($node instanceof Expr\ErrorSuppress) {
            $type = $node->expr->getAttribute('type');

        } elseif ($node instanceof Expr\Eval_) {
            $type = Type::mixed_();

        } elseif ($node instanceof Expr\Exit_) {
            $type = Type::null_();
        }

        if ($type !== null || !$node->hasAttribute('type')) {
            if ($type === null) {
                $type = Type::mixed_();
            }
            $node->setAttribute('type', $type);
        }
    }

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
        if ($node instanceof Node\Expr) {
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
