<?php

namespace PhpCmplr\Completer\Parser;

use PhpParser\Node;
use PhpParser\Comment;

/**
 * Parser-wrapping component.
 */
interface ParserInterface
{
    /**
     * @return Node[]
     */
    public function getNodes();

    /**
     * Get all nodes containing given offset.
     *
     * @param int  $offset
     * @param bool $leftAdjacent If true, choose left-adjacent nodes when possible.
     *
     * @return (Node|Comment)[] Top-most node last.
     */
    public function getNodesAtOffset($offset, $leftAdjacent = false);
}
