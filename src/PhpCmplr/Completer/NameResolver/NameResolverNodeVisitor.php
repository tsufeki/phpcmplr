<?php

namespace PhpCmplr\Completer\NameResolver;

use PhpLenientParser\Node;
use PhpLenientParser\Node\Name;
use PhpLenientParser\Node\Name\FullyQualified;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\NodeVisitor\NameResolver as PhpParserNameResolver;

use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Type\ObjectType;
use PhpCmplr\Completer\DocComment\Tag\TypedTag;

class NameResolverNodeVisitor extends PhpParserNameResolver
{
    /**
     * @var Name
     */
    private $currentClass;

    /**
     * @var Name
     */
    private $parentClass;

    public function beforeTraverse(array $nodes) {
        parent::beforeTraverse($nodes);
        $this->currentClass = null;
        $this->parentClass = null;
    }

    protected function resolveClassName(Name $name)
    {
        $resolved = parent::resolveClassName($name);

        if ($this->currentClass !== null && in_array(strtolower($name->toString()), array('self', 'static'))) {
            $resolved = $this->currentClass;
        } elseif ($this->parentClass !== null && strtolower($name->toString()) === 'parent') {
            $resolved = $this->parentClass;
        }

        $name->setAttribute('resolved', $resolved);

        return $name;
    }

    protected function resolveOtherName(Name $name, $type)
    {
        $resolved = parent::resolveOtherName($name, $type);
        if ($resolved->isUnqualified()) {
            // Assume it's global.
            $resolved = new FullyQualified($name->parts, $name->getAttributes());
        }
        $name->setAttribute('resolved', $resolved);

        return $name;
    }

    protected function addNamespacedName(Node $node)
    {
        if (null !== $this->namespace) {
            $namespacedName = Name::concat($this->namespace, $node->name);
        } else {
            $namespacedName = new Name($node->name);
        }
        $node->setAttribute('namespacedName', new FullyQualified($namespacedName->parts));
    }

    protected function resolveDocTagType(Type $type)
    {
        return $type->walk(function (Type $type) {
            if ($type instanceof ObjectType) {
                $nameStr = $type->getClass();
                if ($nameStr !== null && $nameStr !== '') {
                    if ($nameStr[0] === '\\') {
                        $name = new FullyQualified(substr($nameStr, 1));
                    } else {
                        $name = new Name($nameStr);
                    }
                    $name = $this->resolveClassName($name)->getAttribute('resolved');
                    $nameStr = $name->toString();
                    if ($name instanceof FullyQualified) {
                        $nameStr = '\\' . $nameStr;
                    }
                    return Type::object_($nameStr);
                }
            }

            return $type;
        });
    }

    public function enterNode(Node $node)
    {
        if ($node->hasAttribute('annotations')) {
            foreach ($node->getAttribute('annotations') as $annotations) {
                foreach ($annotations as $docTag) {
                    if ($docTag instanceof TypedTag) {
                        $docTag->setType($this->resolveDocTagType($docTag->getType()));
                    }
                }
            }
        }

        $result = parent::enterNode($node);

        if ($node instanceof Stmt\Class_) {
            $this->currentClass = $node->getAttribute('namespacedName');
            $this->parentClass = null;
            if ($node->extends !== null) {
                $this->parentClass = $node->extends->getAttribute('resolved');
            }
        } elseif ($node instanceof Stmt\Interface_) {
            $this->currentClass = $node->getAttribute('namespacedName');
            $this->parentClass = null;
        }

        return $result;
    }

    public function leaveNode(Node $node)
    {
        $result = parent::leaveNode($node);

        if ($node instanceof Stmt\Class_) {
            $this->currentClass = null;
            $this->parentClass = null;
        }

        return $result;
    }
}
