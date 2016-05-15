<?php

namespace PhpCmplr\Completer;

class OffsetLocation implements Location
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $offset;

    /**
     * @param string $path
     * @param int    $offset
     */
    public function __construct($path, $offset)
    {
        $this->path = $path;
        $this->offset = $offset;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getOffset(SourceFile $file = null)
    {
        return $this->offset;
    }
}
