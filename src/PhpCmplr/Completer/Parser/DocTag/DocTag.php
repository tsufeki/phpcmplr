<?php

namespace PhpCmplr\Completer\Parser\DocTag;

class DocTag
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $text;

    /**
     * @param string $name
     * @param string $text
     */
    protected function __construct($name, $text)
    {
        $this->name = $name;
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $name
     * @param string $text
     *
     * @return DocTag
     */
    public static function get($name, $text) {
        switch ($name) {
            case 'var':
                return new VarTag($name, $text);
            case 'param':
                return new ParamTag($name, $text);
            case 'return':
                return new ReturnTag($name, $text);
            case 'throws':
            case 'throw':
                return new ThrowsTag($name, $text);
            default:
                return new DocTag($name, $text);
        }
    }
}
