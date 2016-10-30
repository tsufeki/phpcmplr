<?php

namespace Tests\PhpCmplr\Completer\Diagnostics\Diagnostics;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile\SourceFile;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\Diagnostics\Diagnostics\DuplicateMember;
use PhpCmplr\Completer\Parser\PositionsReconstructor;

/**
 * @covers \PhpCmplr\Completer\Diagnostics\Diagnostics\DuplicateMember
 */
class DuplicateMemberTest extends \PHPUnit_Framework_TestCase
{
    protected function getDiags($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', new Parser($container), ['diagnostics']);
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        $component = new DuplicateMember($container);
        $component->run();

        return $component->getDiagnostics();
    }

    public function test_properties()
    {
        $diags = $this->getDiags('<?php class C { public $x, $x; }');
        $this->assertCount(1, $diags);
        $this->assertSame('Redeclared property', $diags[0]->getDescription());
        $range = $diags[0]->getRanges()[0];
        $this->assertSame('qaz.php', $diags[0]->getPath());
        $this->assertSame(27, $range->getStart()->getOffset());
        $this->assertSame(28, $range->getEnd()->getOffset());
    }

    public function test_properties_noDiag()
    {
        $diags = $this->getDiags('<?php class C { public $x, $y; }');
        $this->assertCount(0, $diags);
    }

    public function test_methods()
    {
        $diags = $this->getDiags('<?php class C { function f() {} function f() {} }');
        $this->assertCount(1, $diags);
        $this->assertSame('Redeclared method', $diags[0]->getDescription());
        $range = $diags[0]->getRanges()[0];
        $this->assertSame('qaz.php', $diags[0]->getPath());
        $this->assertSame(41, $range->getStart()->getOffset());
        $this->assertSame(41, $range->getEnd()->getOffset());
    }

    public function test_consts()
    {
        $diags = $this->getDiags('<?php class C { const XX = 1; const XX = 2; }');
        $this->assertCount(1, $diags);
        $this->assertSame('Redeclared class const', $diags[0]->getDescription());
        $range = $diags[0]->getRanges()[0];
        $this->assertSame('qaz.php', $diags[0]->getPath());
        $this->assertSame(36, $range->getStart()->getOffset());
        $this->assertSame(37, $range->getEnd()->getOffset());
    }
}
