<?php

namespace PhpCmplr\Core\DocComment\Tag;

use PhpCmplr\Core\Type\Type;

abstract class TypedTag extends Tag
{
    /**
     * @var Type
     */
    private $type;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $typeStartPos;

    /**
     * @var int
     */
    private $typeEndPos;

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Type $type
     *
     * @return $this
     */
    public function setType(Type $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return int
     */
    public function getTypeStartPos()
    {
        return $this->typeStartPos;
    }

    /**
     * @param int $typeStartPos
     *
     * @return $this
     */
    public function setTypeStartPos($typeStartPos)
    {
        $this->typeStartPos = $typeStartPos;

        return $this;
    }

    /**
     * @return int
     */
    public function getTypeEndPos()
    {
        return $this->typeEndPos;
    }

    /**
     * @param int $typeEndPos
     *
     * @return $this
     */
    public function setTypeEndPos($typeEndPos)
    {
        $this->typeEndPos = $typeEndPos;

        return $this;
    }
}
