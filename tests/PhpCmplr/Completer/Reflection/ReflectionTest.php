<?php

namespace Tests\PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Reflection\ReflectionInterface;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\Element\ClassLike;
use PhpCmplr\Completer\Reflection\Element\Class_;
use PhpCmplr\Completer\Reflection\Element\Interface_;
use PhpCmplr\Completer\Reflection\Element\Trait_;
use PhpCmplr\Completer\Reflection\Element\TraitAlias;
use PhpCmplr\Completer\Reflection\Element\TraitInsteadOf;
use PhpCmplr\Completer\Reflection\Element\Method;

class ReflectionTest extends \PHPUnit_Framework_TestCase
{
    protected function prepare(...$classes)
    {
        $map = [];
        foreach ($classes as $class) {
            $map[] = [$class->getName(), [$class]];
        }
        $container = new Container();
        $refl = $this->createMock(ReflectionInterface::class);
        $refl
            ->method('findClass')
            ->will($this->returnValueMap($map));
        $container->set('reflection', $refl, ['reflection']);
        return new Reflection($container);
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
