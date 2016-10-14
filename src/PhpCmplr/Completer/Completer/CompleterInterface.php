<?php

namespace PhpCmplr\Completer\Completer;

interface CompleterInterface
{
    /**
     * Find completions at offset.
     *
     * @param int $offset
     *
     * @return Completion[]
     */
    public function complete($offset);
}
