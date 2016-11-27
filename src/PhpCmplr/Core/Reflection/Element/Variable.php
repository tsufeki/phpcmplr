<?php

namespace PhpCmplr\Core\Reflection\Element;

use PhpCmplr\Core\Type\Type;

class Variable extends Element
{
    /**
     * @var Type
     */
    private $type = null;

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Type $type
     *
     * @return $this
     */
    public function setType(Type $type)
    {
        $this->type = $type;

        return $this;
    }
}
