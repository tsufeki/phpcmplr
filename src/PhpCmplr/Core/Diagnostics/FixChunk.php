<?php

namespace PhpCmplr\Core\Diagnostics;

use PhpCmplr\Core\SourceFile\Range;

class FixChunk
{
    /**
     * @var Range
     */
    private $range;

    /**
     * @var string
     */
    private $replacement;

    /**
     * @param Range  $range
     * @param string $replacement
     */
    public function __construct(Range $range, $replacement)
    {
        $this->range = $range;
        $this->replacement = $replacement;
    }

    /**
     * @return Range
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * @return string
     */
    public function getReplacement()
    {
        return $this->replacement;
    }
}
