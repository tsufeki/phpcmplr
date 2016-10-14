<?php

namespace PhpCmplr\Completer\Reflection\Element;

class ClassConst extends Const_
{
    /**
     * @var ClassLike Class defining this member.
     */
    private $class;

    /**
     * @return ClassLike
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param ClassLike $class
     *
     * @return $this
     */
    public function setClass(ClassLike $class)
    {
        $this->class = $class;

        return $this;
    }
}
