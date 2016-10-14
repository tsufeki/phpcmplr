<?php

namespace PhpCmplr\Completer\NameResolver;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\NodeTraverserComponent;

class NameResolver extends NodeTraverserComponent
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->addVisitor(new NameResolverNodeVisitor());
    }

    public function doRun()
    {
        $docCommentComponent = $this->container->get('doc_comment');
        if ($docCommentComponent) {
            $docCommentComponent->run();
        }
        parent::doRun();
    }
}
