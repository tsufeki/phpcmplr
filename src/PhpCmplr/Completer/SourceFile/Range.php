<?php

namespace PhpCmplr\Completer\SourceFile;

class Range
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
     * @param Location $start
     * @param Location $end
     */
    public function __construct(Location $start, Location $end)
    {
        $this->start = $start;
        $this->end = $end;
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
}
