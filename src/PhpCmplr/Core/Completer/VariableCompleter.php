<?php

namespace PhpCmplr\Core\Completer;

use PhpParser\Node\Expr;

use PhpCmplr\Core\Component;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\Type\Type;

class VariableCompleter extends Component implements CompleterInterface
{
    /**
     * @var Parser
     */
    private $parser;

    public function complete($offset)
    {
        $this->run();
        $nodes = $this->parser->getNodesAtOffset($offset, true);

        $node = null;
        $completions = [];
        if (count($nodes) === 0 || !($nodes[0] instanceof Expr\Variable)) {
            return $completions;
        }
        $node = $nodes[0];

        $scope = null;
        foreach ($nodes as $ctxNode) {
            if ($ctxNode->hasAttribute('variables')) {
                $scope = $ctxNode->getAttribute('variables');
                break;
            }
        }
        if ($scope === null) {
            $allNodes = $this->parser->getNodes();
            if (count($allNodes) !== 0 && $allNodes[0]->hasAttribute('global_variables')) {
                $scope = $allNodes[0]->getAttribute('global_variables');
            } else {
                $scope = [];
            }
        }

        foreach ($scope as $var => $type) {
            if ($type->equals(Type::mixed_()) && is_string($node->name) && ('$' . $node->name) === $var) {
                continue;
            }

            $completion = new Completion();
            $completion->setInsertion(ltrim($var, '$'));
            $completion->setDisplay($completion->getInsertion());
            $completion->setKind(Completion::KIND_VARIABLE);
            $completion->setExtendedDisplay($type->toString(true));
            $completions[] = $completion;
        }

        return $completions;
    }

    protected function doRun()
    {
        $this->parser = $this->container->get('parser');
        $this->container->get('typeinfer')->run();
    }
}
