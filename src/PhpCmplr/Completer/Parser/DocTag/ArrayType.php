<?php

namespace PhpCmplr\Completer\Parser\DocTag;

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
     * @param Type $valuetype
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

    public function compare(Type $other)
    {
        $cmp = parent::compare($other);
        if ($cmp !== 0) {
            return $cmp;
        }
        $cmp = $this->getValueType()->compare($other->getValueType());
        if ($cmp !== 0) {
            return $cmp;
        }
        return $this->getKeyType()->compare($other->getKeyType());
    }
}
