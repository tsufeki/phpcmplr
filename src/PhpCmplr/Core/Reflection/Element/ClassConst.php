<?php

namespace PhpCmplr\Core\Reflection\Element;

// TODO: PHP 7.1 class const accesibility
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
