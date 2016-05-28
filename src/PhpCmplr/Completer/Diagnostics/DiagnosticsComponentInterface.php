<?php

namespace PhpCmplr\Completer\Diagnostics;

/**
 * Provides errors and warnings about the source code.
 */
interface DiagnosticsComponentInterface
{
    /**
     * @return Diagnostic[]
     */
    public function getDiagnostics();
}
