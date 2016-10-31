<?php

namespace PhpCmplr\Completer;

use Psr\Log\LoggerInterface;
use PhpCmplr\Util\FileIOInterface;

class FileStore implements FileStoreInterface
{
    /**
     * @var ContainerFactoryInterface
     */
    private $factory;

    /**
     * @var string[] file path => project root dir
     */
    private $projectRootDirCache;

    /**
     * @var Project[]
     */
    private $projects;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->factory = $container->get('factory');
        $this->projects = [];
        $this->projectRootDirCache = [];
        $this->logger = $container->get('logger');
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getProjectRootPath($path)
    {
        if (array_key_exists($path, $this->projectRootDirCache)) {
            return $this->projectRootDirCache[$path];
        }

        /** @var ProjectRootDirectoryGuesserInterface */
        $rootGuesser = $this->factory->getGlobalContainer()->get('project_root_dir');
        $rootPath = $rootGuesser->getProjectRootDir($path);
        if (empty($rootPath)) {
            $rootPath = '/';
        }
        $this->projectRootDirCache[$path] = $rootPath;

        return $rootPath;
    }

    public function getFile($path)
    {
        /** @var FileIOInterface */
        $io = $this->factory->getGlobalContainer()->get('io');
        $path = $io->canonicalPath($path);
        $projectRootPath = $this->getProjectRootPath($path);

        if (array_key_exists($projectRootPath, $this->projects)) {
            return $this->projects[$projectRootPath]->getFile($path);
        }

        return null;
    }

    public function addFile($path, $contents)
    {
        /** @var FileIOInterface */
        $io = $this->factory->getGlobalContainer()->get('io');
        $path = $io->canonicalPath($path);
        $projectRootPath = $this->getProjectRootPath($path);

        if (!array_key_exists($projectRootPath, $this->projects)) {
            $this->logger->debug(sprintf('File store: add %s to project %s', $path, $projectRootPath));
            $this->projects[$projectRootPath] = $this->factory->createProject($projectRootPath);
        }
        $project = $this->projects[$projectRootPath];
        $fileContainer = $this->factory->createFileContainer($project, $path, $contents);
        $project->addFile($path, $fileContainer);

        return $fileContainer;
    }

    public function quit()
    {
        foreach ($this->projects as $project) {
            $project->quit();
        }
    }
}
