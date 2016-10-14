<?php

namespace Tests\PhpCmplr\Completer\Parser;

use PhpLenientParser\NodeDumper;
use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Identifier;
use PhpLenientParser\Comment;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile\SourceFile;
use PhpCmplr\Completer\Parser\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
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
        return new Parser($container);
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
        name: ErrorNode_Nothing(
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

    public function test_getDiagnostics()
    {
        $diags = $this->loadFile('<?php 7 + *1;')->getDiagnostics();
        $this->assertCount(1, $diags);
        $this->assertSame("Syntax error, unexpected '*'", $diags[0]->getDescription());
    }

    public function test_getDiagnostics_empty()
    {
        $diags = $this->loadFile('<?php 7 + 1;')->getDiagnostics();
        $this->assertCount(0, $diags);
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

    public function test_getNodesAtOffset_leftAdjacent()
    {
        $nodes = $this->loadFile('<?php function f() {}$qaz;')->getNodesAtOffset(21, true);
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(Stmt\Function_::class, $nodes[0]);
    }
}
