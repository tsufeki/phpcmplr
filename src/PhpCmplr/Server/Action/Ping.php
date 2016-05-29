<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\Server\Action;

class Ping extends Action
{
    public function __construct($path = '/ping')
    {
        parent::__construct($path);
    }
}
