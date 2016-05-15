<?php

namespace PhpCmplr\Completer;

interface Location
{
    /**
     * @return string
     */
    public function getPath();

    /**
     * @param SourceFile $file
     *
     * @return int
     */
    public function getOffset(SourceFile $file);
}
