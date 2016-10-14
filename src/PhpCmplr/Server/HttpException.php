<?php

namespace PhpCmplr\Server;

class HttpException extends \Exception
{
    /**
     * @var int
     */
    private $status;

    /**
     * @param int               $status
     * @param \Exception|\Error $exception Exception which caused this HttpException.
     */
    public function __construct($status, $exception = null)
    {
        parent::__construct('HTTP ' . $status, 0, $exception);
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
