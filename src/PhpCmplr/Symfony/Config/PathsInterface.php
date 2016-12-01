<?php

namespace PhpCmplr\Symfony\Config;

interface PathsInterface
{
    /**
     * @return string
     */
    public function getAppConfigPath();

    /**
     * @return string[]
     */
    public function getBundlesPaths();
}
