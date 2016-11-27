<?php

namespace PhpCmplr\Core\Type;

class ArrayType extends Type
{
    /**
     * @var Type
     */
    private $valueType;

    /**
     * @var Type
     */
    private $keyType;

    /**
     * @param Type $valueType
     * @param Type $keyType
     */
    protected function __construct(Type $valueType, Type $keyType)
    {
        parent::__construct('array');
        $this->valueType = $valueType;
        $this->keyType = $keyType;
    }

    /**
     * @return Type
     */
    public function getValueType()
    {
        return $this->valueType;
    }

    /**
     * @return Type
     */
    public function getKeyType()
    {
        return $this->keyType;
    }

    public function toString($short = false)
    {
        $value = $this->getValueType();
        return self::mixed_()->equals($value) ? $this->getName() : $value->toString($short) . '[]';
    }

    public function compare(Type $other)
    {
        $cmp = parent::compare($other);
        if ($cmp !== 0) {
            return $cmp;
        }
        /** @var self $other */
        $cmp = $this->getValueType()->compare($other->getValueType());
        if ($cmp !== 0) {
            return $cmp;
        }
        return $this->getKeyType()->compare($other->getKeyType());
    }
}
