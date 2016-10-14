<?php

namespace PhpCmplr\Completer\SourceFile;

use PhpLenientParser\Node;

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

    /**
     * @param Node   $node
     * @param string $filePath
     *
     * @return self
     */
    public static function fromNode(Node $node, $filePath)
    {
        return new self(
            new OffsetLocation($filePath, $node->getAttribute('startFilePos')),
            new OffsetLocation($filePath, $node->getAttribute('endFilePos'))
        );
    }
}
