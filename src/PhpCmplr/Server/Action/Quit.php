<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\PhpCmplr;
use PhpCmplr\Server\Action;
use PhpCmplr\Server\Server;

/**
 * Quit the server.
 */
class Quit extends Action
{
    /**
     * @var Server
     */
    private $server;

    public function __construct(Server $server, $path = '/quit')
    {
        parent::__construct($path);
        $this->server = $server;
    }

    protected function handle($data, PhpCmplr $phpcmplr)
    {
        $this->server->quit();

        return new \stdClass();
    }
}
