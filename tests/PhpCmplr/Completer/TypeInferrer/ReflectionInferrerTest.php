<?php

namespace Tests\PhpCmplr\Completer\TypeInferrer;

use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Name;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\NameResolver\NameResolver;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\DocComment\Tag\Tag;
use PhpCmplr\Completer\Reflection\ReflectionInterface;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\Element\Method;
use PhpCmplr\Completer\Reflection\Element\Property;
use PhpCmplr\Completer\TypeInferrer\ReflectionInferrer;

/**
 * @covers \PhpCmplr\Completer\TypeInferrer\ReflectionInferrer
 */
class ReflectionInferrerTest extends \PHPUnit_Framework_TestCase
{
    protected function infer(array $nodes, $reflection)
    {
        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser->method('getNodes')->willReturn($nodes);
        $resolver = $this->getMockBuilder(NameResolver::class)->disableOriginalConstructor()->getMock();
        $container->set('parser', $parser);
        $container->set('name_resolver', $resolver);
        $container->set('reflection', $reflection);
        (new ReflectionInferrer($container))->run();
    }

    public function test_MethodCall()
    {
        $method = (new Method())->setDocReturnType(Type::int_())->setStatic(false);
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $refl
            ->expects($this->once())
            ->method('findMethod')
            ->with($this->equalTo('\\C'), $this->equalTo('f'))
            ->willReturn($method);
        $var1 = new Expr\Variable('a', ['type' => Type::object_('\\C')]);
        $expr = new Expr\MethodCall($var1, 'f');
        $this->infer([$expr], $refl);
        $this->assertTrue($expr->getAttribute('type')->equals(Type::int_()));
        $this->assertSame([$method], $expr->getAttribute('reflections'));
    }

    public function test_MethodCall_alternatives()
    {
        $method = (new Method())->setDocReturnType(Type::int_())->setStatic(false);
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
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
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $var1 = new Expr\Variable('a', ['annotations' => ['var' => [Tag::get('var', 'int $a')]]]);
        $var2 = new Expr\Variable('a');
        $this->infer([$var1, $var2], $refl);
        $this->assertTrue($var2->getAttribute('type')->equals(Type::int_()));
    }

    public function test_Variable_this()
    {
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $var1 = new Expr\Variable('this');
        $class = new Stmt\Class_('C', ['stmts' => [
            new Stmt\ClassMethod('f', ['stmts' => [$var1]]),
        ]], ['namespacedName' => new Name\FullyQualified('C')]);
        $this->infer([$class], $refl);
        $this->assertTrue($var1->getAttribute('type')->equals(Type::object_('\\C')));
    }

    public function test_Variable_for()
    {
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $for = new Stmt\For_([
            'init' => new Expr\Assign(new Expr\Variable('a'), new Expr\Variable('b')),
        ], ['annotations' => ['var' => [Tag::get('var', 'int')]]]);
        $var1 = new Expr\Variable('a');
        $this->infer([$for, $var1], $refl);
        $this->assertTrue($var1->getAttribute('type')->equals(Type::int_()));
    }

    public function test_StaticPropertyFetch_self()
    {
        $prop = (new Property())->setName('x')->setStatic(true)->setType(Type::int_());
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
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
        $this->assertSame([$prop], $expr->getAttribute('reflections'));
    }
}
