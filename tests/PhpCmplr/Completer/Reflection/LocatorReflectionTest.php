<?php

namespace Tests\PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\ContainerFactoryInterface;
use PhpCmplr\Completer\Project;
use PhpCmplr\Completer\SourceFile\SourceFile;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\DocComment\DocCommentParser;
use PhpCmplr\Completer\NameResolver\NameResolver;
use PhpCmplr\Completer\Reflection\FileReflection;
use PhpCmplr\Completer\Reflection\LocatorReflection;
use PhpCmplr\Completer\Reflection\LocatorInterface;
use PhpCmplr\Completer\Reflection\Element\Class_;
use PhpCmplr\Completer\Reflection\Element\ClassLike;
use PhpCmplr\Completer\Reflection\Element\Function_;
use PhpCmplr\Completer\Reflection\Element\Interface_;
use PhpCmplr\Completer\Reflection\Element\Trait_;
use PhpCmplr\Util\FileIOInterface;

class LocatorReflectionTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', new Parser($container));
        $container->set('doc_comment', new DocCommentParser($container));
        $container->set('name_resolver', new NameResolver($container));
        $container->set('reflection.file', new FileReflection($container));

        return $container;
    }

    public function test_findClass()
    {
        $cont1 = $this->loadFile('<?php class CC { public function f() {} }', '/qaz.php');
        $cont2 = $this->loadFile('<?php ;', '/wsx.php');

        $factory = $this->getMockForAbstractClass(ContainerFactoryInterface::class);
        $factory->expects($this->exactly(2))
            ->method('createContainer')
            ->will($this->onConsecutiveCalls($cont1, $cont2));

        $locator = $this->getMockForAbstractClass(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('getPathsForClass')
            ->with($this->equalTo('\\CC'))
            ->willReturn(['/qaz.php']);
        $cont2->set('locator', $locator, ['reflection.locator']);

        $io = $this->getMockForAbstractClass(FileIOInterface::class);
        $cont2->set('io', $io);

        $project = new Project($factory);
        $project->addFile('/qaz.php', '');
        $project->addFile('/wsx.php', '');
        $cont2->set('project', $project);

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

        $factory = $this->getMockForAbstractClass(ContainerFactoryInterface::class);
        $factory->expects($this->exactly(2))
            ->method('createContainer')
            ->will($this->onConsecutiveCalls($cont1, $cont2));

        $locator = $this->getMockForAbstractClass(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('getPathsForFunction')
            ->with($this->equalTo('\\fff'))
            ->willReturn(['/qaz.php']);
        $cont2->set('locator', $locator, ['reflection.locator']);

        $io = $this->getMockForAbstractClass(FileIOInterface::class);
        $cont2->set('io', $io);

        $project = new Project($factory);
        $project->addFile('/qaz.php', '');
        $project->addFile('/wsx.php', '');
        $cont2->set('project', $project);

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

        $factory = $this->getMockForAbstractClass(ContainerFactoryInterface::class);
        $factory->expects($this->exactly(2))
            ->method('createContainer')
            ->will($this->onConsecutiveCalls($cont1, $cont2));

        $locator = $this->getMockForAbstractClass(LocatorInterface::class);
        $locator->expects($this->exactly(2))
            ->method('getPathsForConst')
            ->withConsecutive([$this->equalTo('\\ZZ')], [$this->equalTo('\\zZ')])
            ->will($this->onConsecutiveCalls(['/qaz.php'], []));
        $cont2->set('locator', $locator, ['reflection.locator']);

        $io = $this->getMockForAbstractClass(FileIOInterface::class);
        $cont2->set('io', $io);

        $project = new Project($factory);
        $project->addFile('/qaz.php', '');
        $project->addFile('/wsx.php', '');
        $cont2->set('project', $project);

        $refl = new LocatorReflection($cont2);

        $consts = $refl->findConst('\\ZZ');
        $this->assertCount(1, $consts);
        $this->assertSame('\\ZZ', $consts[0]->getName());

        $constsCaseSensitive = $refl->findConst('\\zZ');
        $this->assertCount(0, $constsCaseSensitive);
    }
}
