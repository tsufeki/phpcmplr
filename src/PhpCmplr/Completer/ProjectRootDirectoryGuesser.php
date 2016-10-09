<?php

namespace PhpCmplr\Completer;

use PhpCmplr\Util\FileIOInterface;

class ProjectRootDirectoryGuesser implements ProjectRootDirectoryGuesserInterface
{
    const PROJECT_FILES = ['composer.json', '.git'];

    /**
     * @var FileIOInterface
     */
    private $io;

    /**
     * @param FileIOInterface FileIOInterface $io
     */
    public function __construct(FileIOInterface $io)
    {
        $this->io = $io;
    }

    public function getProjectRootDir($path)
    {
        $rootDir = null;

        foreach (static::PROJECT_FILES as $projectFile) {
            $oldPath = $path;
            $curPath = dirname($path);
            while ($oldPath !== $curPath) {
                $projectFilePath = $curPath . '/' . $projectFile;
                if ($this->io->exists($projectFilePath)) {
                    $rootDir = $curPath;
                }
                $oldPath = $curPath;
                $curPath = dirname($curPath);
            }

            if ($rootDir !== null) {
                return $rootDir;
            }
        }

        return null;
    }
}
