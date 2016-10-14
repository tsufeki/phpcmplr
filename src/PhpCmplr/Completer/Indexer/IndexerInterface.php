<?php

namespace PhpCmplr\Completer\Indexer;

use PhpCmplr\Completer\Runnable;
use PhpCmplr\Completer\Quittable;

interface IndexerInterface extends Runnable, Quittable
{
    /**
     * @param string $key
     *
     * @return array
     */
    public function getData($key);
}
