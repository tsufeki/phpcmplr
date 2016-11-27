<?php

namespace PhpCmplr\Core\Reflection\Element;

class TraitAlias
{
    /**
     * Fully qualified name.
     *
     * @var string
     */
    private $trait;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $newName;

    /**
     * @var int|null
     */
    private $newAccessibility = null;

    /**
     * @return string
     */
    public function getTrait()
    {
        return $this->trait;
    }

    /**
     * @param string $trait
     *
     * @return $this
     */
    public function setTrait($trait)
    {
        $this->trait = $trait;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getNewName()
    {
        return $this->newName;
    }

    /**
     * @param string $newName
     *
     * @return $this
     */
    public function setNewName($newName)
    {
        $this->newName = $newName;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getNewAccessibility()
    {
        return $this->newAccessibility;
    }

    /**
     * @param int|null $newAccessibility
     *
     * @return $this
     */
    public function setNewAccessibility($newAccessibility)
    {
        $this->newAccessibility = $newAccessibility;

        return $this;
    }
}
