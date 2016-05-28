<?php

namespace PhpCmplr\Completer\GoTo_;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\Parser\DocTag\AlternativesType;
use PhpCmplr\Completer\Parser\DocTag\ObjectType;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Reflection\ReflectionComponent;
use PhpCmplr\Completer\Reflection\Method;
use PhpCmplr\Completer\Reflection\Property;
use PhpCmplr\Completer\Reflection\Const_;

class GoToComponent extends Component implements GoToComponentInterface
{
    /**
     * @var ParserComponent
     */
    private $parser;

    public function getGoToLocations($offset)
    {
        $this->run();
        $nodes = $this->parser->getNodesAtOffset($offset);

        $node = null;
        if (count($nodes) > 0) {
            $node = $nodes[0];
            if ($node instanceof Name) {
                $node = count($nodes) > 1 ? $nodes[1] : null;
            }
        }

        $locations = [];

        if ($node->hasAttribute('reflections')) {
            foreach ($node->getAttribute('reflections') as $refl) {
                $loc = $refl->getLocation();
                if ($loc !== null) {
                    $locations[] = $loc;
                }
            }
        }

        return $locations;
    }

    protected function doRun()
    {
        $this->parser = $this->container->get('parser');
        $this->container->get('typeinfer')->run();
    }
}
