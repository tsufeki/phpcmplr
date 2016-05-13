<?php

namespace PhpCmplr\Completer\Parser\DocTag;

class ReturnTag extends DocTag
{
    /**
     * @var Type
     */
    public $type;

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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
