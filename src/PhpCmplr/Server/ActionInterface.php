<?php

namespace PhpCmplr\Server;

use PhpCmplr\PhpCmplr;
use Psr\Log\LoggerInterface;

interface ActionInterface
{
    /**
     * Path which invokes this action.
     *
     * @return string
     */
    public function getPath();

    /**
     * Handle request.
     *
     * @param string   $body
     * @param PhpCmplr $phpcmplr
     *
     * @return string
     * @throws HttpException
     */
    public function handleRequest($body, PhpCmplr $phpcmplr);

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger);
}
