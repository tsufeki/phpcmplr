<?php

namespace PhpCmplr\Completer\GoTo_;

use PhpLenientParser\Node;

use PhpCmplr\Completer\Location;

interface GoToComponentInterface
{
    /**
     * Get "go to" locations for the given position in current file.
     *
     * @param int    $offset
     * @param Node[] $nodes  Node at offset, top-most last.
     *
     * @return Location[] Preferred locations first.
     */
    public function getGoToLocations($offset, $nodes);
}
