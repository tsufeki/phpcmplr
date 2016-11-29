<?php

namespace PhpCmplr\Symfony\Config;

use PhpCmplr\Core\Component;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Core\Project;
use PhpCmplr\Core\FileStoreInterface;
use PhpCmplr\Core\Container;
use Psr\Log\LoggerInterface;
use PhpCmplr\Util\IOException;
use PhpCmplr\Core\Reflection\Reflection;

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
        /** @var Reflection */
        $reflection = $this->container->get('reflection');

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
                $reflClasses = $reflection->findClass($class);
                if (!empty($reflClasses) && null !== ($location = $reflClasses[0]->getLocation())) {
                    $this->bundlesPaths[] = dirname($location->getPath());
                }
            }
        } catch (IOException $e) {
            $logger->info("Symfony: no kernel found.");
        }
    }
}
