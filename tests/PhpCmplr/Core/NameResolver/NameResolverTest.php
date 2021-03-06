<?php

namespace Tests\PhpCmplr\Core\NameResolver;

use PhpParser\Node\Name\FullyQualified;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\SourceFile\SourceFile;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\DocComment\DocCommentParser;
use PhpCmplr\Core\NameResolver\NameResolver;
use PhpCmplr\Core\DocComment\DocCommentNameResolver;
use PhpCmplr\Core\Parser\PositionsReconstructor;

/**
 * @covers \PhpCmplr\Core\NameResolver\NameResolver
 * @covers \PhpCmplr\Core\DocComment\DocCommentNameResolver
 */
class NameResolverTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', $parser = new Parser($container));
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        $container->set('doc_comment', new DocCommentParser($container));
        $container->set('name_resolver.doc_comment', new DocCommentNameResolver($container), ['name_resolver']);
        return [$parser, new NameResolver($container)];
    }

    public function test_run()
    {
        $source = <<<'END'
<?php
namespace A\B;

use P\Q;
use R\S;
use T\U as UU;

class C {
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

    public function test_run_self()
    {
        $source = <<<'END'
<?php
namespace A\B;

class C {
    public function f(self $a) {}
}
END;
        list($parser, $resolver) = $this->loadFile($source);
        $resolver->run();
        $nodes = $parser->getNodes();

        $class = $nodes[0]->stmts[0];

        $params = $class->stmts[0]->params;
        $this->assertInstanceOf(FullyQualified::class, $params[0]->type->getAttribute('resolved'));
        $this->assertSame('A\\B\\C', $params[0]->type->getAttribute('resolved')->toString());
    }

    public function test_run_static()
    {
        $source = <<<'END'
<?php
namespace A\B;

class C {
    public function f() { static::g(); }
}
END;
        list($parser, $resolver) = $this->loadFile($source);
        $resolver->run();
        $nodes = $parser->getNodes();

        $class = $nodes[0]->stmts[0];

        $call = $class->stmts[0]->stmts[0];
        $this->assertInstanceOf(FullyQualified::class, $call->class->getAttribute('resolved'));
        $this->assertSame('A\\B\\C', $call->class->getAttribute('resolved')->toString());
    }

    public function test_run_parent()
    {
        $source = <<<'END'
<?php
namespace A\B;

class C extends D {
    public function f(parent $a) {}
}
END;
        list($parser, $resolver) = $this->loadFile($source);
        $resolver->run();
        $nodes = $parser->getNodes();

        $class = $nodes[0]->stmts[0];

        $params = $class->stmts[0]->params;
        $this->assertInstanceOf(FullyQualified::class, $params[0]->type->getAttribute('resolved'));
        $this->assertSame('A\\B\\D', $params[0]->type->getAttribute('resolved')->toString());
    }

    public function test_run_docComment()
    {
        $source = <<<'END'
<?php
namespace A\B;

use P\Q;
use R\S;
use T\U as UU;

class C {
    /**
     * @param S $a
     * @param UU $b
     * @param X $c
     * @param \Z $d
     * @return \Q
     */
    public function f($a, $b, $c, $d) {}
}
END;
        list($parser, $resolver) = $this->loadFile($source);
        $resolver->run();
        $nodes = $parser->getNodes();

        $annotations = $nodes[0]->stmts[3]->stmts[0]->getAttribute('annotations');

        $this->assertSame('\\R\\S', $annotations['param'][0]->getType()->getClass());
        $this->assertSame('\\T\\U', $annotations['param'][1]->getType()->getClass());
        $this->assertSame('\\A\\B\\X', $annotations['param'][2]->getType()->getClass());
        $this->assertSame('\\Z', $annotations['param'][3]->getType()->getClass());
        $this->assertSame('\\Q', $annotations['return'][0]->getType()->getClass());
    }

    public function test_run_docCommentComplexTypes()
    {
        $source = <<<'END'
<?php
use P\Q;
use R\S;
use T\U as UU;

class C {
    /**
     * @param Q[] $a
     * @param S|UU $b
     */
    public function f($a, $b) {}
}
END;
        list($parser, $resolver) = $this->loadFile($source);
        $resolver->run();
        $nodes = $parser->getNodes();

        $class = $nodes[3];
        $this->assertInstanceOf(FullyQualified::class, $class->getAttribute('namespacedName'));
        $this->assertSame('C', $class->getAttribute('namespacedName')->toString());
        $annotations = $class->stmts[0]->getAttribute('annotations');

        $this->assertSame('array', $annotations['param'][0]->getType()->getName());
        $this->assertSame('\\P\\Q', $annotations['param'][0]->getType()->getValueType()->getClass());
        $this->assertSame('mixed', $annotations['param'][0]->getType()->getKeyType()->getName());

        $this->assertSame('alternatives', $annotations['param'][1]->getType()->getName());
        $alts = $annotations['param'][1]->getType()->getAlternatives();
        $this->assertCount(2, $alts);
        $this->assertSame('\\R\\S', $alts[0]->getClass());
        $this->assertSame('\\T\\U', $alts[1]->getClass());
    }

    public function test_run_unqualifiedFunction()
    {
        $source = <<<'END'
<?php
namespace A\B;

f();
END;
        list($parser, $resolver) = $this->loadFile($source);
        $resolver->run();
        $nodes = $parser->getNodes();

        $funcCall = $nodes[0]->stmts[0];

        $this->assertInstanceOf(FullyQualified::class, $funcCall->name->getAttribute('resolved'));
        $this->assertSame('f', $funcCall->name->getAttribute('resolved')->toString());
        $this->assertInstanceOf(FullyQualified::class, $funcCall->name->getAttribute('namespacedName'));
        $this->assertSame('A\\B\\f', $funcCall->name->getAttribute('namespacedName')->toString());
    }
}
