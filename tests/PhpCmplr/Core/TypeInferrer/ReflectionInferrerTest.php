<?php

namespace Tests\PhpCmplr\Core\TypeInferrer;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Name;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\NameResolver\NameResolver;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\DocComment\Tag\Tag;
use PhpCmplr\Core\Reflection\ReflectionInterface;
use PhpCmplr\Core\Reflection\Reflection;
use PhpCmplr\Core\Reflection\Element\Method;
use PhpCmplr\Core\Reflection\Element\Property;
use PhpCmplr\Core\TypeInferrer\ReflectionInferrer;

/**
 * @covers \PhpCmplr\Core\TypeInferrer\ReflectionInferrer
 */
class ReflectionInferrerTest extends \PHPUnit_Framework_TestCase
{
    protected function infer(array $nodes, $reflection)
    {
        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser->method('getNodes')->willReturn($nodes);
        $resolver = new NameResolver($container);
        $container->set('parser', $parser);
        $container->set('name_resolver', $resolver);
        $container->set('reflection', $reflection);
        $resolver->run();
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
        $refl
            ->expects($this->once())
            ->method('filterAvailableMembers')
            ->with($this->equalTo('\\C'), $this->equalTo([$method]), $this->equalTo(null))
            ->willReturn([$method]);
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
        $refl
            ->expects($this->once())
            ->method('filterAvailableMembers')
            ->with($this->equalTo('\\C'), $this->equalTo([$method]), $this->equalTo(null))
            ->willReturn([$method]);
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
        $refl
            ->expects($this->once())
            ->method('filterAvailableMembers')
            ->with($this->equalTo('\\C'), $this->equalTo([$prop]), $this->equalTo('\\C'))
            ->willReturn([$prop]);
        $expr = new Expr\StaticPropertyFetch(new Name('self'), 'x');
        $class = new Stmt\Class_('C', ['stmts' => [
            new Stmt\ClassMethod('f', ['stmts' => [$expr]]),
        ]], ['namespacedName' => new Name\FullyQualified('C')]);
        $this->infer([$class], $refl);
        $this->assertTrue($expr->getAttribute('type')->equals(Type::int_()));
        $this->assertSame([$prop], $expr->getAttribute('reflections'));
    }

    public function test_Assign()
    {
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $var1 = new Expr\Variable('a', ['type' => Type::object_('\\C')]);
        $var2 = new Expr\Variable('b');
        $var3 = new Expr\Variable('b');
        $expr = new Expr\Assign($var2, $var1);
        $this->infer([$expr, $var3], $refl);
        $this->assertTrue($var3->getAttribute('type')->equals(Type::fromString('mixed|\\C')));
    }

    public function test_Assign_annot()
    {
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $var1 = new Expr\Variable('b', ['annotations' => ['var' => [Tag::get('var', 'int $b')]]]);
        $var2 = new Expr\Variable('a', ['type' => Type::object_('\\C')]);
        $var3 = new Expr\Variable('b');
        $var4 = new Expr\Variable('b');
        $expr = new Expr\Assign($var3, $var2);
        $this->infer([$var1, $expr, $var4], $refl);
        $this->assertTrue($var4->getAttribute('type')->equals(Type::int_()));
    }

    public function test_Foreach()
    {
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $var1 = new Expr\Variable('a', ['type' => Type::fromString('int[]')]);
        $var2 = new Expr\Variable('b');
        $var3 = new Expr\Variable('b');
        $stmt = new Stmt\Foreach_($var1, $var2, ['stmts' => [$var3]]);
        $this->infer([$stmt], $refl);
        $this->assertTrue($var3->getAttribute('type')->equals(Type::fromString('mixed|int')));
    }
}
