<?php

namespace PhpCmplr\Completer\Reflection\Element;

class Interface_ extends ClassLike
{
    /**
     * @var string[]
     */
    private $extends = [];

    /**
     * @return string[]
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
    public function addExtends($extends)
    {
        $this->extends[] = $extends;

        return $this;
    }
}
