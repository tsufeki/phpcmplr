<?php

namespace PhpCmplr\Completer\Reflection;

class TraitInsteadOf
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
     * Fully qualified names of overriden traits.
     *
     * @var string[]
     */
    private $insteadOfs;

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
     * @return string[]
     */
    public function getInsteadOfs()
    {
        return $this->insteadOfs;
    }

    /**
     * @param string $insteadOf
     *
     * @return $this
     */
    public function addInsteadOf($insteadOf)
    {
        $this->insteadOfs[] = $insteadOf;

        return $this;
    }
}
