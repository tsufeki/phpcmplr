<?php

namespace PhpCmplr\Util;

interface FileIOInterface
{
    /**
     * @param string $path
     *
     * @return string Whole file contents.
     *
     * @throws IOException
     */
    public function read($path);
}
