<?php

namespace Tests\PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\Reflection\ReflectionComponentInterface;
use PhpCmplr\Completer\Reflection\ReflectionComponent;
use PhpCmplr\Completer\Reflection\ClassLike;
use PhpCmplr\Completer\Reflection\Class_;
use PhpCmplr\Completer\Reflection\Interface_;
use PhpCmplr\Completer\Reflection\Trait_;
use PhpCmplr\Completer\Reflection\TraitAlias;
use PhpCmplr\Completer\Reflection\TraitInsteadOf;
use PhpCmplr\Completer\Reflection\Method;

class ReflectionComponentTest extends \PHPUnit_Framework_TestCase
{
    protected function prepare(...$classes)
    {
        $map = [];
        foreach ($classes as $class) {
            $map[] = [$class->getName(), [$class]];
        }
        $container = new Container();
        $refl = $this->getMock(ReflectionComponentInterface::class);
        $refl
            ->method('findClass')
            ->will($this->returnValueMap($map));
        $container->set('reflection.component', $refl, ['reflection.component']);
        return new ReflectionComponent($container);
    }

    protected function makeMethod($name)
    {
        return (new Method())
            ->setName($name)
            ->setReturnType(Type::mixed_())
            ->setDocReturnType(Type::mixed_());
    }

    public function test_findAllMethods_baseClass()
    {
        $base = (new Class_())
            ->setName('\\B')
            ->addMethod($this->makeMethod('f'))
            ->addMethod($this->makeMethod('g'));

        $class = (new Class_())
            ->setName('\\C')
            ->setExtends('\\B')
            ->addMethod($this->makeMethod('g'))
            ->addMethod($this->makeMethod('h'));

        $refl = $this->prepare($class, $base);
        $methods = $refl->findAllMethods('\\C');
        $this->assertCount(3, $methods);
        $names = [$methods['f']->getName(), $methods['g']->getName(), $methods['h']->getName()];
        $this->assertSame(['f', 'g', 'h'], $names);
    }

    public function test_findAllMethods_implementedInterface()
    {
        $iface = (new Interface_())
            ->setName('\\B')
            ->addMethod($this->makeMethod('f'))
            ->addMethod($this->makeMethod('g'));

        $class = (new Class_())
            ->setName('\\C')
            ->addImplements('\\B')
            ->addMethod($this->makeMethod('g'))
            ->addMethod($this->makeMethod('h'));

        $refl = $this->prepare($class, $iface);
        $methods = $refl->findAllMethods('\\C');
        $this->assertCount(3, $methods);
        $names = [$methods['f']->getName(), $methods['g']->getName(), $methods['h']->getName()];
        $this->assertSame(['f', 'g', 'h'], $names);
        $this->assertTrue($methods['f']->isAbstract());
        $this->assertFalse($methods['g']->isAbstract());
        $this->assertFalse($methods['h']->isAbstract());
    }

    public function test_findAllMethods_usedTraits()
    {
        $trait = (new Trait_())
            ->setName('\\B')
            ->addMethod($this->makeMethod('f'))
            ->addMethod($this->makeMethod('g'));

        $class = (new Class_())
            ->setName('\\C')
            ->addTrait('\\B')
            ->addMethod($this->makeMethod('g'))
            ->addMethod($this->makeMethod('h'))
            ->addTraitAlias((new TraitAlias())
                ->setTrait('\\B')
                ->setMethod('f')
                ->setNewName('i'));

        $refl = $this->prepare($class, $trait);
        $methods = $refl->findAllMethods('\\C');
        $this->assertCount(4, $methods);
        $names = [$methods['f']->getName(),
            $methods['g']->getName(),
            $methods['h']->getName(),
            $methods['i']->getName()];
        $this->assertSame(['f', 'g', 'h', 'i'], $names);
    }

    public function test_findAllMethods_usedTraits_insteadOf()
    {
        $traitA = (new Trait_())
            ->setName('\\A')
            ->addMethod($this->makeMethod('f')->setAccessibility(ClassLike::M_PRIVATE))
            ->addMethod($this->makeMethod('g'));

        $traitB = (new Trait_())
            ->setName('\\B')
            ->addMethod($this->makeMethod('f')->setAccessibility(ClassLike::M_PUBLIC));

        $class = (new Class_())
            ->setName('\\C')
            ->addTrait('\\A')
            ->addTrait('\\B')
            ->addMethod($this->makeMethod('g'))
            ->addMethod($this->makeMethod('h'))
            ->addTraitInsteadOf((new TraitInsteadOf())
                ->setTrait('\\A')
                ->setMethod('f')
                ->addInsteadOf('\\B'));

        $refl = $this->prepare($class, $traitA, $traitB);
        $methods = $refl->findAllMethods('\\C');
        $this->assertCount(3, $methods);
        $names = [$methods['f']->getName(),
            $methods['g']->getName(),
            $methods['h']->getName()];
        $this->assertSame(['f', 'g', 'h'], $names);
        $this->assertTrue($methods['f']->isPrivate());
    }
}