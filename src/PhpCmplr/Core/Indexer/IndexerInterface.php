<?php

namespace PhpCmplr\Core\Indexer;

use PhpCmplr\Core\Runnable;
use PhpCmplr\Core\Quittable;

interface IndexerInterface extends Runnable, Quittable
{
    /**
     * @param string $key
     *
     * @return array
     */
    public function getData($key);
}
