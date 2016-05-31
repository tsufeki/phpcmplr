<?php

namespace PhpCmplr\Completer;

interface Location
{
    /**
     * @return string
     */
    public function getPath();

    /**
     * @param SourceFile $file Must be the file at getPath().
     *
     * @return int
     */
    public function getOffset(SourceFile $file);

    /**
     * @param SourceFile $file Must be the file at getPath().
     *
     * @return int[] [line, column]
     */
    public function getLineAndColumn(SourceFile $file);
}
