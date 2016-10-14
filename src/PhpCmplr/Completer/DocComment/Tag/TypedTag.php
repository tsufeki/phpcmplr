<?php

namespace PhpCmplr\Completer\DocComment\Tag;

use PhpCmplr\Completer\Type\Type;

abstract class TypedTag extends Tag
{
    /**
     * @var Type
     */
    protected $type;

    /**
     * @var string
     */
    protected $description;

    /**
     * @param string $name
     * @param string $text
     */
    protected function __construct($name, $text)
    {
        parent::__construct($name, $text);
        $text = trim($text);
        $parts = preg_split('~\\s+~', trim($text), 2);
        $this->type = Type::fromString($parts[0]);
        if (count($parts) >= 2) {
            $this->description = $parts[1] ?: null;
        }
    }

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
}
