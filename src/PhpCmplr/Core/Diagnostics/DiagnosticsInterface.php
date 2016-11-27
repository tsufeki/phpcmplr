<?php

namespace PhpCmplr\Core\Diagnostics;

/**
 * Provides errors and warnings about the source code.
 */
interface DiagnosticsInterface
{
    /**
     * @return Diagnostic[]
     */
    public function getDiagnostics();
}
