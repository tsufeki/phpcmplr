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

    /**
     * Check if file  exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists($path);

    /**
     * @param string $path Absolute path.
     *
     * @return string
     */
    public function canonicalPath($path);
}
