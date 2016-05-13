<?php

namespace PhpCmplr\Completer\Parser;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeVisitor\NameResolver;

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
        if ($type instanceof DocTag\ObjectType) {
            $nameStr = $type->getClass();
            if ($nameStr !== null && $nameStr !== '') {
                if ($nameStr[0] === '\\') {
                    $name = new FullyQualified(substr($nameStr, 1));
                } else {
                    $name = new Name($nameStr);
                }
                $name = $this->resolveClassName($name);
                $nameStr = $name->getAttribute('resolved')->toString();
                $type->setClass($nameStr);
            }
        } elseif ($type instanceof DocTag\ArrayType) {
            $this->resolveDocTagType($type->getValueType());
            $this->resolveDocTagType($type->getKeyType());
        } elseif ($type instanceof DocTag\AlternativesType) {
            foreach ($type->getAlternatives() as $alternative) {
                $this->resolveDocTagType($alternative);
            }
        }
    }

    public function enterNode(Node $node) {
        if ($node->hasAttribute('annotations')) {
            foreach ($node->getAttribute('annotations') as $annotations) {
                foreach ($annotations as $docTag) {
                    if ($docTag instanceof DocTag\VarTag ||
                            $docTag instanceof DocTag\ParamTag ||
                            $docTag instanceof DocTag\ReturnTag ||
                            $docTag instanceof DocTag\ThrowsTag) {
                        $this->resolveDocTagType($docTag->getType());
                    }
                }
            }
        }
        return parent::enterNode($node);
    }
}
