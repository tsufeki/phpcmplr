<?php

namespace PhpCmplr\Util;

class StopWatch
{
    /**
     * @var float
     */
    private $start = -INF;

    /**
     * @param bool $startImmediately
     */
    public function __construct($startImmediately = true)
    {
        if ($startImmediately) {
            $this->start();
        }
    }

    public function start()
    {
        $this->start = microtime(true);
    }

    /**
     * @return float Seconds between start and now or INF if not started properly.
     */
    public function get()
    {
        return microtime(true) - $this->start;
    }

    /**
     * @return string
     */
    public function getString()
    {
        $seconds = $this->get();
        return is_finite($seconds) ? sprintf('%.2fs', $seconds) : '-';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getString();
    }
}
