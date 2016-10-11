<?php

namespace PhpCmplr\Util;

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

    public function listFileMTimesRecursive($path, array $extensions = null, $maxSize = null)
    {
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        $regex = $extensions === null ? null : '/\.(' . implode('|', $extensions) .  ')$/i';

        $mtimes = [];
        foreach ($iter as $path => $file) {
            if (!$file->isDir() &&
                ($extensions === null || preg_match($regex, $file->getFilename())) &&
                ($maxSize === null || $file->getSize() <= $maxSize)
            ) {
                $mtimes[$path] = $file->getMTime();
            }
        }

        return $mtimes;
    }
}
