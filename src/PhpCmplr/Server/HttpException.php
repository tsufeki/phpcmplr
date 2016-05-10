<?php

namespace PhpCmplr\Server;

class HttpException extends \Exception
{
    /**
     * @var int
     */
    private $status;

    /**
     * @param int $status
     */
    public function __construct($status)
    {
        parent::__construct('HTTP ' . $status);
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
