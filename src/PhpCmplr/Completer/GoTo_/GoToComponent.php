<?php

namespace PhpCmplr\Completer\GoTo_;

use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Name;
use PhpLenientParser\Node\Identifier;
use PhpLenientParser\Comment;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Parser\ParserComponent;

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
            if ($node instanceof Name || $node instanceof Identifier || $node instanceof Comment) {
                $node = count($nodes) > 1 ? $nodes[1] : null;
            }
        }

        $locations = [];

        if ($node !== null && $node->hasAttribute('reflections')) {
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
