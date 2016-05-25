<?php

namespace Tests\PhpCmplr\Completer\TypeInferrer;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Name;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Parser\NameResolverComponent;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\Parser\DocTag\DocTag;
use PhpCmplr\Completer\Reflection\ReflectionComponentInterface;
use PhpCmplr\Completer\Reflection\ReflectionComponent;
use PhpCmplr\Completer\Reflection\Method;
use PhpCmplr\Completer\Reflection\Property;
use PhpCmplr\Completer\TypeInferrer\ReflectionInferrerComponent;

class ReflectionInferrerComponentTest extends \PHPUnit_Framework_TestCase
{
    protected function infer(array $nodes, $reflection)
    {
        $container = new Container();
        $parser = $this->getMockBuilder(ParserComponent::class)->disableOriginalConstructor()->getMock();
        $parser->method('getNodes')->willReturn($nodes);
        $resolver = $this->getMockBuilder(NameResolverComponent::class)->disableOriginalConstructor()->getMock();
        $container->set('parser', $parser);
        $container->set('name_resolver', $resolver);
        $container->set('reflection', $reflection);
        (new ReflectionInferrerComponent($container))->run();
    }

    public function test_MethodCall()
    {
        $method = (new Method())->setDocReturnType(Type::int_())->setStatic(false);
        $refl = $this->getMockBuilder(ReflectionComponent::class)->disableOriginalConstructor()->getMock();
        $refl
            ->expects($this->once())
            ->method('findMethod')
            ->with($this->equalTo('\\C'), $this->equalTo('f'))
            ->willReturn($method);
        $var1 = new Expr\Variable('a', ['type' => Type::object_('\\C')]);
        $expr = new Expr\MethodCall($var1, 'f');
        $this->infer([$expr], $refl);
        $this->assertTrue($expr->getAttribute('type')->equals(Type::int_()));
    }

    public function test_MethodCall_alternatives()
    {
        $method = (new Method())->setDocReturnType(Type::int_())->setStatic(false);
        $refl = $this->getMockBuilder(ReflectionComponent::class)->disableOriginalConstructor()->getMock();
        $refl
            ->expects($this->once())
            ->method('findMethod')
            ->with($this->equalTo('\\C'), $this->equalTo('f'))
            ->willReturn($method);
        $var1 = new Expr\Variable('a', ['type' => Type::alternatives([
            Type::bool_(),
            Type::object_('\\C'),
        ])]);
        $expr = new Expr\MethodCall($var1, 'f');
        $this->infer([$expr], $refl);
        $this->assertTrue($expr->getAttribute('type')->equals(Type::int_()));
    }

    public function test_Variable()
    {
        $refl = $this->getMockBuilder(ReflectionComponent::class)->disableOriginalConstructor()->getMock();
        $var1 = new Expr\Variable('a', ['annotations' => ['var' => [DocTag::get('var', 'int')]]]);
        $var2 = new Expr\Variable('a');
        $this->infer([$var1, $var2], $refl);
        $this->assertTrue($var2->getAttribute('type')->equals(Type::int_()));
    }

    public function test_Variable_this()
    {
        $refl = $this->getMockBuilder(ReflectionComponent::class)->disableOriginalConstructor()->getMock();
        $var1 = new Expr\Variable('this');
        $class = new Stmt\Class_('C', ['stmts' => [
            new Stmt\ClassMethod('f', ['stmts' => [$var1]]),
        ]], ['namespacedName' => new Name\FullyQualified('C')]);
        $this->infer([$class], $refl);
        $this->assertTrue($var1->getAttribute('type')->equals(Type::object_('\\C')));
    }

    public function test_StaticPropertyFetch_self()
    {
        $prop = (new Property())->setName('x')->setStatic(true)->setType(Type::int_());
        $refl = $this->getMockBuilder(ReflectionComponent::class)->disableOriginalConstructor()->getMock();
        $refl
            ->expects($this->once())
            ->method('findProperty')
            ->with($this->equalTo('\\C'), $this->equalTo('$x'))
            ->willReturn($prop);
        $expr = new Expr\StaticPropertyFetch(new Name('self'), 'x');
        $class = new Stmt\Class_('C', ['stmts' => [
            new Stmt\ClassMethod('f', ['stmts' => [$expr]]),
        ]], ['namespacedName' => new Name\FullyQualified('C')]);
        $this->infer([$class], $refl);
        $this->assertTrue($expr->getAttribute('type')->equals(Type::int_()));
    }
}
