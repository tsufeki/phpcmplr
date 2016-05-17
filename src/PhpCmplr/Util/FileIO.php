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
}
