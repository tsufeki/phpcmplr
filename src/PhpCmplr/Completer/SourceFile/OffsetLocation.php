<?php

namespace PhpCmplr\Completer\SourceFile;

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

    public function getOffset(SourceFileInterface $file = null)
    {
        return $this->offset;
    }

    public function getLineAndColumn(SourceFileInterface $file)
    {
        return $file->getLineAndColumn($this->getOffset());
    }
}
