<?php

namespace PhpCmplr\Util;

use SplFileInfo;

interface FileFilterInterface
{
    /**
     * @param SplFileInfo $fileInfo
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function filter(SplFileInfo $fileInfo);
}
