<?php

namespace PhpCmplr\Completer\Type;

class ObjectType extends Type
{
    /**
     * @var string|null
     */
    private $class;

    /**
     * @param string|nulll $class
     */
    public function __construct($class)
    {
        parent::__construct('object');
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    public function toString($short = false)
    {
        if ($this->getClass()) {
            if ($short) {
                $parts = explode('\\', $this->getClass());
                return $parts[count($parts) - 1];
            } else {
                return $this->getClass();
            }
        }
        return $this->getName();
    }

    public function compare(Type $other)
    {
        $cmp = parent::compare($other);
        return $cmp !== 0 ? $cmp : strcasecmp($this->getClass(), $other->getClass());
    }
}
