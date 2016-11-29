<?php

namespace PhpCmplr\Util;

use SplFileInfo;

class FileIO implements FileIOInterface
{
    public function read($path) {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new IOException(error_get_last()['message']);
        }

        return $contents;
    }

    public function write($path, $contents)
    {
        $ret = @file_put_contents($path, $contents);
        if ($ret === false) {
            throw new IOException(error_get_last()['message']);
        }
    }

    public function exists($path)
    {
        return @file_exists($path) === true;
    }

    public function getMTime($path)
    {
        $mtime = @filemtime($path);
        if ($mtime === false) {
            throw new IOException(error_get_last()['message']);
        }

        return $mtime;
    }

    public function canonicalPath($path)
    {
        $result = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '..') {
                array_pop($result);
                continue;
            }

            if ($seg === '.') {
                continue;
            }

            if ($seg === '') {
                continue;
            }

            $result[] = $seg;
        }

        return '/' . implode('/', $result);
    }

    public function getCacheDir($subdir)
    {
        $cacheDir = getenv('XDG_CACHE_HOME');
        if (!$cacheDir) {
            $cacheDir = getenv('HOME') . '/.cache';
        }

        $cacheDir .= '/phpcmplr/' . $subdir;
        $cacheDir = $this->canonicalPath($cacheDir);
        @mkdir($cacheDir, 0777, true);

        return $cacheDir;
    }

    public function match($path, FileFilterInterface $filter)
    {
        try {
            return $filter->filter(new SplFileInfo($path));
        } catch (\RuntimeException $e) { }

        return false;
    }

    public function listFileMTimesRecursive($path, FileFilterInterface $filter = null)
    {
        try {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );
        } catch (\RuntimeException $e) {
            return [];
        }

        $mtimes = [];
        /** @var \SplFileInfo $file */
        foreach ($iter as $path => $file) {
            try {
                if ($filter === null || $filter->filter($file)) {
                    $mtimes[$path] = $file->getMTime();
                }
            } catch (\RuntimeException $e) { }
        }

        return $mtimes;
    }
}
