<?php

namespace PhpCmplr\Completer;

/**
 * Collection of containers, one per file.
 */
class Project
{
    /**
     * @var string
     */
    private $rootPath;

    /**
     * @var Container
     */
    private $projectContainer;

    /**
     * @var Container[]
     */
    private $fileContainers;

    /**
     * @var array
     */
    private $options;

    /**
     * @param string    $rootPath
     * @param Container $projectContainer
     */
    public function __construct($rootPath, Container $projectContainer, array $options)
    {
        $this->rootPath = $rootPath;
        $this->projectContainer = $projectContainer;
        $this->fileContainers = [];
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * @return Container
     */
    public function getProjectContainer()
    {
        return $this->projectContainer;
    }

    /**
     * @param string    $path
     * @param Container $container
     */
    public function addFile($path, Container $container)
    {
        $this->fileContainers[$path] = $container;
    }

    /**
     * @param string $path
     */
    public function removeFile($path)
    {
        if (array_key_exists($path, $this->fileContainers)) {
            unset($this->fileContainers[$path]);
        }
    }

    /**
     * @param string $path
     *
     * @return Container|null
     */
    public function getFile($path)
    {
        if (array_key_exists($path, $this->fileContainers)) {
            return $this->fileContainers[$path];
        }

        return null;
    }

    /**
     * @return Container[]
     */
    public function getFiles()
    {
        return $this->fileContainers;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function quit()
    {
        foreach ($this->fileContainers as $fileContainer) {
            $fileContainer->quit();
        }

        $this->projectContainer->quit();
    }
}
