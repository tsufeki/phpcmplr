<?php

namespace PhpCmplr\Completer\Parser;

use PhpParser\Error as ParserError;
use PhpParser\Node;

use PhpCmplr\Completer\ComponentInterface;

/**
 * Parser-wrapping component.
 */
interface ParserComponentInterface extends ComponentInterface
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
