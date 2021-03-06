<?php

namespace Tests\PhpCmplr\Core\Parser;

use PhpParser\NodeDumper;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Comment;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\SourceFile\SourceFile;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\Parser\PositionsReconstructor;

/**
 * @covers \PhpCmplr\Core\Parser\Parser
 * @covers \PhpCmplr\Core\Parser\PositionsReconstructor
 */
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
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        $parser = new Parser($container);
        $container->set('parser', $parser);
        return $parser;
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
        name: Expr_Error(
        )
    )
)
END;
        $this->assertSame($dump, $this->dumper->dump($nodes));
    }

    public function test_getNodes_objectOperator()
    {
        $nodes = $this->loadFile('<?php $a->qaz;')->getNodes();
        $dump = <<<'END'
array(
    0: Expr_PropertyFetch(
        var: Expr_Variable(
            name: a
        )
        name: qaz
    )
)
END;
        $this->assertSame($dump, $this->dumper->dump($nodes));
    }

    public function test_getNodes_reconstructor()
    {
        $nodes = $this->loadFile('<?php $qaz->wsx;')->getNodes();
        $this->assertSame('wsx', $nodes[0]->name);
        $this->assertSame(12, $nodes[0]->getAttribute('nameStartFilePos'));
        $this->assertSame(14, $nodes[0]->getAttribute('nameEndFilePos'));
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
        $this->assertCount(2, $nodes);
        $this->assertInstanceOf(Expr\PropertyFetch::class, $nodes[0]);
        $this->assertInstanceOf(Stmt\Function_::class, $nodes[1]);
    }

    public function test_getNodesAtOffset_namespace()
    {
        $nodes = $this->loadFile('<?php namespace N; function f() { $x = 0; $y->qaz; }')->getNodesAtOffset(48);
        $this->assertCount(3, $nodes);
        $this->assertInstanceOf(Expr\PropertyFetch::class, $nodes[0]);
        $this->assertInstanceOf(Stmt\Function_::class, $nodes[1]);
        $this->assertInstanceOf(Stmt\Namespace_::class, $nodes[2]);
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
