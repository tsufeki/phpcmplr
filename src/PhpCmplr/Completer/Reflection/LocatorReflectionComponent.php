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
     * @var Function_[][]
     */
    private $functionCache = [];

    /**
     * @var Const_[][]
     */
    private $constCache = [];

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

        if (array_key_exists(strtolower($fullyQualifiedName), $this->classCache)) {
            return $this->classCache[strtolower($fullyQualifiedName)];
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

        return $this->classCache[strtolower($fullyQualifiedName)] = $classes;
    }

    public function findFunction($fullyQualifiedName)
    {
        $this->run();

        if (array_key_exists(strtolower($fullyQualifiedName), $this->functionCache)) {
            return $this->functionCache[strtolower($fullyQualifiedName)];
        }

        $functions = [];
        foreach ($this->locators as $locator) {
            foreach ($locator->getPathsForFunction($fullyQualifiedName) as $path) {
                try {
                    /** @var Container $cont */
                    $cont = $this->project->getFile($path);
                    if ($cont === null) {
                        $cont = $this->project->addFile($path, $this->io->read($path));
                    }
                    $file = $cont->get('reflection.file');
                    if ($file !== null) {
                        $functions = array_merge($functions, $file->findFunction($fullyQualifiedName));
                    }
                } catch (IOException $e) {
                }
            }
        }

        return $this->functionCache[strtolower($fullyQualifiedName)] = $functions;
    }

    public function findConst($fullyQualifiedName)
    {
        $this->run();

        if (array_key_exists($fullyQualifiedName, $this->constCache)) {
            return $this->constCache[$fullyQualifiedName];
        }

        $consts = [];
        foreach ($this->locators as $locator) {
            foreach ($locator->getPathsForConst($fullyQualifiedName) as $path) {
                try {
                    /** @var Container $cont */
                    $cont = $this->project->getFile($path);
                    if ($cont === null) {
                        $cont = $this->project->addFile($path, $this->io->read($path));
                    }
                    $file = $cont->get('reflection.file');
                    if ($file !== null) {
                        $consts = array_merge($consts, $file->findConst($fullyQualifiedName));
                    }
                } catch (IOException $e) {
                }
            }
        }

        return $this->constsCache[$fullyQualifiedName] = $consts;
    }

    protected function doRun()
    {
        $this->locators = $this->container->getByTag('reflection.locator');
    }
}
