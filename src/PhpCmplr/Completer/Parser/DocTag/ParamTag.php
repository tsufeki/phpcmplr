<?php

namespace PhpCmplr\Completer\Parser\DocTag;

class ParamTag extends DocTag
{
    /**
     * @var Type
     */
    public $type;

    /**
     * @var string
     */
    public $identifier;

    /**
     * @var string
     */
    public $description;

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
            $text = $parts[1];
            if (substr($text, 0, 1) === '$') {
                $parts = preg_split('~\\s+~', $text, 2);
                $this->identifier = $parts[0];
                $text = count($parts) >= 2 ? $parts[1] : "";
            }
            $this->description = $text ?: null;
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
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
