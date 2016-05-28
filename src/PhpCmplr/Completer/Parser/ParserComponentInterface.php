<?php

namespace PhpCmplr\Completer\Parser;

use PhpParser\Error as ParserError;
use PhpParser\Node;

/**
 * Parser-wrapping component.
 */
interface ParserComponentInterface
{
    /**
     * @return Node[]
     */
    public function getNodes();

    /**
     * @return ParserError[]
     */
    public function getErrors();
}
