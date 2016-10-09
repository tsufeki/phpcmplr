<?php

namespace PhpCmplr\Completer;

interface ContainerFactoryInterface
{
    /**
     * @return Container
     */
    public function getGlobalContainer();

    /**
     * @param string $rootPath
     *
     * @return Project
     */
    public function createProject($rootPath);

    /**
     * @param Project $project
     * @param string  $path
     * @param string  $contents
     *
     * @return Container
     */
    public function createFileContainer(Project $project, $path, $contents);
}
