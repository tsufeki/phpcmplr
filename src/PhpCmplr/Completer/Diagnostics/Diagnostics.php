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

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->diagnostics = [];
    }

    public function getDiagnostics()
    {
        $this->run();
        return $this->diagnostics;
    }

    protected function doRun()
    {
        $visitors = $this->container->getByTag('diagnostics.visitor');
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
        parent::doRun();
        foreach ($visitors as $visitor) {
            $this->diagnostics = array_merge($this->diagnostics, $visitor->getDiagnostics());
        }
        $components = $this->container->getByTag('diagnostics');
        foreach ($components as $component) {
            $this->diagnostics = array_merge($this->diagnostics, $component->getDiagnostics());
        }
    }
}
