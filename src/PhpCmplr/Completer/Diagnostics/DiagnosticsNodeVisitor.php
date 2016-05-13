<?php

namespace PhpCmplr\Completer\Diagnostics;

use PhpParser\NodeVisitor;

/**
 * Node visitor for finding errors and diagnostics.
 */
interface DiagnosticsNodeVisitor extends NodeVisitor
{
    /**
     * @return Diagnostic[]
     */
    public function getDiagnostics();
}