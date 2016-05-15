<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\ComponentInterface;

interface ReflectionComponentInterface extends ComponentInterface
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
}
