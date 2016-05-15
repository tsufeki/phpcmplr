<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Parser\DocTag\Type;

class Param
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $byRef = false;

    /**
     * @var Type
     */
    private $typeHint = null;

    /**
     * @var Type
     */
    private $docType = null;

    /**
     * @var bool
     */
    private $variadic = false;

    /**
     * @var bool
     */
    private $optional = false;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isByRef()
    {
        return $this->byRef;
    }

    /**
     * @param bool $byRef
     *
     * @return $this
     */
    public function setByRef($byRef)
    {
        $this->byRef = $byRef;

        return $this;
    }

    /**
     * @return Type
     */
    public function getTypeHint()
    {
        return $this->typeHint;
    }

    /**
     * @param Type $typeHint
     *
     * @return $this
     */
    public function setTypeHint(Type $typeHint)
    {
        $this->typeHint = $typeHint;

        return $this;
    }

    /**
     * @return Type
     */
    public function getDocType()
    {
        return $this->docType;
    }

    /**
     * @param Type $docType
     *
     * @return $this
     */
    public function setDocType(Type $docType)
    {
        $this->docType = $docType;

        return $this;
    }

    /**
     * @return bool
     */
    public function isVariadic()
    {
        return $this->variadic;
    }

    /**
     * @param bool $variadic
     *
     * @return $this
     */
    public function setVariadic($variadic)
    {
        $this->variadic = $variadic;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * @param bool $optional
     *
     * @return $this
     */
    public function setOptional($optional)
    {
        $this->optional = $optional;

        return $this;
    }
}
