<?php

namespace PhpCmplr\Completer;

class FileStore
{
    /**
     * @var ContainerFactoryInterface
     */
    private $factory;

    /**
     * @var string[] file path => project root dir
     */
    private $projectRootDirCache;

    /**
     * @var Project[]
     */
    private $projects;

    /**
     * @param ContainerFactoryInterface $factory
     */
    public function __construct(ContainerFactoryInterface $factory)
    {
        $this->factory = $factory;
        $this->projects = [];
        $this->projectRootDirCache = [];
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getProjectRootPath($path)
    {
        if (array_key_exists($path, $this->projectRootDirCache)) {
            return $this->projectRootDirCache[$path];
        }

        $rootPath = $this->factory->getGlobalContainer()->get('project_root_dir')->getProjectRootDir($path);
        if (empty($rootPath)) {
            $rootPath = '/';
        }
        $this->projectRootDirCache[$path] = $rootPath;

        return $rootPath;
    }

    public function getFile($path)
    {
        $path = $this->factory->getGlobalContainer()->get('io')->canonicalPath($path);
        $projectRootPath = $this->getProjectRootPath($path);

        if (array_key_exists($projectRootPath, $this->projects)) {
            return $this->projects[$projectRootPath]->getFile($path);
        }

        return null;
    }

    public function addFile($path, $contents)
    {
        $path = $this->factory->getGlobalContainer()->get('io')->canonicalPath($path);
        $projectRootPath = $this->getProjectRootPath($path);

        if (!array_key_exists($projectRootPath, $this->projects)) {
            $this->projects[$projectRootPath] = $this->factory->createProject($projectRootPath);
        }
        $project = $this->projects[$projectRootPath];
        $fileContainer = $this->factory->createFileContainer($project, $path, $contents);
        $project->addFile($path, $fileContainer);

        return $fileContainer;
    }
}
