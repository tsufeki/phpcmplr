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
use PhpCmplr\Completer\DocComment\Tag\TypedTag;
use PhpCmplr\Completer\Type\ObjectType;
use PhpCmplr\Completer\SourceFile\OffsetLocation;

class UndefinedDocCommentType extends NodeVisitorComponent implements DiagnosticsNodeVisitorInterface
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
        $this->container->get('doc_comment')->run();
        $this->container->get('name_resolver')->run();
        $this->diagnostics = [];
    }

    public function enterNode(Node $node)
    {
        $classes = [];

        $annotations = $node->getAttribute('annotations');
        if ($annotations !== null) {
            foreach ($annotations as $annotationList) {
                foreach ($annotationList as $tag) {
                    if ($tag instanceof TypedTag) {
                        /** @var TypedTag $tag */
                        $tag->getType()->walk(function (Type $type) use (&$classes, $tag) {
                            if ($type instanceof ObjectType && ($name = $type->getClass())) {
                                $classes[] = [$name, $tag];
                            }
                        });
                    }
                }
            }
        }

        /** @var TypedTag $tag */
        foreach ($classes as list($name, $tag)) {
            if (!in_array(strtolower($name), ['self', 'parent', 'static']) &&
                    empty($this->reflection->findClass($name))) {
                $range = new Range(
                    new OffsetLocation($this->file->getPath(), $tag->getTypeStartPos()),
                    new OffsetLocation($this->file->getPath(), $tag->getTypeEndPos())
                );
                $fixes = [];
                if ($this->namespaceReflection !== null && strpos($name, '\\') === false) {
                    foreach ($this->namespaceReflection->findFullyQualifiedClasses($name) as $fqname) {
                        $fixes[] = $this->fixHelper->getUseFix($fqname, $range->getStart());
                    }
                }

                $this->diagnostics[] = new Diagnostic([$range], 'Undefined class', $fixes);
            }
        }
    }
}
