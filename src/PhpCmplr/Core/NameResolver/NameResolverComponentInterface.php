<?php

namespace PhpCmplr\Core\NameResolver;

use PhpParser\Node;
use PhpCmplr\Core\Runnable;

interface NameResolverComponentInterface extends Runnable
{
    /**
     * @param Node                  $node
     * @param NameResolverInterface $nameResolver
     */
    public function enterNode(Node $node, NameResolverInterface $nameResolver);
}
