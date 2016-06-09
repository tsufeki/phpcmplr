<?php

namespace Tests\PhpCmplr\Completer\Parser;

use PhpParser\NodeDumper;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Comment;

use PhpCmplr\Completer\Parser\Identifier;
use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile;
use PhpCmplr\Completer\Parser\ParserComponent;

class ParserComponentTest extends \PHPUnit_Framework_TestCase
{
    protected $dumper;

    public function setUp()
    {
        $this->dumper = new NodeDumper();
    }

    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        return new ParserComponent($container);
    }

    public function test_getNodes()
    {
        $nodes = $this->loadFile('<?php $qaz = 7;')->getNodes();
        $dump = <<<'END'
array(
    0: Expr_Assign(
        var: Expr_Variable(
            name: qaz
        )
        expr: Scalar_LNumber(
            value: 7
        )
    )
)
END;
        $this->assertSame($dump, $this->dumper->dump($nodes));
    }

    public function test_getNodes_lenient_objectOperator()
    {
        $nodes = $this->loadFile('<?php $a->;')->getNodes();
        $dump = <<<'END'
array(
    0: Expr_PropertyFetch(
        var: Expr_Variable(
            name: a
        )
        name: ErrorNode_NoString(
        )
    )
)
END;
        $this->assertSame($dump, $this->dumper->dump($nodes));
    }

    public function test_getNodes_Identifier_objectOperator()
    {
        $nodes = $this->loadFile('<?php $a->qaz;')->getNodes();
        $dump = <<<'END'
array(
    0: Expr_PropertyFetch(
        var: Expr_Variable(
            name: a
        )
        name: Identifier(
            name: qaz
        )
    )
)
END;
        $this->assertSame($dump, $this->dumper->dump($nodes));
    }

    public function test_getErrors()
    {
        $errors = $this->loadFile('<?php 7 + *1;')->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame("Syntax error, unexpected '*'", $errors[0]->getRawMessage());
    }

    public function test_getErrors_empty()
    {
        $errors = $this->loadFile('<?php 7 + 1;')->getErrors();
        $this->assertCount(0, $errors);
    }

    public function test_getNodesAtOffset()
    {
        $nodes = $this->loadFile('<?php function f() { $x = 0; $y->qaz; }')->getNodesAtOffset(35);
        $this->assertCount(3, $nodes);
        $this->assertInstanceOf(Identifier::class, $nodes[0]);
        $this->assertInstanceOf(Expr\PropertyFetch::class, $nodes[1]);
        $this->assertInstanceOf(Stmt\Function_::class, $nodes[2]);
    }

    public function test_getNodesAtOffset_namespace()
    {
        $nodes = $this->loadFile('<?php namespace N; function f() { $x = 0; $y->qaz; }')->getNodesAtOffset(48);
        $this->assertCount(4, $nodes);
        $this->assertInstanceOf(Identifier::class, $nodes[0]);
        $this->assertInstanceOf(Expr\PropertyFetch::class, $nodes[1]);
        $this->assertInstanceOf(Stmt\Function_::class, $nodes[2]);
        $this->assertInstanceOf(Stmt\Namespace_::class, $nodes[3]);
    }

    public function test_getNodesAtOffset_docComments()
    {
        $nodes = $this->loadFile('<?php /** qaz */ function f() { $x = 0; }')->getNodesAtOffset(12);
        $this->assertCount(2, $nodes);
        $this->assertInstanceOf(Comment\Doc::class, $nodes[0]);
        $this->assertSame('/** qaz */', $nodes[0]->getText());
        $this->assertInstanceOf(Stmt\Function_::class, $nodes[1]);
    }
}
