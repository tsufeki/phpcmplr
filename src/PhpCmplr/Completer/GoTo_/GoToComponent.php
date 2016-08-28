<?php

namespace PhpCmplr\Completer\GoTo_;

use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Name;
use PhpLenientParser\Node\Identifier;
use PhpLenientParser\Comment;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Parser\Parser;

class GoToComponent extends Component
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var GoToComponentInterface[]
     */
    private $goToComponents;

    public function getGoToLocations($offset)
    {
        $this->run();
        $nodes = $this->parser->getNodesAtOffset($offset);

        $locations = [];
        foreach ($this->goToComponents as $goto) {
            $locations = array_merge($locations, $goto->getGoToLocations($offset, $nodes));
        }

        return $locations;
    }

    protected function doRun()
    {
        $this->parser = $this->container->get('parser');
        $this->goToComponents = $this->container->getByTag('goto');
    }
}
