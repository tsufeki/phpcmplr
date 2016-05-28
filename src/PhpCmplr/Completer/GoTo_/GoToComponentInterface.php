<?php

namespace PhpCmplr\Completer\GoTo_;

use PhpCmplr\Completer\Location;

interface GoToComponentInterface
{
    /**
     * Get "go to" locations for the given position in current file.
     *
     * @param int $offset
     *
     * @return Location[] Preferred locations first.
     */
    public function getGoToLocations($offset);
}
