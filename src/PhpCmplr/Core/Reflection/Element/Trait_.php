<?php

namespace PhpCmplr\Core\Reflection\Element;

class Trait_ extends ClassLike
{
    use TraitUserTrait;

    public function addConst(ClassConst $const)
    {
        throw new \LogicException("Trait can't have consts");
    }
}
