<?php

namespace PhpCmplr\Completer;

class SourceFile extends Component implements SourceFileInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $contents;

    /**
     * @param Container $container
     * @param string    $path      Full, absolute path.
     * @param string    $contents  UTF-8 encoded contents.
     */
    public function __construct(Container $container, $path, $contents)
    {
        parent::__construct($container);
        $this->path = $path;
        $this->contents = $contents;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function getOffset($line, $column)
    {
        $maxOffset = max(0, strlen($this->contents) - 1);
        $offset = 0;
        for ($i = 0; $i < $line - 1; $i++) {
            $offset = strpos($this->contents, "\n", $offset);
            if ($offset === false) {
                return $maxOffset;
            }
            $offset++; // newline character
        }
        $offset += $column - 1;
        if ($offset > $maxOffset) {
            return $maxOffset;
        }
        return $offset;
    }

    public function getLineAndColumn($offset)
    {
        $offset = max(0, min($offset, strlen($this->contents) - 1));
        $line = 0;
        $currentOffset = 0;
        $lastOffset = 0;
        while ($currentOffset <= $offset) {
            $lastOffset = $currentOffset;
            $currentOffset = strpos($this->contents, "\n", $currentOffset);
            $line++;
            if ($currentOffset === false) {
                break;
            }
            $currentOffset++; // newline character
        }
        return [$line, 1 + max(0, $offset - $lastOffset)];
    }

    protected function doRun()
    {
    }
}
