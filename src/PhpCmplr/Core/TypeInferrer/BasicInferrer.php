<?php

namespace PhpCmplr\Core\TypeInferrer;

use PhpParser\Node;
use PhpParser\Node\Expr;

use PhpCmplr\Core\NodeVisitorComponent;
use PhpCmplr\Core\Type\Type;

class BasicInferrer extends NodeVisitorComponent
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
}
