<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Parser\DocTag\Type;

class Function_ extends Element
{
    /**
     * @var Param[]
     */
    private $params = [];

    /**
     * @var Type
     */
    private $returnType = null;

    /**
     * @var Type
     */
    private $docReturnType = null;

    /**
     * @var bool
     */
    private $returnByRef = false;

    public function __clone()
    {
        parent::__clone();
        $oldParams = $this->params;
        $this->params = [];
        foreach ($oldParams as $param) {
            $this->params[] = clone $param;
        }
    }

    /**
     * @return Param[]
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param Param $param
     *
     * @return $this
     */
    public function addParam(Param $param)
    {
        $this->params[] = $param;

        return $this;
    }

    /**
     * @return Type
     */
    public function getReturnType()
    {
        return $this->returnType;
    }

    /**
     * @param Type $returnType
     *
     * @return $this
     */
    public function setReturnType(Type $returnType)
    {
        $this->returnType = $returnType;

        return $this;
    }

    /**
     * @return Type
     */
    public function getDocReturnType()
    {
        return $this->docReturnType;
    }

    /**
     * @param Type $docReturnType
     *
     * @return $this
     */
    public function setDocReturnType(Type $docReturnType)
    {
        $this->docReturnType = $docReturnType;

        return $this;
    }

    /**
     * @return bool
     */
    public function getReturnByRef()
    {
        return $this->returnByRef;
    }

    /**
     * @param bool $returnByRef
     *
     * @return $this
     */
    public function setReturnByRef($returnByRef)
    {
        $this->returnByRef = $returnByRef;

        return $this;
    }

    public function getParamsAsString()
    {
        $params = [];
        foreach ($this->getParams() as $param) {
            $params[] = $param->toString();
        }
        return '(' . implode(', ', $params) . ')';
    }
}
