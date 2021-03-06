<?php

namespace PhpCmplr\Core\Diagnostics;

use PhpParser\NodeVisitor;

/**
 * Node visitor for finding errors and diagnostics.
 */
interface DiagnosticsNodeVisitorInterface extends NodeVisitor
{
    /**
     * @return Diagnostic[]
     */
    public function getDiagnostics();
}
