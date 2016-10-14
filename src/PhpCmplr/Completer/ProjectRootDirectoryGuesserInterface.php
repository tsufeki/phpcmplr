<?php

namespace PhpCmplr\Completer;

interface ProjectRootDirectoryGuesserInterface
{
    /**
     * @param string $path
     *
     * @return string|null
     */
    public function getProjectRootDir($path);
}
