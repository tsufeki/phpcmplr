<?php

namespace PhpCmplr\Completer\Parser;

use PhpParser\NodeAbstract;

/**
 * This class is used for obtaining startFilePos and endFilePos attributes on name
 * in ClassConstFetch, PropertyFetch, MethodCall, StaticCall nodes.
 */
class Identifier extends NodeAbstract
{
    /**
     * @var string Identifier string.
     */
    public $name;

    /**
     * @param string $name
     * @param array  $attributes
     */
    public function __construct($name, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->name = $name;
    }

    public function getType()
    {
        return 'Identifier';
    }

    public function getSubNodeNames()
    {
        return array('name');
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
