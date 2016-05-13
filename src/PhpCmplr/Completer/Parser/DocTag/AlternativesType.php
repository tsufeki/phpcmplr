<?php

namespace PhpCmplr\Completer\Parser\DocTag;

class AlternativesType extends Type
{
    /**
     * @var Type[]
     */
    private $alternatives;

    /**
     * @param Type[] $alternatives
     */
    public function __construct($alternatives)
    {
        parent::__construct('alternatives');
        $this->alternatives = $alternatives;
    }

    /**
     * @return Type[]
     */
    public function getAlternatives()
    {
        return $this->alternatives;
    }
}
