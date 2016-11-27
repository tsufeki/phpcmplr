<?php

namespace Tests\PhpCmplr\Core\Reflection;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\FileStoreInterface;
use PhpCmplr\Core\SourceFile\SourceFile;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\DocComment\DocCommentParser;
use PhpCmplr\Core\NameResolver\NameResolver;
use PhpCmplr\Core\Reflection\FileReflection;
use PhpCmplr\Core\Reflection\LocatorReflection;
use PhpCmplr\Core\Reflection\LocatorInterface;
use PhpCmplr\Core\Reflection\Element\Class_;
use PhpCmplr\Core\Reflection\Element\ClassLike;
use PhpCmplr\Core\Reflection\Element\Function_;
use PhpCmplr\Core\Reflection\Element\Interface_;
use PhpCmplr\Core\Reflection\Element\Trait_;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Core\Parser\PositionsReconstructor;

/**
 * @covers \PhpCmplr\Core\Reflection\LocatorReflection
 */
class LocatorReflectionTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', new Parser($container));
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        $container->set('doc_comment', new DocCommentParser($container));
        $container->set('name_resolver', new NameResolver($container));
        $container->set('reflection.file', new FileReflection($container));

        return $container;
    }

    public function test_findClass()
    {
        $cont1 = $this->loadFile('<?php class CC { public function f() {} }', '/qaz.php');
        $cont2 = $this->loadFile('<?php ;', '/wsx.php');

        $fileStore = $this->getMockForAbstractClass(FileStoreInterface::class);
        $fileStore->expects($this->exactly(1))
            ->method('getFile')
            ->with($this->equalTo('/qaz.php'))
            ->willReturn($cont1);
        $cont2->set('file_store', $fileStore);

        $locator = $this->getMockForAbstractClass(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('getPathsForClass')
            ->with($this->equalTo('\\CC'))
            ->willReturn(['/qaz.php']);
        $cont2->set('locator', $locator, ['reflection.locator']);

        $io = $this->getMockForAbstractClass(FileIOInterface::class);
        $cont2->set('io', $io);

        $refl = new LocatorReflection($cont2);

        $classes = $refl->findClass('\\CC');
        $this->assertCount(1, $classes);
        $this->assertSame('\\CC', $classes[0]->getName());
        $this->assertSame('f', $classes[0]->getMethods()[0]->getName());

        $classesCaseInsensitive = $refl->findClass('\\cC');
        $this->assertSame($classes, $classesCaseInsensitive);
    }

    public function test_findFunction()
    {
        $cont1 = $this->loadFile('<?php function fff() {}', '/qaz.php');
        $cont2 = $this->loadFile('<?php ;', '/wsx.php');

        $fileStore = $this->getMockForAbstractClass(FileStoreInterface::class);
        $fileStore->expects($this->exactly(1))
            ->method('getFile')
            ->with($this->equalTo('/qaz.php'))
            ->willReturn($cont1);
        $cont2->set('file_store', $fileStore);

        $locator = $this->getMockForAbstractClass(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('getPathsForFunction')
            ->with($this->equalTo('\\fff'))
            ->willReturn(['/qaz.php']);
        $cont2->set('locator', $locator, ['reflection.locator']);

        $io = $this->getMockForAbstractClass(FileIOInterface::class);
        $cont2->set('io', $io);

        $refl = new LocatorReflection($cont2);

        $functions = $refl->findFunction('\\fff');
        $this->assertCount(1, $functions);
        $this->assertSame('\\fff', $functions[0]->getName());

        $functionsCaseInsensitive = $refl->findFunction('\\fFf');
        $this->assertSame($functions, $functionsCaseInsensitive);
    }

    public function test_findConst()
    {
        $cont1 = $this->loadFile('<?php const ZZ = 7;', '/qaz.php');
        $cont2 = $this->loadFile('<?php ;', '/wsx.php');

        $fileStore = $this->getMockForAbstractClass(FileStoreInterface::class);
        $fileStore->expects($this->exactly(1))
            ->method('getFile')
            ->with($this->equalTo('/qaz.php'))
            ->willReturn($cont1);
        $cont2->set('file_store', $fileStore);

        $locator = $this->getMockForAbstractClass(LocatorInterface::class);
        $locator->expects($this->exactly(2))
            ->method('getPathsForConst')
            ->withConsecutive([$this->equalTo('\\ZZ')], [$this->equalTo('\\zZ')])
            ->will($this->onConsecutiveCalls(['/qaz.php'], []));
        $cont2->set('locator', $locator, ['reflection.locator']);

        $io = $this->getMockForAbstractClass(FileIOInterface::class);
        $cont2->set('io', $io);

        $refl = new LocatorReflection($cont2);

        $consts = $refl->findConst('\\ZZ');
        $this->assertCount(1, $consts);
        $this->assertSame('\\ZZ', $consts[0]->getName());

        $constsCaseSensitive = $refl->findConst('\\zZ');
        $this->assertCount(0, $constsCaseSensitive);
    }
}
