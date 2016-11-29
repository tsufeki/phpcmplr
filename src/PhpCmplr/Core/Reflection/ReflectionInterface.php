<?php

namespace PhpCmplr\Core\Reflection;

use PhpCmplr\Core\Reflection\Element\ClassLike;
use PhpCmplr\Core\Reflection\Element\Function_;
use PhpCmplr\Core\Reflection\Element\Const_;

interface ReflectionInterface
{
    /**
     * Find semantic information about a class, interface or trait.
     *
     * @param string $fullyQualifiedName
     *
     * @return ClassLike[]
     */
    public function findClass($fullyQualifiedName);

    /**
     * Find semantic information about a function (not a method).
     *
     * @param string $fullyQualifiedName
     *
     * @return Function_[]
     */
    public function findFunction($fullyQualifiedName);

    /**
     * Find semantic information about a non-class const.
     *
     * @param string $fullyQualifiedName
     *
     * @return Const_[]
     */
    public function findConst($fullyQualifiedName);
}
