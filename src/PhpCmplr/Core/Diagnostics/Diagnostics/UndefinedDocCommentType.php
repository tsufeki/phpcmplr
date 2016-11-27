<?php

namespace PhpCmplr\Core\Diagnostics\Diagnostics;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Param;
use PhpParser\Node\Name;

use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\SourceFile\Range;
use PhpCmplr\Core\SourceFile\SourceFileInterface;
use PhpCmplr\Core\NodeVisitorComponent;
use PhpCmplr\Core\Diagnostics\DiagnosticsNodeVisitorInterface;
use PhpCmplr\Core\Diagnostics\Diagnostic;
use PhpCmplr\Core\Diagnostics\FixHelper;
use PhpCmplr\Core\Reflection\Reflection;
use PhpCmplr\Core\Reflection\NamespaceReflection;
use PhpCmplr\Core\DocComment\Tag\TypedTag;
use PhpCmplr\Core\Type\ObjectType;
use PhpCmplr\Core\SourceFile\OffsetLocation;

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
                                $classes[] = [$name, $type->getUnresolvedClass(), $tag];
                            }

                            return $type;
                        });
                    }
                }
            }
        }

        /** @var TypedTag $tag */
        foreach ($classes as list($name, $unresolved, $tag)) {
            if (!in_array(strtolower($name), ['self', 'parent', 'static']) &&
                    empty($this->reflection->findClass($name))) {
                $range = new Range(
                    new OffsetLocation($this->file->getPath(), $tag->getTypeStartPos()),
                    new OffsetLocation($this->file->getPath(), $tag->getTypeEndPos())
                );
                $fixes = [];
                if ($this->namespaceReflection !== null && strpos($unresolved, '\\') === false) {
                    foreach ($this->namespaceReflection->findFullyQualifiedClasses($unresolved) as $fqname) {
                        $fixes[] = $this->fixHelper->getUseFix($fqname, $range->getStart());
                    }
                }

                $this->diagnostics[] = new Diagnostic([$range], 'Undefined class', $fixes);
            }
        }
    }
}
