<?php

namespace PhpCmplr\Completer\Parser;

use PhpParser\Error as ParserError;
use PhpParser\Lexer\Emulative as Lexer;
use PhpParser\ParserFactory;
use PhpParser\Parser;
use PhpParser\Node;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Component;

class ParserComponent extends Component implements ParserComponentInterface
{
    /**
     * @var Parser
     */
    private $parser;

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
        $this->parser = (new ParserFactory())->create(
            ParserFactory::PREFER_PHP7,
            new Lexer(['usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos']]),
            ['throwOnError' => false]
        );
        $this->nodes = [];
        $this->errors = [];
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
     * @param Node[]           $result
     *
     * @return bool True iff anything in $nodes include offset.
     */
    private function getNodeAtOffsetRecursive($nodes, $offset, array &$result)
    {
        if ($nodes instanceof Node) {
            if ($nodes->getAttribute('startFilePos') <= $offset &&
                    $nodes->getAttribute('endFilePos') >= $offset) {
                $result[] = $nodes;
                foreach ($nodes->getSubNodeNames() as $subnode) {
                    if ($this->getNodeAtOffsetRecursive($nodes->$subnode, $offset, $result)) {
                        break;
                    }
                }
                return true;
            } else {
                return false;
            }
        } elseif (is_array($nodes)) {
            foreach ($nodes as $node) {
                if ($this->getNodeAtOffsetRecursive($node, $offset, $result)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getNodesAtOffset($offset)
    {
        $result = [];
        $this->getNodeAtOffsetRecursive($this->getNodes(), $offset, $result);
        return array_reverse($result);
    }

    protected function doRun()
    {
        try {
            $this->nodes = $this->parser->parse($this->container->get('file')->getContents());
            foreach ($this->parser->getErrors() as $error) {
                $this->errors[] = $error;
            }
        } catch (ParserError $error) {
            $this->errors[] = $error;
        }
    }
}
