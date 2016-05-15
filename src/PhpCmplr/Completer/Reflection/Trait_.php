<?php

namespace PhpCmplr\Completer\Reflection;

class Trait_ extends ClassLike
{
    use TraitUserTrait;

    public function addConst(Const_ $const)
    {
        throw new \LogicException("Trait can't have consts");
    }
}
