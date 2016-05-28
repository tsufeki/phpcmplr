<?php

namespace Tests\PhpCmplr\Completer\Parser;

use PhpParser\NodeDumper;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

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
        $dump = <<<'DUMP'
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
DUMP;
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
        $this->assertCount(2, $nodes);
        $this->assertInstanceOf(Expr\PropertyFetch::class, $nodes[0]);
        $this->assertInstanceOf(Stmt\Function_::class, $nodes[1]);
    }
}
