<?php

namespace PhpCmplr\Core\Reflection;

use PhpCmplr\Core\Component;
use PhpCmplr\Core\Container;
use PhpCmplr\Core\FileStoreInterface;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Util\IOException;

use PhpCmplr\Core\Reflection\Element\ClassLike;
use PhpCmplr\Core\Reflection\Element\Const_;
use PhpCmplr\Core\Reflection\Element\Function_;

class LocatorReflection extends Component implements ReflectionInterface
{
    /**
     * @var FileStoreInterface
     */
    private $fileStore;

    /**
     * @var FileIOInterface
     */
    private $io;

    /**
     * @var LocatorInterface[]
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
                    $cont = $this->fileStore->getFile($path);
                    if ($cont === null) {
                        $cont = $this->fileStore->addFile($path, $this->io->read($path));
                    }
                    /** @var FileReflection */
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
                    $cont = $this->fileStore->getFile($path);
                    if ($cont === null) {
                        $cont = $this->fileStore->addFile($path, $this->io->read($path));
                    }
                    /** @var FileReflection */
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
                    $cont = $this->fileStore->getFile($path);
                    if ($cont === null) {
                        $cont = $this->fileStore->addFile($path, $this->io->read($path));
                    }
                    /** @var FileReflection */
                    $file = $cont->get('reflection.file');
                    if ($file !== null) {
                        $consts = array_merge($consts, $file->findConst($fullyQualifiedName));
                    }
                } catch (IOException $e) {
                }
            }
        }

        return $this->constCache[$fullyQualifiedName] = $consts;
    }

    protected function doRun()
    {
        $this->io = $this->container->get('io');
        $this->fileStore = $this->container->get('file_store');
        $this->locators = $this->container->getByTag('reflection.locator');
    }
}
