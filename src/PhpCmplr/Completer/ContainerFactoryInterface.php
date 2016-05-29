<?php

namespace PhpCmplr\Completer;

interface ContainerFactoryInterface
{
    /**
     * @param string $path
     * @param string $contents
     * @param array  $options
     *
     * @return Container
     */
    public function createContainer($path, $contents, array $options = []);
}
