<?php

namespace PhpCmplr\Symfony\Config;

use PhpCmplr\Core\Component;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Core\Project;

class Paths extends Component implements PathsInterface
{
    /**
     * @var FileIOInterface
     */
    private $io;

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
        $this->appConfigPath = $project->getRootPath() . '/app/config';
        $this->bundlesPaths = []; //TODO
    }
}
