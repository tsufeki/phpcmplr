<?php

namespace PhpCmplr\Completer\Parser;

use PhpParser\Error as ParserError;
use PhpParser\Lexer\Emulative as Lexer;
use PhpParser\Parser;
use PhpParser\Parser\Multiple;
use PhpParser\Node;
use PhpParser\Comment;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Parser\Parser\Php5Lenient;
use PhpCmplr\Completer\Parser\Parser\Php7Lenient;

class ParserComponent extends Component implements ParserComponentInterface
{
    /**
     * @var Node[]
     */
    private $nodes;

    /**
     * @var ParserError[]
     */
    private $errors;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->nodes = [];
        $this->errors = [];
    }

    /**
     * @return Parser
     */
    protected function createParser()
    {
        $lexer = new Lexer(['usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos']]);
        $parserOptions = ['throwOnError' => false];

        return new Multiple([
            new Php7Lenient($lexer, $parserOptions),
            new Php5Lenient($lexer, $parserOptions)
        ]);
    }

    public function getNodes()
    {
        $this->run();
        return $this->nodes;
    }

    public function getErrors()
    {
        $this->run();
        return $this->errors;
    }

    /**
     * @param Node|array|mixed $nodes
     * @param int              $offset
     * @param (Comment|Node)[] $result
     * @param bool             $rightAdjustment
     *
     * @return bool True iff anything in $nodes include offset.
     */
    private function getNodeAtOffsetRecursive($nodes, $offset, array &$result, $rightAdjustment)
    {
        if ($nodes instanceof Node) {

            $comments = $nodes->getAttribute('comments', []);
            foreach ($comments as $comment) {
                if ($comment instanceof Comment\Doc) {
                    $start = $comment->getFilePos();
                    $end = $start + strlen($comment->getText());
                    if ($start <= $offset && $end + $rightAdjustment > $offset) {
                        $result[] = $nodes;
                        $result[] = $comment;
                        return true;
                    }
                }
            }

            // Namespace node needs special handling as it can be a no-braces namespace
            // where offsets include only declaration and not the logically contained statements.
            $isNamespace = $nodes instanceof Node\Stmt\Namespace_;

            $inRange = $nodes->getAttribute('startFilePos') <= $offset &&
                $nodes->getAttribute('endFilePos') + $rightAdjustment >= $offset;

            if ($isNamespace || $inRange) {
                $result[] = $nodes;
                foreach ($nodes->getSubNodeNames() as $subnode) {
                    if ($this->getNodeAtOffsetRecursive($nodes->$subnode, $offset, $result, $rightAdjustment)) {
                        return true;
                    }
                }
                if ($inRange) {
                    return true;
                }
            }
        } elseif (is_array($nodes)) {
            foreach ($nodes as $node) {
                if ($this->getNodeAtOffsetRecursive($node, $offset, $result, $rightAdjustment)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getNodesAtOffset($offset, $leftAdjacent = false)
    {
        $this->run();
        $result = [];
        $rightAdjustment = $leftAdjacent ? 1 : 0;
        $this->getNodeAtOffsetRecursive($this->getNodes(), $offset, $result, $rightAdjustment);
        return array_reverse($result);
    }

    protected function doRun()
    {
        try {
            $parser = $this->createParser();
            $this->nodes = $parser->parse($this->container->get('file')->getContents());
            if ($this->nodes === null) {
                $this->nodes = [];
            }
            foreach ($parser->getErrors() as $error) {
                $this->errors[] = $error;
            }
        } catch (ParserError $error) {
            $this->errors[] = $error;
        }
    }
}
