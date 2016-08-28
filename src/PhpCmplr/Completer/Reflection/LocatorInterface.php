<?php

namespace PhpCmplr\Completer\Reflection;

interface LocatorInterface
{
    /**
     * Get list of possible files defining the given class.
     *
     * @param string $fullyQualifiedName
     *
     * @return string[]
     */
    public function getPathsForClass($fullyQualifiedName);

    /**
     * Get list of possible files defining the given non-method function.
     *
     * @param string $fullyQualifiedName
     *
     * @return string[]
     */
    public function getPathsForFunction($fullyQualifiedName);

    /**
     * Get list of possible files defining the given non-class const.
     *
     * @param string $fullyQualifiedName
     *
     * @return string[]
     */
    public function getPathsForConst($fullyQualifiedName);
}
