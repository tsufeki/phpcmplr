<?php

namespace Tests\PhpCmplr\Core\Reflection;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\Reflection\ReflectionInterface;
use PhpCmplr\Core\Reflection\Reflection;
use PhpCmplr\Core\Reflection\Element\ClassLike;
use PhpCmplr\Core\Reflection\Element\Class_;
use PhpCmplr\Core\Reflection\Element\Interface_;
use PhpCmplr\Core\Reflection\Element\Trait_;
use PhpCmplr\Core\Reflection\Element\TraitAlias;
use PhpCmplr\Core\Reflection\Element\TraitInsteadOf;
use PhpCmplr\Core\Reflection\Element\Method;

/**
 * @covers \PhpCmplr\Core\Reflection\Reflection
 */
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

    public function test_getCommonType()
    {
        $base = (new Class_())
            ->setName('\\B');

        $class = (new Class_())
            ->setName('\\C')
            ->setExtends('\\B')
            ->addMethod($this->makeMethod('f')
                ->setReturnType(Type::fromString('int|int[]|\\C|\\B[]'))
                ->setDocReturnType(Type::fromString('int|string|\\C[]|\\B')));

        $refl = $this->prepare($class, $base);
        $this->assertSame('\\C[]|int|\\C', $refl->findMethod('\\C', 'f')->getDocReturnType()->toString());
    }

    public function test_filterAvailableMembers()
    {
        $base = (new Class_())
            ->setName('\\B');

        $trait = (new Trait_())
            ->setName('\\T');

        $class = (new Class_())
            ->setName('\\C')
            ->setExtends('\\B')
            ->addTrait($trait);

        $sub = (new Class_())
            ->setName('\\D')
            ->setExtends('\\C');

        $other = (new Class_())
            ->setName('\\X');

        $membersOfClass = [
            (new Method())
                ->setAccessibility(ClassLike::M_PRIVATE)
                ->setName('a')
                ->setClass($class),
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('b')
                ->setClass($class),
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('c')
                ->setClass($base),
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('d')
                ->setClass($trait),
        ];
        $membersOfSub = [
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('e')
                ->setClass($sub),
        ];
        $membersOfBase = [
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('f')
                ->setClass($base),
        ];
        $membersOfOther = [
            (new Method())
                ->setName('g')
                ->setClass($other),
            (new Method())
                ->setAccessibility(ClassLike::M_PRIVATE)
                ->setName('h')
                ->setClass($other),
            (new Method())
                ->setAccessibility(ClassLike::M_PROTECTED)
                ->setName('i')
                ->setClass($other),
        ];

        $refl = $this->prepare($class, $base, $sub, $other, $trait);
        $names = [];
        foreach ($refl->filterAvailableMembers('\\C', $membersOfClass, '\\C') as $member) {
            $names[] = $member->getName();
        }
        foreach ($refl->filterAvailableMembers('\\D', $membersOfSub, '\\C') as $member) {
            $names[] = $member->getName();
        }
        foreach ($refl->filterAvailableMembers('\\B', $membersOfBase, '\\C') as $member) {
            $names[] = $member->getName();
        }
        foreach ($refl->filterAvailableMembers('\\X', $membersOfOther, '\\C') as $member) {
            $names[] = $member->getName();
        }

        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f', 'g'], $names);
    }
}
