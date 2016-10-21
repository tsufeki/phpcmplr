<?php

namespace PhpCmplr\Completer\Parser;

use PhpCmplr\Completer\NodeVisitorComponent;
use PhpParser\Node;
use PhpParser\Node\Expr;

class PositionsReconstructor extends NodeVisitorComponent
{
    /**
     * @var array of arrays [token id, value, offset]
     */
    private $tokens;

    /**
     * @var array
     */
    private $ignoredTokens = [T_WHITESPACE, T_OPEN_TAG, T_COMMENT, T_DOC_COMMENT];

    /**
     * @param Node $node
     *
     * @return int[]
     */
    private function getTokenIndexes(Node $node)
    {
        $start = $node->getAttribute('startTokenPos');
        $end = $node->getAttribute('endTokenPos');

        return range($start, $end);
    }

    /**
     * @param Node $node
     *
     * @return array
     */
    private function getUnassignedTokens(Node $node)
    {
        $indexes = $this->getTokenIndexes($node);
        foreach ($node->getSubNodeNames() as $subnodeName) {
            $subnodes = $node->$subnodeName;
            if (!is_array($subnodes)) {
                $subnodes = [$subnodes];
            }

            foreach ($subnodes as $subnode) {
                if (is_object($subnode) && $subnode instanceof Node) {
                    $indexes = array_diff($indexes, $this->getTokenIndexes($subnode));
                }
            }
        }
        sort($indexes, SORT_NUMERIC);

        $unassigned = [];
        foreach ($indexes as $i) {
            if (!in_array($this->tokens[$i]['id'], $this->ignoredTokens)) {
                $unassigned[] = $this->tokens[$i];
            }
        }

        return $unassigned;
    }

    public function beforeTraverse(array $nodes)
    {
        $parser = $this->container->get('parser');
        $this->tokens = [];
        $offset = 0;
        foreach ($parser->getTokens() as $token) {
            if (is_string($token)) {
                $this->tokens[] = ['id' => $token, 'value' => $token, 'offset' => $offset];
                $offset += strlen($token);
            } else {
                $this->tokens[] = ['id' => $token[0], 'value' => $token[1], 'offset' => $offset];
                $offset += strlen($token[1]);
            }
        }
    }

    public function enterNode(Node $node)
    {
        if (($node instanceof Expr\ClassConstFetch ||
            $node instanceof Expr\MethodCall ||
            $node instanceof Expr\PropertyFetch ||
            $node instanceof Expr\StaticCall ||
            $node instanceof Expr\StaticPropertyFetch) &&
            is_string($node->name)
        ) {
            $tokens = $this->getUnassignedTokens($node);
            if (count($tokens) >= 2 && in_array($tokens[0]['id'], [T_OBJECT_OPERATOR, T_PAAMAYIM_NEKUDOTAYIM])) {
                $node->setAttribute('nameStartFilePos', $tokens[1]['offset']);
                $node->setAttribute('nameEndFilePos', $tokens[1]['offset'] + strlen($tokens[1]['value']) - 1);
            }
        }
    }
}
