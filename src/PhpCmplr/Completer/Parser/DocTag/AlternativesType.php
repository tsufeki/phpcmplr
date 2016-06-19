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

    public function toString($short = false)
    {
        return implode('|', array_map(
            function ($a) use ($short) { return $a->toString($short); },
            $this->getAlternatives()));
    }

    public function compare(Type $other)
    {
        $cmp = parent::compare($other);
        if ($cmp !== 0) {
            return $cmp;
        }
        for ($i = 0;; $i++) {
            if (count($this->alternatives) <= $i && count($other->alternatives) <= $i) {
                return 0;
            }
            if (count($this->alternatives) <= $i) {
                return -1;
            }
            if (count($other->alternatives) <= $i) {
                return 1;
            }
            $cmp = $this->alternatives[$i]->compare($other->alternatives[$i]);
            if ($cmp !== 0) {
                return $cmp;
            }
        }
    }
}
