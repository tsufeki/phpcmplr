<?php

namespace PhpCmplr\Completer\NameResolver;

use PhpParser\Node\Name;

interface NameResolverInterface
{
    /**
     * @param Name $name
     */
    public function resolveClassName(Name $name);

    /**
     * @param Name $name
     * @param int  $type \PhpParser\Node\Stmt\Use_::TYPE_FUNCTION or ::TYPE_CONSTANT
     */
    public function resolveOtherName(Name $name, $type);
}
