<?php

namespace Tests\PhpCmplr\Completer\Parser;

use PhpParser\Node\Name\FullyQualified;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Parser\DocCommentComponent;
use PhpCmplr\Completer\Parser\NameResolverComponent;

class NameResolverComponentTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', $parser = new ParserComponent($container));
        $container->set('doc_component', new DocCommentComponent($container));
        return [$parser, new NameResolverComponent($container)];
    }

    public function test_NameResolverNodeVisitor()
    {
        $source = <<<'END'
<?php
namespace A\B;

use P\Q;
use R\S;
use T\U as UU;

class C {
    /** @return Q */
    public function f(S $a, UU $b, X $c, \Z $d) {}
}
END;
        list($parser, $resolver) = $this->loadFile($source);
        $resolver->run();
        $nodes = $parser->getNodes();

        $class = $nodes[0]->stmts[3];
        $this->assertInstanceOf(FullyQualified::class, $class->getAttribute('namespacedName'));
        $this->assertSame('A\\B\\C', $class->getAttribute('namespacedName')->toString());

        $params = $class->stmts[0]->params;
        $this->assertInstanceOf(FullyQualified::class, $params[0]->type->getAttribute('resolved'));
        $this->assertInstanceOf(FullyQualified::class, $params[1]->type->getAttribute('resolved'));
        $this->assertInstanceOf(FullyQualified::class, $params[2]->type->getAttribute('resolved'));
        $this->assertInstanceOf(FullyQualified::class, $params[3]->type->getAttribute('resolved'));
        $this->assertSame('R\\S', $params[0]->type->getAttribute('resolved')->toString());
        $this->assertSame('T\\U', $params[1]->type->getAttribute('resolved')->toString());
        $this->assertSame('A\\B\\X', $params[2]->type->getAttribute('resolved')->toString());
        $this->assertSame('Z', $params[3]->type->getAttribute('resolved')->toString());
    }
}
