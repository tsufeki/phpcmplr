<?php

namespace PhpCmplr\Completer;

interface FileStoreInterface
{
    /**
     * @param string $path
     *
     * @return Container
     */
    public function getFile($path);

    /**
     * @param string $path
     * @param string $contents
     *
     * @return Container
     */
    public function addFile($path, $contents);
}
