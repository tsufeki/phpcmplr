<?php

namespace PhpCmplr\Core;

trait ComponentTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var bool
     */
    private $firstRun;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->firstRun = true;
    }

    /**
     * Do the actual work.
     *
     * This method will only be called once.
     */
    protected function doRun()
    {
    }

    public function run()
    {
        if ($this->firstRun) {
            $this->firstRun = false;
            $this->doRun();
        }
    }
}
