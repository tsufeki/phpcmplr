<?php

namespace PhpCmplr\Completer\Reflection\Element;

class Method extends Function_
{
    use MemberTrait;

    /**
     * @var bool
     */
    private $abstract = false;

    /**
     * @var bool
     */
    private $final = false;

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
}
