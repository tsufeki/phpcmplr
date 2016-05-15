<?php

namespace PhpCmplr\Completer\Reflection;

class Class_ extends ClassLike
{
    use TraitUserTrait;

    /**
     * @var bool
     */
    private $abstract = false;

    /**
     * @var bool
     */
    private $final = false;

    /**
     * @var string|null
     */
    private $extends = null;

    /**
     * @var string[]
     */
    private $implements = [];

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return $this->abstract;
    }

    /**
     * @param bool $abstract
     *
     * @return $this
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFinal()
    {
        return $this->final;
    }

    /**
     * @param bool $final
     *
     * @return $this
     */
    public function setFinal($final)
    {
        $this->final = $final;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * @param string $extends
     *
     * @return $this
     */
    public function setExtends($extends)
    {
        $this->extends = $extends;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getImplements()
    {
        return $this->implements;
    }

    /**
     * @param string $implements
     *
     * @return $this
     */
    public function addImplements($implements)
    {
        $this->implements[] = $implements;

        return $this;
    }
}
