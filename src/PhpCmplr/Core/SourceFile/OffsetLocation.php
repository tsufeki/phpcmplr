<?php

namespace PhpCmplr\Core\SourceFile;

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

    public static function move(SourceFileInterface $file, Location $location, $distance = 1)
    {
        return new OffsetLocation($location->getPath(), $location->getOffset($file) + $distance);
    }
}
