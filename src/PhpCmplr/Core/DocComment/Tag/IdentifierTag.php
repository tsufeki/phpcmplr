<?php

namespace PhpCmplr\Core\DocComment\Tag;

abstract class IdentifierTag extends TypedTag
{
    /**
     * @var string|null
     */
    private $identifier;

    /**
     * @return string|null
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string|null $identifier
     *
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }
}
