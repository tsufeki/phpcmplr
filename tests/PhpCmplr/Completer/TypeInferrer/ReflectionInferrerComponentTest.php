<?php

namespace Tests\PhpCmplr\Completer\TypeInferrer;

use PhpParser\Node\Expr;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Parser\NameResolverComponent;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\Reflection\ReflectionComponentInterface;
use PhpCmplr\Completer\Reflection\ReflectionComponent;
use PhpCmplr\Completer\Reflection\Method;
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
}
