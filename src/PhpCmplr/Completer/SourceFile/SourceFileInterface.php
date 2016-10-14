<?php

namespace PhpCmplr\Completer\SourceFile;

/**
 * Source file with contents.
 */
interface SourceFileInterface
{
    /**
     * @return string
     */
    public function getPath();

    /**
     * @return string
     */
    public function getContents();

    /**
     * @return bool
     */
    public function isEmpty();

    /**
     * Get offset (0-based) inside file of the character at given line and column (1-based).
     *
     * @param int $line
     * @param int $column
     *
     * @return int
     */
    public function getOffset($line, $column);

    /**
     * Get line and and column (1-based) for the given offset (0-based).
     *
     * @param int $offset
     *
     * @return int[] [line, column]
     */
    public function getLineAndColumn($offset);

    /**
     * @return \Iterator of strings (lines with EOLs).
     */
    public function getLines();

    /**
     * Get line number $lineno (1-based).
     *
     * @param int $lineno
     *
     * @return string
     */
    public function getLine($lineno);
}
