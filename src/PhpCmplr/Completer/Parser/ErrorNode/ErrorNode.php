<?php

namespace PhpCmplr\Completer\Parser\ErrorNode;

use PhpParser\NodeAbstract;

abstract class ErrorNode extends NodeAbstract
{
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
    }

    public function getType() {
        return strtr(substr(rtrim(get_class($this), '_'), 26), '\\', '_');
    }

    public function getSubNodeNames()
    {
        return [];
    }
}
