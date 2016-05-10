<?php

namespace PhpCmplr\Completer\Diagnostics;

use PhpCmplr\Completer\ComponentInterface;

/**
 * Provides errors and warnings about the source code.
 */
interface DiagnosticsComponentInterface extends ComponentInterface
{
    /**
     * @return Diagnostic[]
     */
    public function getDiagnostics();
}
