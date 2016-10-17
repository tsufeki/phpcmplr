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
     * @param string $path
     * @param string $contents
     *
     * @throws IOException
     */
    public function write($path, $contents);

    /**
     * Check if file  exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists($path);

    /**
     * Return last modification date as Unix timestamp.
     *
     * @param string $path
     *
     * @return int
     *
     * @throws IOException
     */
    public function getMTime($path);

    /**
     * @param string $path Absolute path.
     *
     * @return string
     */
    public function canonicalPath($path);

    /**
     * @param string $subdir
     *
     * @return string
     */
    public function getCacheDir($subdir);

    /**
     * @param string              $path
     * @param FileFilterInterface $filter
     *
     * @return bool
     */
    public function match($path, FileFilterInterface $filter);

    /**
     * @param string              $path
     * @param FileFilterInterface $filter
     *
     * @return array path => int mtime
     */
    public function listFileMTimesRecursive($path, FileFilterInterface $filter = null);
}
