<?php

namespace PhpCmplr\Completer\DocComment;

use PhpParser\Node;
use PhpParser\Node\Name;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\NameResolver\NameResolverComponentInterface;
use PhpCmplr\Completer\NameResolver\NameResolverInterface;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Type\ObjectType;
use PhpCmplr\Completer\DocComment\Tag\TypedTag;

class DocCommentNameResolver extends Component implements NameResolverComponentInterface
{
    protected function resolveDocTagType(Type $type, NameResolverInterface $nameResolver)
    {
        return $type->walk(function (Type $type) use ($nameResolver) {
            if ($type instanceof ObjectType) {
                $nameStr = $type->getClass();
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
                    return Type::object_($nameStr);
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
