<?php

namespace PhpCmplr\Completer\Completer;

use PhpCmplr\Completer\Parser\DocTag\Type;

class Completion
{
    /**
     * @var string Inserted string.
     */
    private $insertion;

    /**
     * @var string Displayed in menu.
     */
    private $display;

    /**
     * @var string variable, function, const, property, static_property,
     *             method, static_method, class_const, class, interface, trait,
     *             namespace.
     */
    private $kind;

    /**
     * @var string Type or return type.
     */
    private $type;

    /**
     * @var string Long info.
     */
    private $description;

    /**
     * @return string
     */
    public function getInsertion()
    {
        return $this->insertion;
    }

    /**
     * @param string $insertion
     *
     * @return $this
     */
    public function setInsertion($insertion)
    {
        $this->insertion = $insertion;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param string $display
     *
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     *
     * @return $this
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
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
}
