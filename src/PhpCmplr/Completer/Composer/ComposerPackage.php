<?php

namespace PhpCmplr\Completer\Composer;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Autoload\AutoloadGenerator;
use Composer\Autoload\ClassLoader;

use PhpCmplr\Util\FileIOInterface;

/**
 * Local composer package.
 */
class ComposerPackage
{
    const COMPOSER_CONFIG = 'composer.json';

    /**
     * @var ComposerPackage[]
     */
    private static $packages = [];

    /**
     * @var string
     */
    private $baseDir;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var ClassLoader
     */
    private $classLoader;

    /**
     * @param string $baseDir Root directory of composer package.
     */
    public function __construct($baseDir)
    {
        $this->baseDir = $baseDir;

        $io = new NullIO();
        $this->composer = (new Factory())->createComposer(
            $io,
            $baseDir . '/composer.json',
            true,
            $baseDir,
            false);
        $generator = new AutoloadGenerator($this->composer->getEventDispatcher(), $io);
        $generator->setDevMode(true);
        $packages = $this->composer
            ->getRepositoryManager()
            ->getLocalRepository()
            ->getPackages();
        $packageMap = $generator->buildPackageMap(
            $this->composer->getInstallationManager(),
            $this->composer->getPackage(),
            $packages);
        $packageMap[0][1] = $baseDir; // To make root package paths absolute too.
        $autoloads = $generator->parseAutoloads(
            $packageMap,
            $this->composer->getPackage());
        $this->classLoader = $generator->createLoader($autoloads);
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * Find file(s) defining given class.
     *
     * @param string $class Fully qualified name.
     *
     * @return string[] Paths.
     */
    public function getPathsForClass($class)
    {
        if ($class !== '' && $class[0] === '\\') {
            $class = substr($class, 1);
        }
        $file = $this->classLoader->findFile($class);
        // TODO: ensure it's an absolute path.
        return is_string($file) ? [str_replace('//', '/', $file)] : [];
    }

    /**
     * Find a composer (root) package given file belongs to.
     *
     * @param string          $phpFilePath
     * @param FileIOInterface $io
     *
     * @return ComposerPackage|null
     */
    public static function get($phpFilePath, FileIOInterface $io)
    {
        $baseDir = null;
        $oldPath = $phpFilePath;
        $path = dirname($oldPath);
        while ($oldPath !== $path) {
            $config = $path . '/' . self::COMPOSER_CONFIG;
            if ($io->exists($config)) {
                $baseDir = $path;
            }
            $oldPath = $path;
            $path = dirname($path);
        }

        if ($baseDir === null) {
            return null;
        }

        if (array_key_exists($baseDir, self::$packages)) {
            return self::$packages[$baseDir];
        }

        return self::$packages[$baseDir] = new static($baseDir);
    }
}
