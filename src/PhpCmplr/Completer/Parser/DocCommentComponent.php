<?php

namespace PhpCmplr\Completer\Parser;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\NodeTraverserComponent;

class DocCommentComponent extends NodeTraverserComponent
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->addVisitor(new DocCommentNodeVisitor());
    }
}
