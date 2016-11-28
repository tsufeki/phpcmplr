<?php

namespace PhpCmplr\Symfony\Config;

interface FileLoaderInterface
{
    /**
     * @return string[]
     */
    public function getSupportedExtensions();

    /**
     * @param string $fileContents
     * @param Config $config
     *
     * @return bool true if file succesfully consumed.
     */
    public function load($fileContents, Config $config);
}
