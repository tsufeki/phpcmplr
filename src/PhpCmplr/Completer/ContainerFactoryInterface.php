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
    public function create($path, $contents, array $options = []);
}
