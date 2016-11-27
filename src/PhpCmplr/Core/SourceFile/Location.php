<?php

namespace PhpCmplr\Core\SourceFile;

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
    public function getOffset(SourceFileInterface $file);

    /**
     * @param SourceFile $file Must be the file at getPath().
     *
     * @return int[] [line, column]
     */
    public function getLineAndColumn(SourceFileInterface $file);
}
