<?php

namespace PhpCmplr\Core;

use PhpCmplr\PhpCmplr;
use PhpCmplr\Util\FileIOInterface;

class ProjectRootDirectoryGuesser implements ProjectRootDirectoryGuesserInterface
{
    const PROJECT_FILES = [PhpCmplr::PROJECT_OPTIONS_FILE, 'composer.json', '.git'];
    const PROJECT_IGNORE_FILE = '.phpcmplr-ignore';

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

        $oldPath = $path;
        $curPath = dirname($path);
        while ($oldPath !== $curPath) {
            if (!$this->io->exists($curPath . '/' . static::PROJECT_IGNORE_FILE)) {
                foreach (static::PROJECT_FILES as $projectFile) {
                    if ($this->io->exists($curPath . '/' . $projectFile)) {
                        $rootDir = $curPath;
                        break;
                    }
                }
            }
            $oldPath = $curPath;
            $curPath = dirname($curPath);
        }

        if ($rootDir !== null) {
            return $rootDir;
        }

        return null;
    }
}
