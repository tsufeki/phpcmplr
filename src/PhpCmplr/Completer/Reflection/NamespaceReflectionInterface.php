<?php

namespace PhpCmplr\Completer\Reflection;

interface NamespaceReflectionInterface
{
    /**
     * @param string $unqualifiedName
     *
     * @return string[]
     */
    public function findFullyQualifiedClasses($unqualifiedName);

    /**
     * @param string $unqualifiedName
     *
     * @return string[]
     */
    public function findFullyQualifiedFunctions($unqualifiedName);

    /**
     * @param string $unqualifiedName
     *
     * @return string[]
     */
    public function findFullyQualifiedConsts($unqualifiedName);
}
