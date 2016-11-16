<?php

namespace PhpCmplr\Completer\Diagnostics;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\NodeTraverserComponent;

class Diagnostics extends NodeTraverserComponent
{
    /**
     * @var Diagnostic[]
     */
    private $diagnostics;

    /**
     * @var int
     */
    private $maxCount;

    /**
     * @param Container $container
     * @param int       $maxCount
     */
    public function __construct(Container $container, $maxCount)
    {
        parent::__construct($container);
        $this->diagnostics = [];
        $this->maxCount = $maxCount;
    }

    /**
     * @return Diagnostic[]
     */
    public function getDiagnostics()
    {
        $this->run();
        return $this->diagnostics;
    }

    protected function doRun()
    {
        /** @var DiagnosticsNodeVisitorInterface[] */
        $visitors = $this->container->getByTag('diagnostics.visitor');
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
        parent::doRun();
        foreach ($visitors as $visitor) {
            $this->diagnostics = array_merge($this->diagnostics, $visitor->getDiagnostics());
            if (count($this->diagnostics) >= $this->maxCount) {
                $this->diagnostics = array_slice($this->diagnostics, 0, $this->maxCount);
                return;
            }
        }
        /** @var DiagnosticsInterface[] */
        $components = $this->container->getByTag('diagnostics');
        foreach ($components as $component) {
            $this->diagnostics = array_merge($this->diagnostics, $component->getDiagnostics());
            if (count($this->diagnostics) >= $this->maxCount) {
                $this->diagnostics = array_slice($this->diagnostics, 0, $this->maxCount);
                return;
            }
        }
    }
}
