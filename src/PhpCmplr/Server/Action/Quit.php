<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\PhpCmplr;
use PhpCmplr\Server\Action;

/**
 * Quit the server.
 */
class Quit extends Action
{
    public function __construct($path = '/quit')
    {
        parent::__construct($path);
    }

    protected function handle($data, PhpCmplr $phpcmplr)
    {
        $phpcmplr->quit();

        return new \stdClass();
    }
}
