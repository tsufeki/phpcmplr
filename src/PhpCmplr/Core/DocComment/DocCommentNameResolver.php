<?php

namespace PhpCmplr\Core\DocComment;

use PhpParser\Node;
use PhpParser\Node\Name;

use PhpCmplr\Core\Component;
use PhpCmplr\Core\NameResolver\NameResolverComponentInterface;
use PhpCmplr\Core\NameResolver\NameResolverInterface;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\Type\ObjectType;
use PhpCmplr\Core\DocComment\Tag\TypedTag;

class DocCommentNameResolver extends Component implements NameResolverComponentInterface
{
    protected function resolveDocTagType(Type $type, NameResolverInterface $nameResolver)
    {
        return $type->walk(function (Type $type) use ($nameResolver) {
            if ($type instanceof ObjectType) {
                $unresolved = $nameStr = $type->getClass();
                if ($nameStr !== null && $nameStr !== '') {
                    if ($nameStr[0] === '\\') {
                        $name = new Name\FullyQualified(substr($nameStr, 1));
                    } else {
                        $name = new Name($nameStr);
                    }
                    $nameResolver->resolveClassName($name);
                    $name = $name->getAttribute('resolved');
                    $nameStr = $name->toString();
                    if ($name instanceof Name\FullyQualified) {
                        $nameStr = '\\' . $nameStr;
                    }
                    return Type::object_($nameStr, $unresolved);
                }
            }

            return $type;
        });
    }

    public function enterNode(Node $node, NameResolverInterface $nameResolver)
    {
        if ($node->hasAttribute('annotations')) {
            foreach ($node->getAttribute('annotations') as $annotations) {
                foreach ($annotations as $docTag) {
                    if ($docTag instanceof TypedTag) {
                        $docTag->setType($this->resolveDocTagType($docTag->getType(), $nameResolver));
                    }
                }
            }
        }
    }

    protected function doRun()
    {
        $this->container->get('doc_comment')->run();
    }
}
