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

    /**
     * Get all nodes containing given offset.
     *
     * @param int $offset
     *
     * @return Node[] Top-most node last.
     */
    public function getNodesAtOffset($offset);
}
