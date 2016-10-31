<?php

namespace PhpCmplr\Completer\GoTo_;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Comment;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\SourceFile\Location;

class GoTo_ extends Component
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var GoToInterface[]
     */
    private $goToComponents;

    /**
     * Get "go to" locations for the given position in current file.
     *
     * @param int    $offset
     *
     * @return Location[] Preferred locations first.
     */
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
