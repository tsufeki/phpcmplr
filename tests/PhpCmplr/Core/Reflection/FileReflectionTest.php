<?php

namespace Tests\PhpCmplr\Core\Reflection;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\SourceFile\SourceFile;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\DocComment\DocCommentParser;
use PhpCmplr\Core\NameResolver\NameResolver;
use PhpCmplr\Core\Reflection\FileReflection;
use PhpCmplr\Core\Reflection\Element\Class_;
use PhpCmplr\Core\Reflection\Element\ClassLike;
use PhpCmplr\Core\Reflection\Element\Const_;
use PhpCmplr\Core\Reflection\Element\Function_;
use PhpCmplr\Core\Reflection\Element\Interface_;
use PhpCmplr\Core\Reflection\Element\Trait_;
use PhpCmplr\Core\DocComment\DocCommentNameResolver;
use PhpCmplr\Core\Parser\PositionsReconstructor;

/**
 * @covers \PhpCmplr\Core\Reflection\FileReflection
 */
class FileReflectionTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', new Parser($container));
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        $container->set('doc_comment', new DocCommentParser($container));
        $container->set('name_resolver.doc_comment', new DocCommentNameResolver($container), ['name_resolver']);
        $container->set('name_resolver', new NameResolver($container));
        return new FileReflection($container);
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

        $this->assertInstanceOf(Function_::class, $fun);
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

    public function test_findFunction()
    {
        $source = <<<'END'
<?php
namespace A\B;

function ff() { }
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $this->assertInstanceOf(Function_::class, $refl->findFunction('\\A\\B\\ff')[0]);
    }

    public function test_getClasses()
    {
        $source = <<<'END'
<?php
namespace A\B;

abstract class C extends D implements E\F, \Z {
    /** @var \Y $x */
    public $x, $y;
    /** @var int */
    private static $z;

    final protected function ff($a) { }
    abstract public function gg();

    const QAZ = 13, WSX = -1;
}
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $this->assertCount(1, $refl->getClasses());
        $cls = $refl->getClasses()[0];

        $this->assertInstanceOf(Class_::class, $cls);
        $this->assertSame('\\A\\B\\C', $cls->getName());
        $this->assertTrue($cls->isAbstract());
        $this->assertFalse($cls->isFinal());
        $this->assertSame('\\A\\B\\D', $cls->getExtends());
        $this->assertSame(['\\A\\B\\E\\F', '\\Z'], $cls->getImplements());

        $props = $cls->getProperties();
        $this->assertCount(3, $props);
        $this->assertSame('$x', $props[0]->getName());
        $this->assertSame('\\Y', $props[0]->getType()->getClass());
        $this->assertSame(ClassLike::M_PUBLIC, $props[0]->getAccessibility());
        $this->assertTrue($props[0]->isPublic());
        $this->assertFalse($props[0]->isProtected());
        $this->assertFalse($props[0]->isPrivate());
        $this->assertFalse($props[0]->isStatic());
        $this->assertSame($cls, $props[0]->getClass());
        $this->assertSame('$y', $props[1]->getName());
        $this->assertSame('mixed', $props[1]->getType()->getName());
        $this->assertTrue($props[1]->isPublic());
        $this->assertFalse($props[1]->isStatic());
        $this->assertSame($cls, $props[1]->getClass());
        $this->assertSame('$z', $props[2]->getName());
        $this->assertSame('int', $props[2]->getType()->getName());
        $this->assertTrue($props[2]->isPrivate());
        $this->assertTrue($props[2]->isStatic());
        $this->assertSame($cls, $props[2]->getClass());

        $methods = $cls->getMethods();
        $this->assertCount(2, $methods);
        $this->assertSame('ff', $methods[0]->getName());
        $this->assertFalse($methods[0]->isAbstract());
        $this->assertTrue($methods[0]->isFinal());
        $this->assertTrue($methods[0]->isProtected());
        $this->assertFalse($methods[0]->isStatic());
        $this->assertSame($cls, $methods[0]->getClass());
        $this->assertSame('gg', $methods[1]->getName());
        $this->assertTrue($methods[1]->isAbstract());
        $this->assertFalse($methods[1]->isFinal());
        $this->assertTrue($methods[1]->isPublic());
        $this->assertFalse($methods[1]->isStatic());
        $this->assertSame($cls, $methods[1]->getClass());

        $consts = $cls->getConsts();
        $this->assertCount(2, $consts);
        $this->assertSame('QAZ', $consts[0]->getName());
        $this->assertSame('WSX', $consts[1]->getName());
    }

    public function test_findClass()
    {
        $source = <<<'END'
<?php
namespace A\B;

class C { }
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $this->assertInstanceOf(Class_::class, $refl->findClass('\\A\\B\\C')[0]);
    }

    public function test_getClasses_interface()
    {
        $source = <<<'END'
<?php
namespace A\B;

interface C extends E\F, \Z {
    public function ff($a) { }

    const QAZ = 13;
}
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $this->assertCount(1, $refl->getClasses());
        $iface = $refl->getClasses()[0];

        $this->assertInstanceOf(Interface_::class, $iface);
        $this->assertSame('\\A\\B\\C', $iface->getName());
        $this->assertSame(['\\A\\B\\E\\F', '\\Z'], $iface->getExtends());

        $methods = $iface->getMethods();
        $this->assertCount(1, $methods);
        $this->assertSame('ff', $methods[0]->getName());
        $this->assertSame($iface, $methods[0]->getClass());

        $consts = $iface->getConsts();
        $this->assertCount(1, $consts);
        $this->assertSame('QAZ', $consts[0]->getName());
    }

    public function test_getClasses_trait()
    {
        $source = <<<'END'
<?php
namespace A\B;

trait C {
    /** @var \Y $x */
    public $x, $y;
    /** @var int */
    private $z;

    final protected function ff($a) { }
    abstract public function gg();
}
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $this->assertCount(1, $refl->getClasses());
        $trait = $refl->getClasses()[0];

        $this->assertInstanceOf(Trait_::class, $trait);
        $this->assertSame('\\A\\B\\C', $trait->getName());

        $props = $trait->getProperties();
        $this->assertCount(3, $props);
        $this->assertSame('$x', $props[0]->getName());
        $this->assertSame('\\Y', $props[0]->getType()->getClass());
        $this->assertTrue($props[0]->isPublic());
        $this->assertSame($trait, $props[0]->getClass());
        $this->assertSame('$y', $props[1]->getName());
        $this->assertSame('mixed', $props[1]->getType()->getName());
        $this->assertTrue($props[1]->isPublic());
        $this->assertSame($trait, $props[1]->getClass());
        $this->assertSame('$z', $props[2]->getName());
        $this->assertSame('int', $props[2]->getType()->getName());
        $this->assertTrue($props[2]->isPrivate());
        $this->assertSame($trait, $props[2]->getClass());

        $methods = $trait->getMethods();
        $this->assertCount(2, $methods);
        $this->assertSame('ff', $methods[0]->getName());
        $this->assertFalse($methods[0]->isAbstract());
        $this->assertTrue($methods[0]->isFinal());
        $this->assertTrue($methods[0]->isProtected());
        $this->assertSame($trait, $methods[0]->getClass());
        $this->assertSame('gg', $methods[1]->getName());
        $this->assertTrue($methods[1]->isAbstract());
        $this->assertFalse($methods[1]->isFinal());
        $this->assertTrue($methods[1]->isPublic());
        $this->assertSame($trait, $methods[1]->getClass());
    }

    public function test_getClasses_multi()
    {
        $source = <<<'END'
<?php
namespace A\B;

class C {}
interface I {}
trait T {}
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $classes = $refl->getClasses();
        $this->assertCount(3, $classes);

        $this->assertInstanceOf(Class_::class, $classes[0]);
        $this->assertSame('\\A\\B\\C', $classes[0]->getName());
        $this->assertInstanceOf(Interface_::class, $classes[1]);
        $this->assertSame('\\A\\B\\I', $classes[1]->getName());
        $this->assertInstanceOf(Trait_::class, $classes[2]);
        $this->assertSame('\\A\\B\\T', $classes[2]->getName());
    }

    public function test_getClasses_usedTraits()
    {
        $source = <<<'END'
<?php
namespace A\B;

class C {
    use \T;
    use \U, V {
        \U::f insteadof V;
        V::g as gg;
        \U::h as private h;
    }
}
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $this->assertCount(1, $refl->getClasses());
        $cls = $refl->getClasses()[0];

        $this->assertInstanceOf(Class_::class, $cls);
        $this->assertSame('\\A\\B\\C', $cls->getName());
        $this->assertSame(['\\T', '\\U', '\\A\\B\\V'], $cls->getTraits());

        $insteadOfs = $cls->getTraitInsteadOfs();
        $this->assertCount(1, $insteadOfs);
        $this->assertSame('\\U', $insteadOfs[0]->getTrait());
        $this->assertSame('f', $insteadOfs[0]->getMethod());
        $this->assertSame(['\\A\\B\\V'], $insteadOfs[0]->getInsteadOfs());

        $aliases = $cls->getTraitAliases();
        $this->assertCount(2, $aliases);
        $this->assertSame('\\A\\B\\V', $aliases[0]->getTrait());
        $this->assertSame('g', $aliases[0]->getMethod());
        $this->assertSame('gg', $aliases[0]->getNewName());
        $this->assertSame(null, $aliases[0]->getNewAccessibility());

        $this->assertSame('\\U', $aliases[1]->getTrait());
        $this->assertSame('h', $aliases[1]->getMethod());
        $this->assertSame('h', $aliases[1]->getNewName());
        $this->assertSame(ClassLike::M_PRIVATE, $aliases[1]->getNewAccessibility());
    }

    public function test_getConsts()
    {
        $source = <<<'END'
<?php
namespace A\B;

const QAZ = 13, WSX = -1;
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $consts = $refl->getConsts();
        $this->assertCount(2, $consts);

        $this->assertInstanceOf(Const_::class, $consts[0]);
        $this->assertSame('\\A\\B\\QAZ', $consts[0]->getName());
        $this->assertInstanceOf(Const_::class, $consts[1]);
        $this->assertSame('\\A\\B\\WSX', $consts[1]->getName());
    }

    public function test_findConst()
    {
        $source = <<<'END'
<?php
namespace A\B;

const QAZ = 13;
END;
        $refl = $this->loadFile($source);
        $refl->run();
        $this->assertInstanceOf(Const_::class, $refl->findConst('\\A\\B\\QAZ')[0]);
    }
}
