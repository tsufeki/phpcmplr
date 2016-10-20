<?php

namespace PhpCmplr\Completer\NameResolver;

use PhpParser\Node;
use PhpCmplr\Completer\Runnable;

interface NameResolverComponentInterface extends Runnable
{
    /**
     * @param Node                  $node
     * @param NameResolverInterface $nameResolver
     */
    public function enterNode(Node $node, NameResolverInterface $nameResolver);
}
