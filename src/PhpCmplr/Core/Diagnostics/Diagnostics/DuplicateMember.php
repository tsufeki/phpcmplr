<?php

namespace PhpCmplr\Core\Diagnostics\Diagnostics;

use PhpCmplr\Core\Diagnostics\DiagnosticsNodeVisitorInterface;
use PhpCmplr\Core\Diagnostics\Diagnostic;
use PhpCmplr\Core\NodeVisitorComponent;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\ClassMethod;
use PhpCmplr\Core\SourceFile\Range;
use PhpCmplr\Core\SourceFile\OffsetLocation;
use PhpParser\Node\Stmt\ClassConst;

class DuplicateMember extends NodeVisitorComponent implements DiagnosticsNodeVisitorInterface
{
    /**
     * @var Diagnostic[]
     */
    private $diagnostics;

    /**
     * @var SplStack of ArrayObjects
     */
    private $classStack;

    public function getDiagnostics()
    {
        return $this->diagnostics;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->diagnostics = [];
        $this->classStack = new \SplStack();
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof ClassLike) {
            $this->classStack->push([
                'methods' => new \ArrayObject(),
                'properties' => new \ArrayObject(),
                'consts' => new \ArrayObject(),
            ]);

        } elseif (!$this->classStack->isEmpty()) {
            if ($node instanceof PropertyProperty) {
                if (isset($this->classStack->top()['properties'][$node->name])) {
                    $this->diagnostics[] = new Diagnostic(
                        [$this->makeRange($node)],
                        'Redeclared property'
                    );
                } else {
                    $this->classStack->top()['properties'][$node->name] = true;
                }

            } elseif ($node instanceof ClassMethod) {
                if (isset($this->classStack->top()['methods'][$node->name])) {
                    $this->diagnostics[] = new Diagnostic(
                        [$this->makeRange($node)],
                        'Redeclared method'
                    );
                } else {
                    $this->classStack->top()['methods'][$node->name] = true;
                }

            } elseif ($node instanceof ClassConst) {
                foreach ($node->consts as $const) {
                    if (isset($this->classStack->top()['consts'][$const->name])) {
                        $this->diagnostics[] = new Diagnostic(
                            [$this->makeRange($const)],
                            'Redeclared class const'
                        );
                    } else {
                        $this->classStack->top()['consts'][$const->name] = true;
                    }
                }
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassLike) {
            $this->classStack->pop();
        }
    }

    /**
     * @param Node $node
     *
     * @return Range
     */
    private function makeRange(Node $node)
    {
        $path = $this->container->get('file')->getPath();
        if ($node->hasAttribute('nameStartFilePos') && $node->hasAttribute('nameEndFilePos')) {
            return new Range(
                new OffsetLocation($path, $node->getAttribute('nameStartFilePos')),
                new OffsetLocation($path, $node->getAttribute('nameEndFilePos'))
            );
        }

        return Range::fromNode($node, $path);
    }
}
