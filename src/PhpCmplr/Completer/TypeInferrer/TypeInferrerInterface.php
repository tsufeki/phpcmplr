<?php

namespace PhpCmplr\Completer\TypeInferrer;

use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Runnable;

interface TypeInferrerInterface extends Runnable
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
