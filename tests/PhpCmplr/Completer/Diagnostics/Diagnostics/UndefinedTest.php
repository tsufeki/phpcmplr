<?php

namespace Tests\PhpCmplr\Completer\Diagnostics\Diagnostics;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile\SourceFile;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\Diagnostics\Diagnostics\DuplicateMember;
use PhpCmplr\Completer\Parser\PositionsReconstructor;
use PhpCmplr\Completer\Diagnostics\Diagnostics\Undefined;
use PhpCmplr\Completer\NameResolver\NameResolver;
use PhpCmplr\Completer\Diagnostics\FixHelper;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\NamespaceReflection;

/**
 * @covers \PhpCmplr\Completer\Diagnostics\Diagnostics\Undefined
 */
class UndefinedTest extends \PHPUnit_Framework_TestCase
{
    protected function getDiags($contents, $class, $found = [], $fixes = [], $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', new Parser($container), ['diagnostics']);
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        $container->set('name_resolver', new NameResolver($container));
        $container->set('fix_helper', new FixHelper($container));
        $reflection = $this->getMockBuilder(Reflection::class)
            ->setMethods(['findClass'])
            ->disableOriginalConstructor()
            ->getMock();
        $reflection
            ->expects($this->once())
            ->method('findClass')
            ->with($this->equalTo($class))
            ->willReturn($found);
        $container->set('reflection', $reflection);
        $namespaceReflection = $this->getMockBuilder(NamespaceReflection::class)
            ->setMethods(['findFullyQualifiedClasses'])
            ->disableOriginalConstructor()
            ->getMock();
        $namespaceReflection
            ->method('findFullyQualifiedClasses')
            ->with($this->equalTo(explode('\\', $class)[substr_count($class, '\\')]))
            ->willReturn($fixes);
        $container->set('namespace_reflection', $namespaceReflection);
        $component = new Undefined($container);
        $component->run();

        return $component->getDiagnostics();
    }

    public function test_ClassConst()
    {
        $diags = $this->getDiags('<?php C::X;', '\\C');
        $this->assertCount(1, $diags);
        $this->assertSame('Undefined class', $diags[0]->getDescription());
        $range = $diags[0]->getRanges()[0];
        $this->assertSame('qaz.php', $diags[0]->getPath());
        $this->assertSame(6, $range->getStart()->getOffset());
        $this->assertSame(6, $range->getEnd()->getOffset());
    }

    public function test_ClassConst_noDiag()
    {
        $diags = $this->getDiags('<?php C::X;', '\\C', ['a class']);
        $this->assertCount(0, $diags);
    }

    public function test_ClassConst_fix()
    {
        $diags = $this->getDiags('<?php C::X;', '\\C', [], ['\\NN\\C']);
        $this->assertCount(1, $diags);
        $this->assertSame('Undefined class', $diags[0]->getDescription());
        $range = $diags[0]->getRanges()[0];
        $this->assertSame('qaz.php', $diags[0]->getPath());
        $this->assertSame(6, $range->getStart()->getOffset());
        $this->assertSame(6, $range->getEnd()->getOffset());
        $this->assertCount(1, $diags[0]->getFixes());
        $this->assertCount(1, $diags[0]->getFixes()[0]->getChunks());
        $chunk = $diags[0]->getFixes()[0]->getChunks()[0];
        $this->assertContains('use NN\\C;', $chunk->getReplacement());
    }

    public function test_New()
    {
        $diags = $this->getDiags('<?php new C();', '\\C');
        $this->assertCount(1, $diags);
        $this->assertSame('Undefined class', $diags[0]->getDescription());
        $range = $diags[0]->getRanges()[0];
        $this->assertSame('qaz.php', $diags[0]->getPath());
        $this->assertSame(10, $range->getStart()->getOffset());
        $this->assertSame(10, $range->getEnd()->getOffset());
    }

    public function test_Class_extends()
    {
        $diags = $this->getDiags('<?php class A extends C {}', '\\C');
        $this->assertCount(1, $diags);
        $this->assertSame('Undefined class', $diags[0]->getDescription());
        $range = $diags[0]->getRanges()[0];
        $this->assertSame('qaz.php', $diags[0]->getPath());
        $this->assertSame(22, $range->getStart()->getOffset());
        $this->assertSame(22, $range->getEnd()->getOffset());
    }
}
