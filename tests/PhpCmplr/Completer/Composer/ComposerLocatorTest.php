<?php

namespace Tests\PhpCmplr\Completer\Composer;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile;
use PhpCmplr\Completer\Composer\ComposerLocator;
use PhpCmplr\Util\FileIO;

class ComposerLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function test_getPathsForClass()
    {
        $container = new Container();
        $container->set('io', new FileIO());
        $container->set('file', new SourceFile($container, __DIR__ . '/qaz.php', ''));
        $locator = new ComposerLocator($container);
        $this->assertSame([__FILE__], $locator->getPathsForClass(self::class));
    }

    public function test_getPathsForClass_notRoot()
    {
        $container = new Container();
        $container->set('io', new FileIO());
        $container->set('file', new SourceFile($container,
            __DIR__ . '/../../../../vendor/composer/composer/qaz.php', ''));
        $locator = new ComposerLocator($container);
        $this->assertSame(
            [realpath(__DIR__ . '/../../../../vendor/nikic/php-parser/lib/PhpParser/Node/Expr/Assign.php')],
            $locator->getPathsForClass('PhpParser\\Node\\Expr\\Assign'));
    }

    public function test_getPathsForClass_notFound()
    {
        $container = new Container();
        $container->set('io', new FileIO());
        $container->set('file', new SourceFile($container, '/foobar/qaz.php', ''));
        $locator = new ComposerLocator($container);
        $this->assertSame([], $locator->getPathsForClass(self::class));
    }
}