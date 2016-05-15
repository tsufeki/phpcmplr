<?php

namespace Tests\PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Parser\DocCommentComponent;
use PhpCmplr\Completer\Parser\NameResolverComponent;
use PhpCmplr\Completer\Reflection\FileReflectionComponent;

class FileReflectionComponentTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', new ParserComponent($container));
        $container->set('doc_comment', new DocCommentComponent($container));
        $container->set('name_resolver', new NameResolverComponent($container));
        return new FileReflectionComponent($container);
    }

    public function test_getFunctions()
    {
        $source = <<<'END'
<?php
namespace A\B;

/**
 * @param int $a Qaz
 * @param D   $b
 * @return \X\Y
 */
function &ff(&$a, B $b, \C $c = null, ...$z) : int { }
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $this->assertCount(1, $refl->getFunctions());
        $fun = $refl->getFunctions()[0];

        $this->assertSame('\\A\\B\\ff', $fun->getName());
        $this->assertSame('qaz.php', $fun->getLocation()->getPath());
        $this->assertSame(84, $fun->getLocation()->getOffset(null));
        $this->assertTrue($fun->getReturnByRef());
        $this->assertSame('int', $fun->getReturnType()->getName());
        $this->assertSame('\\X\\Y', $fun->getDocReturnType()->getClass());

        $params = $fun->getParams();
        $this->assertCount(4, $params);

        $this->assertSame('$a', $params[0]->getName());
        $this->assertTrue($params[0]->isByRef());
        $this->assertFalse($params[0]->isOptional());
        $this->assertFalse($params[0]->isVariadic());
        $this->assertSame('mixed', $params[0]->getTypeHint()->getName());
        $this->assertSame('int', $params[0]->getDocType()->getName());

        $this->assertSame('$b', $params[1]->getName());
        $this->assertFalse($params[1]->isByRef());
        $this->assertFalse($params[1]->isOptional());
        $this->assertFalse($params[1]->isVariadic());
        $this->assertSame('\\A\\B\\B', $params[1]->getTypeHint()->getClass());
        $this->assertSame('\\A\\B\\D', $params[1]->getDocType()->getClass());

        $this->assertSame('$c', $params[2]->getName());
        $this->assertFalse($params[2]->isByRef());
        $this->assertTrue($params[2]->isOptional());
        $this->assertFalse($params[2]->isVariadic());
        $this->assertSame('\\C', $params[2]->getTypeHint()->getClass());
        $this->assertSame('mixed', $params[2]->getDocType()->getName());

        $this->assertSame('$z', $params[3]->getName());
        $this->assertFalse($params[3]->isByRef());
        $this->assertFalse($params[3]->isOptional());
        $this->assertTrue($params[3]->isVariadic());
        $this->assertSame('mixed', $params[3]->getTypeHint()->getName());
        $this->assertSame('mixed', $params[3]->getDocType()->getName());
    }
}
