<?php

namespace PhpCmplr\Completer\Diagnostics;

use PhpParser\Error as ParserError;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\NodeTraverserComponent;

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
        $this->path = $this->container->get('file')->getPath();
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
        return new Diagnostic($this->path, $start, $end, $error->getRawMessage());
    }

    public function getDiagnostics()
    {
        $this->run();
        return $this->diagnostics;
    }

    protected function doRun()
    {
        foreach ($this->container->get('parser')->getErrors() as $error) {
            $this->diagnostics[] = $this->makeDiagnosticFromError($error);
        }
        parent::doRun();
        foreach ($this->getVisitors() as $visitor) {
            $this->diagnostics = array_merge($this->diagnostics, $visitor->getDiagnostics());
        }
    }
}
