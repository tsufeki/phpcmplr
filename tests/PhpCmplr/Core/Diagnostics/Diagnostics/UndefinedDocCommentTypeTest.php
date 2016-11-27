<?php

namespace Tests\PhpCmplr\Core\Diagnostics\Diagnostics;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\SourceFile\SourceFile;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\Diagnostics\Diagnostics\DuplicateMember;
use PhpCmplr\Core\Parser\PositionsReconstructor;
use PhpCmplr\Core\Diagnostics\Diagnostics\UndefinedDocCommentType;
use PhpCmplr\Core\NameResolver\NameResolver;
use PhpCmplr\Core\Diagnostics\FixHelper;
use PhpCmplr\Core\Reflection\Reflection;
use PhpCmplr\Core\Reflection\NamespaceReflection;
use PhpCmplr\Core\DocComment\DocCommentParser;
use PhpCmplr\Core\DocComment\DocCommentNameResolver;

/**
 * @covers \PhpCmplr\Core\Diagnostics\Diagnostics\UndefinedDocCommentType
 */
class UndefinedDocCommentTypeTest extends \PHPUnit_Framework_TestCase
{
    protected function getDiags($contents, $class, $found = [], $fixes = [], $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', new Parser($container), ['diagnostics']);
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        $container->set('doc_comment', new DocCommentParser($container));
        $container->set('name_resolver.doc_comment', new DocCommentNameResolver($container), ['name_resolver']);
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
        $component = new UndefinedDocCommentType($container);
        $component->run();

        return $component->getDiagnostics();
    }

    public function test_var()
    {
        $diags = $this->getDiags('<?php /** @var C $x */ $x;', '\\C');
        $this->assertCount(1, $diags);
        $this->assertSame('Undefined class', $diags[0]->getDescription());
        $range = $diags[0]->getRanges()[0];
        $this->assertSame('qaz.php', $diags[0]->getPath());
        $this->assertSame(15, $range->getStart()->getOffset());
        $this->assertSame(15, $range->getEnd()->getOffset());
    }

    public function test_var_noDiag()
    {
        $diags = $this->getDiags('<?php /** @var C $x */ $x;', '\\C', ['a class']);
        $this->assertCount(0, $diags);
    }
}
