<?php

namespace PhpCmplr\Completer\TypeInferrer;

use PhpCmplr\Completer\Type\Type;

interface TypeInferrerInterface
{
    /**
     * Get type of the expression at offset.
     *
     * @param int $offset
     *
     * @return Type|null
     */
    public function getType($offset);
}
