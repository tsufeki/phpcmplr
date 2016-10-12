<?php

namespace PhpCmplr\Completer\Parser;

use PhpLenientParser\Error as ParserError;
use PhpLenientParser\Lexer\Emulative as Lexer;
use PhpLenientParser\Parser as RealParser;
use PhpLenientParser\ParserFactory;
use PhpLenientParser\Node;
use PhpLenientParser\Comment;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\SourceFile\OffsetLocation;
use PhpCmplr\Completer\SourceFile\Range;
use PhpCmplr\Completer\Diagnostics\DiagnosticsInterface;
use PhpCmplr\Completer\Diagnostics\Diagnostic;

class Parser extends Component implements ParserInterface, DiagnosticsInterface
{
    /**
     * @var Node[]
     */
    private $nodes;

    /**
     * @var Diagnostic[]
     */
    private $diagnostics;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->nodes = [];
        $this->diagnostics = [];
    }

    /**
     * @return RealParser
     */
    protected function createParser()
    {
        $lexer = new Lexer(['usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos']]);
        $parserOptions = ['throwOnError' => false];

        return (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer, $parserOptions);
    }

    public function getNodes()
    {
        $this->run();
        return $this->nodes;
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

    /**
     * @param ParserError $error
     * @param string      $path
     *
     * @return Diagnostic
     */
    private function makeDiagnosticFromError(ParserError $error, $path)
    {
        $attributes = $error->getAttributes();
        $start = array_key_exists('startFilePos', $attributes) ? $attributes['startFilePos'] : 0;
        $end = array_key_exists('endFilePos', $attributes) ? $attributes['endFilePos'] : $start;

        return new Diagnostic(
            [new Range(
                new OffsetLocation($path, $start),
                new OffsetLocation($path, $end)
            )],
            $error->getRawMessage()
        );
    }

    public function getDiagnostics()
    {
        $this->run();
        return $this->diagnostics;
    }

    protected function doRun()
    {
        $path = $this->container->get('file')->getPath();
        try {
            $parser = $this->createParser();
            $this->nodes = $parser->parse($this->container->get('file')->getContents());
            if ($this->nodes === null) {
                $this->nodes = [];
            }
            foreach ($parser->getErrors() as $error) {
                $this->diagnostics[] = $this->makeDiagnosticFromError($error, $path);
            }
        } catch (ParserError $error) {
            $this->diagnostics[] = $this->makeDiagnosticFromError($error, $path);
        }
    }
}
