<?php

namespace PhpCmplr\Completer\Diagnostics;

class Diagnostic
{
    /**
     * @var string
     */
    private $file;

    /**
     * File offset.
     *
     * @var int
     */
    private $start;

    /**
     * File offset.
     *
     * @var int
     */
    private $end;

    /**
     * @var string
     */
    private $description;

    /**
     * @param string $file
     * @param int    $start
     * @param int    $end
     * @param string $description
     */
    public function __construct($file, $start, $end, $description)
    {
        $this->file = $file;
        $this->start = $start;
        $this->end = $end;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
