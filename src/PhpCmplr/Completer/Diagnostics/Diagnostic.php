<?php

namespace PhpCmplr\Completer\Diagnostics;

use PhpCmplr\Completer\SourceFile\Range;

class Diagnostic
{
    /**
     * @var Range[]
     */
    private $ranges;

    /**
     * @var string
     */
    private $description;

    /**
     * @var Fix[]
     */
    private $fixes;

    /**
     * @param Range[] $ranges
     * @param string  $description
     * @param Fix[]   $fixes
     */
    public function __construct(array $ranges, $description, array $fixes = [])
    {
        $this->ranges = $ranges;
        $this->description = $description;
        $this->fixes = $fixes;
    }

    /**
     * @return Range[]
     */
    public function getRanges()
    {
        return $this->ranges;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return Fix[]
     */
    public function getFixes()
    {
        return $this->fixes;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->getRanges()[0]->getStart()->getPath();
    }
}
