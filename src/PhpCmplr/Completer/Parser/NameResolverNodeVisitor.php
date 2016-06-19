<?php

namespace PhpCmplr\Completer\Parser;

use PhpLenientParser\Node;
use PhpLenientParser\Node\Name;
use PhpLenientParser\Node\Name\FullyQualified;
use PhpLenientParser\NodeVisitor\NameResolver;

use PhpCmplr\Completer\Parser\DocTag;

class NameResolverNodeVisitor extends NameResolver
{
    protected function resolveClassName(Name $name)
    {
        $resolved = parent::resolveClassName($name);
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

    protected function resolveDocTagType(DocTag\Type $type)
    {
        return $type->walk(function (DocTag\Type $type) {
            if ($type instanceof DocTag\ObjectType) {
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
                    return DocTag\Type::object_($nameStr);
                }
            }

            return $type;
        });
    }

    public function enterNode(Node $node) {
        if ($node->hasAttribute('annotations')) {
            foreach ($node->getAttribute('annotations') as $annotations) {
                foreach ($annotations as $docTag) {
                    if ($docTag instanceof DocTag\TypedTag) {
                        $docTag->setType($this->resolveDocTagType($docTag->getType()));
                    }
                }
            }
        }
        return parent::enterNode($node);
    }
}
