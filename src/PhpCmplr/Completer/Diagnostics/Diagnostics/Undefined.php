<?php

namespace PhpCmplr\Completer\Diagnostics\Diagnostics;

use PhpLenientParser\Node;
use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Param;
use PhpLenientParser\Node\Name;

use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\SourceFile\Range;
use PhpCmplr\Completer\SourceFile\SourceFileInterface;
use PhpCmplr\Completer\NodeVisitorComponent;
use PhpCmplr\Completer\Diagnostics\DiagnosticsNodeVisitorInterface;
use PhpCmplr\Completer\Diagnostics\Diagnostic;
use PhpCmplr\Completer\Diagnostics\Fix;
use PhpCmplr\Completer\Diagnostics\FixChunk;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\NamespaceReflection;

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

    public function getDiagnostics()
    {
        return $this->diagnostics;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->file = $this->container->get('file');
        $this->reflection = $this->container->get('reflection');
        $this->namespaceReflection = $this->container->get('namespace_reflection');
        $this->container->get('name_resolver')->run();
        $this->diagnostics = [];
    }

    public function enterNode(Node $node)
    {
        $classes = [];

        if ($node instanceof Expr\Instanceof_ || $node instanceof Expr\New_) {
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
                            // TODO
                            $fixes[] = new Fix([new FixChunk($range, $fqname)], $fqname);
                        }
                    }

                    $this->diagnostics[] = new Diagnostic([$range], 'Undefined class', $fixes);
                }
            }
        }
    }
}
