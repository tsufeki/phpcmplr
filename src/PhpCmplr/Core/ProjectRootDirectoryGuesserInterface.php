<?php

namespace PhpCmplr\Core;

interface ProjectRootDirectoryGuesserInterface
{
    /**
     * @param string $path
     *
     * @return string|null
     */
    public function getProjectRootDir($path);
}
