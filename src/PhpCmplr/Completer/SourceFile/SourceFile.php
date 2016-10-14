<?php

namespace PhpCmplr\Completer\SourceFile;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Container;

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

    public function isEmpty()
    {
        return strcmp('', $this->contents) === 0;
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

    public function getLines()
    {
        $offset = $lastOffset = 0;
        while (true) {
            $offset = strpos($this->contents, "\n", $lastOffset);
            if ($offset !== false) {
                yield substr($this->contents, $lastOffset, $offset - $lastOffset + 1);
                $lastOffset = $offset + 1;
            } else {
                $line = substr($this->contents, $lastOffset);
                if (!empty($line)) {
                    yield $line;
                }
                break;
            }
        }
    }

    public function getLine($lineno)
    {
        $lineno--;
        foreach ($this->getLines() as $i => $line) {
            if ($i === $lineno) {
                return $line;
            }
        }

        return null;
    }
}
