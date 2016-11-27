<?php

namespace PhpCmplr\Core\SourceFile;

class LineAndColumnLocation implements Location
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $line;

    /**
     * @var int
     */
    private $col;

    public function __construct($path, $line, $col)
    {
        $this->path = $path;
        $this->line = $line;
        $this->col = $col;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getOffset(SourceFileInterface $file)
    {
        return $file->getOffset($this->line, $this->col);
    }

    public function getLineAndColumn(SourceFileInterface $file = null)
    {
        return [$this->line, $this->col];
    }

    public static function moveToStartOfLine(SourceFileInterface $file, Location $location)
    {
        return new LineAndColumnLocation($location->getPath(), $location->getLineAndColumn($file)[0], 1);
    }
}
