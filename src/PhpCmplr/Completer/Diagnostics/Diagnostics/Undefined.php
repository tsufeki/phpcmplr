<?php

namespace PhpCmplr\Completer\Diagnostics\Diagnostics;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Param;
use PhpParser\Node\Name;

use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\SourceFile\Range;
use PhpCmplr\Completer\SourceFile\SourceFileInterface;
use PhpCmplr\Completer\NodeVisitorComponent;
use PhpCmplr\Completer\Diagnostics\DiagnosticsNodeVisitorInterface;
use PhpCmplr\Completer\Diagnostics\Diagnostic;
use PhpCmplr\Completer\Diagnostics\FixHelper;
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

    /**
     * @var FixHelper
     */
    private $fixHelper;

    public function getDiagnostics()
    {
        return $this->diagnostics;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->file = $this->container->get('file');
        $this->reflection = $this->container->get('reflection');
        $this->namespaceReflection = $this->container->get('namespace_reflection');
        $this->fixHelper = $this->container->get('fix_helper');
        $this->container->get('name_resolver')->run();
        $this->diagnostics = [];
    }

    public function enterNode(Node $node)
    {
        $classes = [];

        if ($node instanceof Expr\Instanceof_ ||
                $node instanceof Expr\New_ ||
                $node instanceof Expr\ClassConstFetch ||
                $node instanceof Expr\StaticPropertyFetch ||
                $node instanceof Expr\StaticCall) {
            $classes[] = $node->class;

        } elseif ($node instanceof Stmt\Catch_) {
            $classes = $node->types;

        } elseif ($node instanceof Param) {
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
                            $fixes[] = $this->fixHelper->getUseFix($fqname, $range->getStart());
                        }
                    }

                    $this->diagnostics[] = new Diagnostic([$range], 'Undefined class', $fixes);
                }
            }
        }

        if ($node instanceof Expr\FuncCall && $node->name instanceof Name) {
            $names = $this->getFunctionOrConstNames($node);
            $found = false;
            foreach ($names as $name) {
                if (!empty($this->reflection->findFunction($name))) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->diagnostics[] = new Diagnostic(
                    [Range::fromNode($node->name, $this->file->getPath())],
                    'Undefined function'
                );
            }
        }

        if ($node instanceof Expr\ConstFetch && $node->name instanceof Name) {
            $names = $this->getFunctionOrConstNames($node);
            $found = false;
            foreach ($names as $name) {
                if (!empty($this->reflection->findConst($name))) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->diagnostics[] = new Diagnostic(
                    [Range::fromNode($node->name, $this->file->getPath())],
                    'Undefined const'
                );
            }
        }
    }

    /**
     * @param Expr\FuncCall|Expr\ConstFetch $node
     *
     * @return string[]
     */
    private function getFunctionOrConstNames(Node $node)
    {
        $names = [Type::nameToString($node->name)];
        if ($node->name->hasAttribute('namespacedName')) {
            $names[] = Type::nameToString($node->name->getAttribute('namespacedName'));
        }

        return $names;
    }
}
