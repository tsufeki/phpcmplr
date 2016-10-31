<?php

namespace PhpCmplr\Completer\DocComment\Tag;

use PhpCmplr\Completer\Type\Type;

class Tag
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $text;

    /**
     * @var int|null
     */
    private $startPos;

    /**
     * @var int|null
     */
    private $endPos;

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
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return int
     */
    public function getStartPos()
    {
        return $this->startPos;
    }

    /**
     * @param int $startPos
     *
     * @return $this
     */
    public function setStartPos($startPos)
    {
        $this->startPos = $startPos;

        return $this;
    }

    /**
     * @return int
     */
    public function getEndPos()
    {
        return $this->endPos;
    }

    /**
     * @param int $endPos
     *
     * @return $this
     */
    public function setEndPos($endPos)
    {
        $this->endPos = $endPos;

        return $this;
    }

    /**
     * @param string $name
     * @param string $text
     *
     * @return Tag
     */
    public static function get($name, $text, $startPos = null, $textStartPos = null)
    {
        $tag = null;
        switch ($name) {
            case 'throw':
                $name = 'throws';
            case 'return':
            case 'throws':
                $tag = $name === 'return' ? new ReturnTag() : new ThrowsTag();
                preg_match('~^(\\S*)(\\s*)(.*)$~s', $text, $matches);
                $tag->setType(Type::fromString($matches[1]));
                $tag->setDescription(trim($matches[3]) ?: null);
                $tag->setTypeStartPos($textStartPos);
                $tag->setTypeEndPos($textStartPos + strlen($matches[1]) - 1);
                break;
            case 'var':
            case 'param':
                $tag = $name === 'var' ? new VarTag() : new ParamTag();
                preg_match('~^(\\S*)(\\s*)(\\$\\S*|)(\\s*)(.*)$~s', $text, $matches);
                $tag->setType(Type::fromString($matches[1]));
                $tag->setDescription(trim($matches[5]) ?: null);
                $tag->setIdentifier(trim($matches[3]) ?: null);
                $tag->setTypeStartPos($textStartPos);
                $tag->setTypeEndPos($textStartPos + strlen($matches[1]) - 1);
                break;
            default:
                $tag = new Tag();
                break;
        }

        $tag->setName($name);
        $tag->setText($text);
        $tag->setStartPos($startPos);
        if ($textStartPos !== null) {
            $tag->setEndPos($textStartPos + strlen($text) - 1);
        }

        return $tag;
    }
}
