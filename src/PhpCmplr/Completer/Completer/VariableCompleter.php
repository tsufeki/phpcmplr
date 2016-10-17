<?php

namespace PhpCmplr\Completer\Completer;

use PhpLenientParser\Node\Expr;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\Type\Type;

class VariableCompleter extends Component implements CompleterInterface
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param Property[] $properties
     * @param bool       $staticContext
     *
     * @return Completion[]
     */
    public function formatProperties(array $properties, $staticContext = false)
    {
        $completions = [];
        foreach ($properties as $property) {
            $completion = new Completion();
            $completion->setInsertion($staticContext ? $property->getName() : ltrim($property->getName(), '$'));
            $completion->setDisplay($completion->getInsertion());
            $completion->setKind($property->isStatic() ? Completion::KIND_STATIC_PROPERTY : Completion::KIND_PROPERTY);
            $completion->setExtendedDisplay($property->getType()->toString(true));
            $completions[] = $completion;
        }
        return $completions;
    }

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
