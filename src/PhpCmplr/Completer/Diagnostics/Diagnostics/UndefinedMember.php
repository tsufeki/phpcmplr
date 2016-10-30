<?php

namespace PhpCmplr\Completer\Diagnostics\Diagnostics;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Param;
use PhpParser\Node\Name;

use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\SourceFile\OffsetLocation;
use PhpCmplr\Completer\SourceFile\Range;
use PhpCmplr\Completer\SourceFile\SourceFileInterface;
use PhpCmplr\Completer\NodeVisitorComponent;
use PhpCmplr\Completer\Diagnostics\DiagnosticsNodeVisitorInterface;
use PhpCmplr\Completer\Diagnostics\Diagnostic;
use PhpCmplr\Completer\Reflection\Reflection;

class UndefinedMember extends NodeVisitorComponent implements DiagnosticsNodeVisitorInterface
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
     * @var string|null
     */
    private $ctxClass;

    public function getDiagnostics()
    {
        return $this->diagnostics;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->file = $this->container->get('file');
        $this->reflection = $this->container->get('reflection');
        $this->container->get('name_resolver')->run();
        $this->container->get('typeinfer')->run();
        $this->diagnostics = [];
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Stmt\ClassLike && $node->name !== null) {
            $this->ctxClass = $node->hasAttribute('namespacedName')
                ? Type::nameToString($node->getAttribute('namespacedName'))
                : $node->name;
        }

        $reflections = $node->hasAttribute('reflections') ? $node->getAttribute('reflections') : [];
        if ($node instanceof Expr\MethodCall ||
            $node instanceof Expr\StaticCall ||
            $node instanceof Expr\PropertyFetch ||
            $node instanceof Expr\StaticPropertyFetch ||
            $node instanceof Expr\ClassConstFetch
        ) {
            // TODO: PHP 7.1 class const accesibility
            if (!($node instanceof Expr\ClassConstFetch)) {
                $reflections = $this->reflection->filterAvailableMembers($reflections, $this->ctxClass);
            }

            // TODO: stdClass objects can have arbitrary properties.
            if (empty($reflections)) {
                $this->diagnostics[] = new Diagnostic(
                    [$this->makeRange($node)],
                    $this->makeMessage($node)
                );
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Stmt\ClassLike && $node->name !== null) {
            $this->ctxClass = null;
        }
    }

    /**
     * @param Node $node
     *
     * @return Range
     */
    private function makeRange(Node $node)
    {
        $path = $this->file->getPath();
        if ($node->hasAttribute('nameStartFilePos') && $node->hasAttribute('nameEndFilePos')) {
            return new Range(
                new OffsetLocation($path, $node->getAttribute('nameStartFilePos')),
                new OffsetLocation($path, $node->getAttribute('nameEndFilePos'))
            );
        }

        return Range::fromNode($node, $path);
    }

    private function makeMessage(Node $node)
    {
        $msg = 'Undefined ';
        if ($node instanceof Expr\MethodCall) $msg .= 'method';
        if ($node instanceof Expr\StaticCall) $msg .= 'method';
        if ($node instanceof Expr\PropertyFetch) $msg .= 'property';
        if ($node instanceof Expr\StaticPropertyFetch) $msg .= 'property';
        if ($node instanceof Expr\ClassConstFetch) $msg .= 'class const';

        return $msg;
    }
}
