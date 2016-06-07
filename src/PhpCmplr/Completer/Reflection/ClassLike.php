<?php

namespace PhpCmplr\Completer\Reflection;

/**
 * Class, interface or trait base.
 *
 * Not clonable.
 */
abstract class ClassLike extends Element
{
    const M_PRIVATE = 1;
    const M_PROTECTED = 2;
    const M_PUBLIC = 3;

    /**
     * @var ClassConst[]
     */
    private $consts = [];

    /**
     * @var Property[]
     */
    private $properties = [];

    /**
     * @var Method[]
     */
    private $methods = [];

    /**
     * @return ClassConst[]
     */
    public function getConsts()
    {
        return $this->consts;
    }

    /**
     * @param ClassConst $const
     *
     * @return $this
     */
    public function addConst(ClassConst $const)
    {
        $this->consts[] = $const;

        return $this;
    }

    /**
     * @return Property[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param Property $property
     *
     * @return $this
     */
    public function addProperty(Property $property)
    {
        $this->properties[] = $property;

        return $this;
    }

    /**
     * @return Method[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param Method $method
     *
     * @return $this
     */
    public function addMethod(Method $method)
    {
        $this->methods[] = $method;

        return $this;
    }
}
