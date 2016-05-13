<?php

namespace PhpCmplr\Completer;

/**
 * Abstract component.
 */
abstract class Component implements ComponentInterface
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
    abstract protected function doRun();

    public function run()
    {
        if ($this->firstRun) {
            $this->firstRun = false;
            $this->doRun();
        }
    }
}
