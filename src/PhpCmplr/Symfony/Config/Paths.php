<?php

namespace PhpCmplr\Symfony\Config;

use PhpCmplr\Core\Component;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Core\Project;
use PhpCmplr\Core\FileStoreInterface;
use PhpCmplr\Core\Container;
use Psr\Log\LoggerInterface;
use PhpCmplr\Util\IOException;
use PhpCmplr\Core\Reflection\LocatorInterface;

class Paths extends Component implements PathsInterface
{
    /**
     * @var string
     */
    private $appConfigPath;

    /**
     * @var string[]
     */
    private $bundlesPaths;

    public function getAppConfigPath()
    {
        $this->run();

        return $this->appConfigPath;
    }

    public function getBundlesPaths()
    {
        $this->run();

        return $this->bundlesPaths;
    }

    protected function doRun()
    {
        /** @var Project */
        $project = $this->container->get('project');
        /** @var FileStoreInterface */
        $fileStore = $this->container->get('file_store');
        /** @var FileIOInterface */
        $io = $this->container->get('io');
        /** @var LoggerInterface */
        $logger = $this->container->get('logger');
        /** @var LocatorInterface[] */
        $locators = $this->container->getByTag('reflection.locator');

        $this->appConfigPath = $project->getRootPath() . '/app/config';
        $this->bundlesPaths = [];

        try {
            $kernelPath = $project->getRootPath() . '/app/AppKernel.php';
            /** @var Container $cont */
            $cont = $fileStore->getFile($kernelPath);
            if ($cont === null) {
                $cont = $fileStore->addFile($kernelPath, $io->read($kernelPath));
            }

            $extractor = new BundleClassesExtractor($cont);
            $classes = $extractor->getClasses();
            foreach ($classes as $class) {
                $bundlePath = null;
                foreach ($locators as $locator) {
                    $paths = $locator->getPathsForClass($class);
                    if (!empty($paths)) {
                        $bundlePath = dirname($paths[0]);
                        break;
                    }
                }
                if ($bundlePath) {
                    $this->bundlesPaths[] = $bundlePath;
                    $logger->debug(sprintf("Symfony: located bundle %s", $bundlePath));
                } else {
                    $logger->debug(sprintf("Symfony: can't locate bundle %s", $class));
                }
            }
        } catch (IOException $e) {
            $logger->info("Symfony: no kernel found.");
        }
    }
}
