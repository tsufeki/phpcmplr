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

    public function __construct()
    {
        $this->containers = [];
    }

    public function addFile($path, $contents)
    {
        $this->containers[$path] = $this->createContainer($path, $contents);

        return $this;
    }

    public function removeFile($path)
    {
        if (array_key_exists($path, $this->containers)) {
            unset($this->containers[$path]);
        }

        return $this;
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

    protected function createContainer($path, $contents)
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', new Parser\ParserComponent($container));
        $container->set('diagnostics', new Diagnostics\DiagnosticsComponent($container));

        return $container;
    }
}
