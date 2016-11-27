<?php

namespace PhpCmplr\Core\Reflection\Element;

trait TraitUserTrait
{
    /**
     * @var string[]
     */
    private $traits = [];

    /**
     * @var TraitInsteadOf[]
     */
    private $traitInsteadOfs = [];

    /**
     * @var TraitAlias[]
     */
    private $traitAliases = [];

    /**
     * @return string[]
     */
    public function getTraits()
    {
        return $this->traits;
    }

    /**
     * @param string $trait
     *
     * @return $this
     */
    public function addTrait($trait)
    {
        $this->traits[] = $trait;

        return $this;
    }

    /**
     * @return TraitInsteadOf[]
     */
    public function getTraitInsteadOfs()
    {
        return $this->traitInsteadOfs;
    }

    /**
     * @param TraitInsteadOf $traitInsteadOf
     *
     * @return $this
     */
    public function addTraitInsteadOf(TraitInsteadOf $traitInsteadOf)
    {
        $this->traitInsteadOfs[] = $traitInsteadOf;

        return $this;
    }

    /**
     * @return TraitAlias[]
     */
    public function getTraitAliases()
    {
        return $this->traitAliases;
    }

    /**
     * @param TraitAlias $traitAlias
     *
     * @return $this
     */
    public function addTraitAlias(TraitAlias $traitAlias)
    {
        $this->traitAliases[] = $traitAlias;

        return $this;
    }
}
