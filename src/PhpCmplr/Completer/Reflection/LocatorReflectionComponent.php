<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Project;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Util\IOException;

class LocatorReflectionComponent extends Component implements ReflectionComponentInterface
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var FileIOInterface
     */
    private $io;

    /**
     * @var Locator[]
     */
    private $locators;

    /**
     * @var ClassLike[][]
     */
    private $classCache = [];

    /**
     * @param Container       $container
     * @param Project         $project
     * @param FileIOInterface $io
     */
    public function __construct(Container $container, Project $project, FileIOInterface $io)
    {
        parent::__construct($container);
        $this->project = $project;
        $this->io = $io;
    }

    public function findClass($fullyQualifiedName)
    {
        $this->run();

        if (array_key_exists($fullyQualifiedName, $this->classCache)) {
            return $this->classCache[$fullyQualifiedName];
        }

        $classes = [];
        foreach ($this->locators as $locator) {
            foreach ($locator->getPathsForClass($fullyQualifiedName) as $path) {
                try {
                    /** @var Container $cont */
                    $cont = $this->project->getFile($path);
                    if ($cont === null) {
                        $cont = $this->project->addFile($path, $this->io->read($path));
                    }
                    $file = $cont->get('reflection.file');
                    if ($file !== null) {
                        $classes = array_merge($classes, $file->findClass($fullyQualifiedName));
                    }
                } catch (IOException $e) {
                }
            }
        }

        return $this->classCache[$fullyQualifiedName] = $classes;
    }

    public function findFunction($fullyQualifiedName)
    {
        // TODO
        $this->run();
        return [];
    }

    public function findConst($fullyQualifiedName)
    {
        // TODO
        $this->run();
        return [];
    }

    protected function doRun()
    {
        $this->locators = $this->container->getByTag('reflection.locator');
    }
}
