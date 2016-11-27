<?php

namespace PhpCmplr\Core\Indexer;

interface IndexDataInterface
{
    /**
     * @return string
     */
    public function getKey();

    /**
     * @param array $indexData
     */
    public function update(array &$indexData);
}
