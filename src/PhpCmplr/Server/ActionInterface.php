<?php

namespace PhpCmplr\Server;

use PhpCmplr\Completer\Project;

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
     * @param string  $body
     * @param Project $project
     *
     * @return string
     * @throws HttpException
     */
    public function handleRequest($body, Project $project);

    /**
     * @param mixed $logger
     */
    public function setLogger($logger);
}
