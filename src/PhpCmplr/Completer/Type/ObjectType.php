<?php

namespace PhpCmplr\Completer\Type;

class ObjectType extends Type
{
    /**
     * @var string|null
     */
    private $class;

    /**
     * @var string|null
     */
    private $unresolvedClass;

    /**
     * @param string|null       $class
     * @param string|null|false $unresolvedClass
     */
    public function __construct($class, $unresolvedClass = false)
    {
        parent::__construct('object');
        $this->class = $class;
        $this->unresolvedClass = $unresolvedClass === false ? $class : $unresolvedClass;
    }

    /**
     * @return string|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string|null
     */
    public function getUnresolvedClass()
    {
        return $this->unresolvedClass;
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
        /** @var self $other */
        return $cmp !== 0 ? $cmp : strcasecmp($this->getClass(), $other->getClass());
    }
}
