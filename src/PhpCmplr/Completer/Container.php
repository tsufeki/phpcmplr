<?php

namespace PhpCmplr\Completer;

class Container
{
    /**
     * @var Component[]
     */
    private $components;

    public function __construct()
    {
        $this->components = [];
    }

    /**
     * @param string $componentKey
     *
     * @return ComponentInterface
     */
    public function get($componentKey)
    {
        if (array_key_exists($componentKey, $this->components)) {
            return $this->components[$componentKey];
        }

        return null;
    }

    /**
     * @param string             $componentKey
     * @param ComponentInterface $component
     *
     * @return $this
     */
    public function set($componentKey, ComponentInterface $component)
    {
        $this->components[$componentKey] = $component;

        return $this;
    }
}
