<?php

namespace PhpCmplr\Core\TypeInferrer;

use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\Runnable;

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
