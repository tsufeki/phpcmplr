<?php

namespace PhpCmplr\Completer\Diagnostics\Diagnostics;

use PhpLenientParser\Node;
use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Param;
use PhpLenientParser\Node\Name;

use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\SourceFile\Location;
use PhpCmplr\Completer\SourceFile\OffsetLocation;
use PhpCmplr\Completer\SourceFile\LineAndColumnLocation;
use PhpCmplr\Completer\SourceFile\Range;
use PhpCmplr\Completer\SourceFile\SourceFileInterface;
use PhpCmplr\Completer\NodeVisitorComponent;
use PhpCmplr\Completer\Diagnostics\DiagnosticsNodeVisitorInterface;
use PhpCmplr\Completer\Diagnostics\Diagnostic;
use PhpCmplr\Completer\Diagnostics\Fix;
use PhpCmplr\Completer\Diagnostics\FixChunk;
use PhpCmplr\Completer\Diagnostics\FixHelper;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\NamespaceReflection;
use PhpCmplr\Completer\Parser\Parser;

class Undefined extends NodeVisitorComponent implements DiagnosticsNodeVisitorInterface
{
    /**
     * @var Diagnostic[]
     */
    private $diagnostics;

    /**
     * @var SourceFileInterface
     */
    private $file;

    /**
     * @var Reflection
     */
    private $reflection;

    /**
     * @var NamespaceReflection
     */
    private $namespaceReflection;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var FixHelper
     */
    private $fixHelper;

    /**
     * @var Node[]
     */
    private $nodePathFromTop;

    /**
     * @var Range
     */
    private $insertRange;

    /**
     * @var string
     */
    private $insertText;

    public function getDiagnostics()
    {
        return $this->diagnostics;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->file = $this->container->get('file');
        $this->reflection = $this->container->get('reflection');
        $this->namespaceReflection = $this->container->get('namespace_reflection');
        $this->parser = $this->container->get('parser');
        $this->fixHelper = $this->container->get('fix_helper');
        $this->container->get('name_resolver')->run();
        $this->diagnostics = [];
        $this->nodePathFromTop = [];
        $this->insertLocation = null;
    }

    public function enterNode(Node $node)
    {
        array_push($this->nodePathFromTop, $node);
        $classes = [];

        if ($node instanceof Expr\Instanceof_ ||
                $node instanceof Expr\New_ ||
                $node instanceof Expr\ClassConstFetch ||
                $node instanceof Expr\StaticPropertyFetch ||
                $node instanceof Expr\StaticCall) {
            $classes[] = $node->class;

        } elseif ($node instanceof Stmt\Catch_ || $node instanceof Param) {
            $classes[] = $node->type;

        } elseif ($node instanceof Stmt\Function_ || $node instanceof Stmt\ClassMethod ||
                $node instanceof Expr\Closure) {
            $classes[] = $node->returnType;

        } elseif ($node instanceof Stmt\Class_) {
            $classes[] = $node->extends;
            $classes = array_merge($classes, $node->implements);

        } elseif ($node instanceof Stmt\Interface_) {
            $classes = array_merge($classes, $node->extends);

        } elseif ($node instanceof Stmt\TraitUse) {
            $classes = array_merge($classes, $node->traits);

        } elseif ($node instanceof Stmt\TraitUseAdaptation) {
            $classes[] = $node->trait;
        }

        foreach ($classes as $class) {
            if (is_object($class) && $class instanceof Name) {
                $name = Type::nameToString($class);
                if (!in_array(strtolower($name), ['self', 'parent', 'static']) &&
                        empty($this->reflection->findClass($name))) {
                    $range = Range::fromNode($class, $this->file->getPath());
                    $fixes = [];
                    if ($this->namespaceReflection !== null && $class->isUnqualified()) {
                        foreach ($this->namespaceReflection->findFullyQualifiedClasses($class->toString()) as $fqname) {
                            //$fixes[] = new Fix([new FixChunk($range, $fqname)], $fqname);
                            $fixes[] = $this->getFix($fqname);
                        }
                    }

                    $this->diagnostics[] = new Diagnostic([$range], 'Undefined class', $fixes);
                }
            }
        }
    }

    public function leaveNode(Node $node)
    {
        array_pop($this->nodePathFromTop);
    }

    /**
     * @param string $fqname
     *
     * @return Fix
     */
    private function getFix($fqname)
    {
        if ($this->insertRange === null) {
            $namespace = null;
            foreach (array_reverse($this->nodePathFromTop) as $ancestor) {
                if ($ancestor instanceof Stmt\Namespace_) {
                    $namespace = $ancestor;
                    break;
                }
            }

            $lastUse = null;
            $stmts = $namespace === null ? $this->parser->getNodes() : $namespace->stmts;
            foreach ($stmts as $stmt) {
                if ($stmt instanceof Stmt\Use_ || $stmt instanceof Stmt\GroupUse) {
                    $lastUse = $stmt;
                }
            }

            if ($lastUse !== null) {
                /** @var Range */
                $range = Range::fromNode($lastUse, $this->file->getPath());
                $startLine = $range->getStart()->getLineAndColumn($this->file)[0];
                $insertLocation = OffsetLocation::move($this->file, $range->getEnd());
                $indent = $this->fixHelper->getIndentOfLines([$this->file->getLine($startLine)]);
                $this->insertText = "\n" . $this->fixHelper->makeIndent($indent) . "use %s;";
            } else {
                $insertLocation = LineAndColumnLocation::moveToStartOfLine(
                    $this->file,
                    Range::fromNode($stmts[0], $this->file->getPath())->getStart()
                );
                $this->insertText = "use %s;\n\n";
            }

            $this->insertRange = new Range(
                $insertLocation,
                OffsetLocation::move($this->file, $insertLocation, -1)
            );
        }

        $fqname = ltrim($fqname, '\\');
        return new Fix(
            [new FixChunk(
                $this->insertRange,
                sprintf($this->insertText, $fqname)
            )],
            'use ' . $fqname
        );
    }
}
