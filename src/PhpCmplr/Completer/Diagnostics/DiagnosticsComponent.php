<?php

namespace PhpCmplr\Completer\Diagnostics;

use PhpLenientParser\Error as ParserError;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\NodeTraverserComponent;
use PhpCmplr\Completer\OffsetLocation;

class DiagnosticsComponent extends NodeTraverserComponent implements DiagnosticsComponentInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var Diagnostic[]
     */
    private $diagnostics;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->diagnostics = [];
    }

    /**
     * @param ParserError $error
     *
     * @return Diagnostic
     */
    protected function makeDiagnosticFromError(ParserError $error)
    {
        $attributes = $error->getAttributes();
        $start = array_key_exists('startFilePos', $attributes) ? $attributes['startFilePos'] : 0;
        $end = array_key_exists('endFilePos', $attributes) ? $attributes['endFilePos'] : $start;

        return new Diagnostic(
            new OffsetLocation($this->path, $start),
            new OffsetLocation($this->path, $end),
            $error->getRawMessage());
    }

    public function getDiagnostics()
    {
        $this->run();
        return $this->diagnostics;
    }

    protected function doRun()
    {
        $this->path = $this->container->get('file')->getPath();
        foreach ($this->container->get('parser')->getErrors() as $error) {
            $this->diagnostics[] = $this->makeDiagnosticFromError($error);
        }
        $visitors = $this->container->getByTag('diagnostics.visitor');
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
        parent::doRun();
        foreach ($visitors as $visitor) {
            $this->diagnostics = array_merge($this->diagnostics, $visitor->getDiagnostics());
        }
        $components = $this->container->getByTag('diagnostics.component');
        foreach ($components as $component) {
            $this->diagnostics = array_merge($this->diagnostics, $component->getDiagnostics());
        }
    }
}
