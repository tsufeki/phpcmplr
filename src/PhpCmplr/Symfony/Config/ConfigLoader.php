<?php

namespace PhpCmplr\Symfony\Config;

use PhpCmplr\Core\Component;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Util\BasicFileFilter;
use PhpCmplr\Util\IOException;
use Psr\Log\LoggerInterface;

class ConfigLoader extends Component
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FileIOInterface
     */
    private $io;

    /**
     * @var Config
     */
    private $config;

    /**
     * @return Config
     */
    public function getConfig()
    {
        $this->run();

        return $this->config;
    }

    /**
     * @param FileLoaderInterface[] $fileLoaders
     *
     * @return FileLoaderInterface[][]
     */
    private function getFileExtensionMap(array $fileLoaders)
    {
        $map = [];
        foreach ($fileLoaders as $loader) {
            foreach ($loader->getSupportedExtensions() as $ext) {
                $map[strtolower($ext)][] = $loader;
            }
        }

        return $map;
    }

    /**
     * @param string $path
     * @param FileLoaderInterface[][] $extensionMap
     */
    private function loadFile($path, $extensionMap)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (array_key_exists($ext, $extensionMap)) {
            try {
                $contents = $this->io->read($path);
                foreach ($extensionMap[$ext] as $loader) {
                    if ($loader->load($contents, $this->config)) {
                        $this->logger->debug(sprintf("Symfony: load config file %s", $path));
                        break;
                    }
                }
            } catch (IOException $e) {
                $this->logger->notice(sprintf("Symfony: can't load config file %s",
                    $path), ['exception' => $e]);
            }
        }
    }

    protected function doRun()
    {
        $this->logger = $this->container->get('logger');
        $this->io = $this->container->get('io');
        /** @var PathsInterface */
        $paths = $this->container->get('symfony.paths');
        $fileLoaders = $this->container->getByTag('symfony.config_loader');
        $extensionMap = $this->getFileExtensionMap($fileLoaders);
        $this->config = new Config();

        $dirs = [];
        foreach ($paths->getBundlesPaths() as $path) {
            $dirs[] = $path . '/Resources/config';
        }
        $dirs[] = $paths->getAppConfigPath();
        $filter = new BasicFileFilter();

        foreach ($dirs as $dir) {
            foreach ($this->io->listFileMTimesRecursive($dir, $filter) as $path => $_) {
                $this->loadFile($path, $extensionMap);
            }
        }

        $this->config->resolve();
    }
}
