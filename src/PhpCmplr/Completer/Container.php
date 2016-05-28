<?php

namespace PhpCmplr\Completer;

class Container
{
    /**
     * @var object[]
     */
    private $components;

    /**
     * @var object[]
     */
    private $componentsByTag;

    public function __construct()
    {
        $this->components = [];
        $this->componentsByTag = [];
    }

    /**
     * @param string $componentKey
     *
     * @return object
     */
    public function get($componentKey)
    {
        if (array_key_exists($componentKey, $this->components)) {
            return $this->components[$componentKey];
        }

        return null;
    }

    /**
     * @param string $tag
     *
     * @return object[]
     */
    public function getByTag($tag)
    {
        if (array_key_exists($tag, $this->componentsByTag)) {
            return $this->componentsByTag[$tag];
        }

        return [];
    }

    /**
     * @param string   $componentKey
     * @param object   $component
     * @param string[] $tags
     *
     * @return $this
     */
    public function set($componentKey, $component, $tags = [])
    {
        $this->components[$componentKey] = $component;
        foreach ($tags as $tag) {
            $this->componentsByTag[$tag][] = $component;
        }

        return $this;
    }
}
