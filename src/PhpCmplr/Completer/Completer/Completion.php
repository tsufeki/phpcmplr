<?php

namespace PhpCmplr\Completer\Completer;

use PhpCmplr\Completer\Type\Type;

class Completion
{
    const KIND_VARIABLE = 'variable';
    const KIND_FUNCTION = 'function';
    const KIND_CONST = 'const';
    const KIND_PROPERTY = 'property';
    const KIND_STATIC_PROPERTY = 'static_property';
    const KIND_METHOD = 'method';
    const KIND_STATIC_METHOD = 'static_method';
    const KIND_CLASS_CONST = 'class_const';
    const KIND_CLASS = 'class';
    const KIND_INTERFACE = 'interface';
    const KIND_TRAIT = 'trait';
    const KIND_NAMESPACE = 'namespace';

    /**
     * @var string Inserted string.
     */
    private $insertion;

    /**
     * @var string Displayed in menu.
     */
    private $display;

    /**
     * @var string One of KIND_* consts.
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
