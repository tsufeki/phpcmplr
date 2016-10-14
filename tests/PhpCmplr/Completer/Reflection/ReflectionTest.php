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

    protected function makeMethod($name, $visibility = ClassLike::M_PUBLIC)
    {
        return (new Method())
            ->setName($name)
            ->setReturnType(Type::mixed_())
            ->setDocReturnType(Type::mixed_())
            ->setAccessibility($visibility);
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

    public function test_findAllMethods_baseClass_private()
    {
        $base = (new Class_())
            ->setName('\\B')
            ->addMethod($this->makeMethod('f', ClassLike::M_PRIVATE))
            ->addMethod($this->makeMethod('g'));

        $class = (new Class_())
            ->setName('\\C')
            ->setExtends('\\B')
            ->addMethod($this->makeMethod('h'));

        $refl = $this->prepare($class, $base);
        $methods = $refl->findAllMethods('\\C');
        $this->assertCount(2, $methods);
        $names = [$methods['g']->getName(), $methods['h']->getName()];
        $this->assertSame(['g', 'h'], $names);
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

    public function test_isSubclass()
    {
        $base = (new Class_())
            ->setName('\\B');

        $class = (new Class_())
            ->setName('\\C')
            ->setExtends('\\B');

        $refl = $this->prepare($class, $base);
        $this->assertTrue($refl->isSubclass('\\C', '\\B'));
        $this->assertTrue($refl->isSubclass('\\X', '\\X'));
        $this->assertFalse($refl->isSubclass('\\B', '\\C'));
        $this->assertFalse($refl->isSubclass('\\C', '\\D'));
    }

    public function test_filterAvailableMembers()
    {
        $base = (new Class_())
            ->setName('\\B');

        $class = (new Class_())
            ->setName('\\C')
            ->setExtends('\\B');

        $sub = (new Class_())
            ->setName('\\D')
            ->setExtends('\\C');

        $other = (new Class_())
            ->setName('\\X');

        $members = [
            (new Method())
                ->setName('a')
                ->setClass($other),
            (new Method())
                ->setAccessibility(ClassLike::M_PRIVATE)
                ->setName('b')
                ->setClass($class),
            (new Method())
                ->setAccessibility(ClassLike::M_PRIVATE)
                ->setName('c')
                ->setClass($other),
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('d')
                ->setClass($class),
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('e')
                ->setClass($base),
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('f')
                ->setClass($other),
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('g')
                ->setClass($sub),
        ];

        $refl = $this->prepare($class, $base, $sub, $other);
        $names = [];
        foreach ($refl->filterAvailableMembers($members, '\\C') as $member) {
            $names[] = $member->getName();
        }

        $this->assertSame(['a', 'b', 'd', 'e', 'g'], $names);
    }
}
