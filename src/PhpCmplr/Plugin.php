<?php

namespace PhpCmplr;

use PhpCmplr\Completer\Container;
use PhpCmplr\Server\Server;

/**
 * PhpCmplr plugin base class.
 */
abstract class Plugin
{
    /**
     * @param Server $server
     */
    public function addActions(Server $server)
    {
    }

    /**
     * @param Container $container
     */
    public function addComponents(Container $container)
    {
    }
}
