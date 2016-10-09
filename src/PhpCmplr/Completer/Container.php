<?php

namespace PhpCmplr\Completer;

class Container
{
    /**
     * @var Container
     */
    private $parent;

    /**
     * @var object[]
     */
    private $components;

    /**
     * @var string[][]
     */
    private $componentKeysByTag;

    public function __construct(Container $parent = null)
    {
        $this->parent = $parent;
        $this->components = [];
        $this->componentKeysByTag = [];
    }

    /**
     * @param string $componentKey
     *
     * @return object|null
     */
    public function get($componentKey)
    {
        if (array_key_exists($componentKey, $this->components)) {
            return $this->components[$componentKey];
        }

        if ($this->parent !== null) {
            return $this->parent->get($componentKey);
        }

        return null;
    }

    /**
     * @param string $tag
     *
     * @return string[]
     */
    protected function getKeysByTag($tag)
    {
        $keys = [];
        if (array_key_exists($tag, $this->componentKeysByTag)) {
            $keys = $this->componentKeysByTag[$tag];
        }

        if ($this->parent !== null) {
            foreach ($this->parent->getKeysByTag($tag) as $key) {
                if (!array_key_exists($key, $this->components)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    /**
     * @param string $tag
     *
     * @return object[]
     */
    public function getByTag($tag)
    {
        $components = [];
        foreach ($this->getKeysByTag($tag) as $key) {
            $components[] = $this->get($key);
        }

        return $components;
    }

    /**
     * @param string   $componentKey
     * @param object   $component
     * @param string[] $tags
     *
     * @return $this
     */
    public function set($componentKey, $component = null, $tags = [])
    {
        if (array_key_exists($componentKey, $this->components)) {
            $this->remove($componentKey);
        }

        $this->components[$componentKey] = $component;
        foreach ($tags as $tag) {
            $this->componentKeysByTag[$tag][] = $componentKey;
        }

        return $this;
    }

    /**
     * @param string $componentKey
     */
    public function remove($componentKey)
    {
        unset($this->components[$componentKey]);
        foreach ($this->componentKeysByTag as &$keys) {
            foreach ($keys as $i => $key) {
                if ($key === $componentKey) {
                    unset($keys[$i]);
                }
            }
            unset($keys);
        }
    }
}
