<?php

namespace PhpCmplr;

use PhpCmplr\Core\Container;
use PhpCmplr\Server\Server;

/**
 * PhpCmplr plugin base class.
 */
abstract class Plugin
{
    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return [];
    }

    /**
     * @param Server $server
     */
    public function addActions(Server $server, array $options)
    {
    }

    /**
     * @param Container $container
     * @param array     $options
     */
    public function addGlobalComponents(Container $container, array $options)
    {
    }

    /**
     * @param Container $container
     * @param array     $options
     */
    public function addProjectComponents(Container $container, array $options)
    {
    }

    /**
     * @param Container $container
     * @param array     $options
     */
    public function addFileComponents(Container $container, array $options)
    {
    }

    /**
     * @param Container $container
     * @param array     $options
     */
    public function addIndexerComponents(Container $container, array $options)
    {
    }
}
