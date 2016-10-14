<?php

namespace PhpCmplr\Completer\Diagnostics;

class Fix
{
    /**
     * @var FixChunk[]
     */
    private $chunks;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @param FixChunk[]  $chunks
     * @param string|null $description
     */
    public function __construct(array $chunks, $description = null)
    {
        $this->chunks = $chunks;
        $this->description = $description;
    }

    /**
     * @return FixChunk[]
     */
    public function getChunks()
    {
        return $this->chunks;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }
}
