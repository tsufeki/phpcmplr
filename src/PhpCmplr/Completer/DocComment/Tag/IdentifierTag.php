<?php

namespace PhpCmplr\Completer\DocComment\Tag;

abstract class IdentifierTag extends TypedTag
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @param string $name
     * @param string $text
     */
    protected function __construct($name, $text)
    {
        parent::__construct($name, $text);
        if ($this->description !== null) {
            $text = $this->description;
            if (substr($text, 0, 1) === '$') {
                $parts = preg_split('~\\s+~', $text, 2);
                $this->identifier = $parts[0];
                $text = count($parts) >= 2 ? $parts[1] : "";
                $this->description = $text ?: null;
            }
        }
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
