<?php

namespace PhpCmplr\Completer\Diagnostics;

use PhpCmplr\Completer\SourceFile\Location;

class Diagnostic
{
    /**
     * @var Location
     */
    private $start;

    /**
     * @var Location
     */
    private $end;

    /**
     * @var string
     */
    private $description;

    /**
     * @param Location $start
     * @param Location $end
     * @param string   $description
     */
    public function __construct(Location $start, Location $end, $description)
    {
        $this->start = $start;
        $this->end = $end;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->getStart()->getPath();
    }

    /**
     * @return Location
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return Location
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
