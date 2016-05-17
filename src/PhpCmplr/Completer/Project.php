<?php

namespace PhpCmplr\Completer;

/**
 * Collection of containers, one per file.
 */
class Project
{
    /**
     * @var Container[]
     */
    private $containers;

    /**
     * @var ContainerFactoryInterface
     */
    private $factory;

    public function __construct(ContainerFactoryInterface $containerFactory)
    {
        $this->containers = [];
        $this->factory = $containerFactory;
    }

    public function addFile($path, $contents, array $options = [])
    {
        return $this->containers[$path] = $this->factory->create($path, $contents, $options);
    }

    public function removeFile($path)
    {
        if (array_key_exists($path, $this->containers)) {
            unset($this->containers[$path]);
        }
    }

    public function getFile($path)
    {
        if (array_key_exists($path, $this->containers)) {
            return $this->containers[$path];
        }

        return null;
    }

    public function getFiles()
    {
        return $this->containers;
    }
}
