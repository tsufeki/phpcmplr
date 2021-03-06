<?php

namespace Tests\PhpCmplr\Core\Completer;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Name;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\Reflection\Reflection;
use PhpCmplr\Core\Reflection\Element\ClassLike;
use PhpCmplr\Core\Reflection\Element\Class_;
use PhpCmplr\Core\Reflection\Element\Method;
use PhpCmplr\Core\Reflection\Element\Property;
use PhpCmplr\Core\Reflection\Element\ClassConst;
use PhpCmplr\Core\TypeInferrer\TypeInferrer;
use PhpCmplr\Core\Completer\Completer;
use PhpCmplr\Core\Completer\MemberCompleter;
use PhpCmplr\Core\Reflection\Element\Param;

/**
 * @covers \PhpCmplr\Core\Completer\MemberCompleter
 */
class MemberCompleterTest extends \PHPUnit_Framework_TestCase
{
    public function complete(array $nodes, array $methods = [], array $props = [], array $consts = [])
    {
        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parser
            ->method('getNodesAtOffset')
            ->with($this->equalTo(5), $this->equalTo(true))
            ->willReturn($nodes);
        $container->set('parser', $parser);
        $typeinfer = $this->getMockBuilder(TypeInferrer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $typeinfer
            ->method('run')
            ->willReturn(null);
        $container->set('typeinfer', $typeinfer);
        $reflection = $this->getMockBuilder(Reflection::class)
            ->setMethods(['findAllMethods', 'findAllProperties', 'findAllClassConsts', 'isTrait'])
            ->disableOriginalConstructor()
            ->getMock();
        $reflection
            ->method('findAllMethods')
            ->with($this->equalTo('\\C'))
            ->willReturn($methods);
        $reflection
            ->method('findAllProperties')
            ->with($this->equalTo('\\C'))
            ->willReturn($props);
        $reflection
            ->method('findAllClassConsts')
            ->with($this->equalTo('\\C'))
            ->willReturn($consts);
        $reflection
            ->method('isTrait')
            ->with($this->equalTo('\\C'))
            ->willReturn(false);
        $container->set('reflection', $reflection);

        $completer = new MemberCompleter($container);
        return $completer->complete(5);
    }

    public function test_MethodCall()
    {
        $class = (new Class_())
            ->setName('\\C');
        $method = (new Method())
            ->setName('qaz')
            ->setClass($class)
            ->setDocReturnType(Type::int_())
            ->addParam((new Param())
                ->setName('$a')
                ->setDocType(Type::string_()));
        $var1 = new Expr\Variable('a', ['type' => Type::object_('\\C')]);
        $id = 'q';
        $expr = new Expr\MethodCall($var1, $id, []);

        $completions = $this->complete([$expr], [$method]);

        $this->assertCount(1, $completions);
        $this->assertSame('qaz(', $completions[0]->getInsertion());
        $this->assertSame('qaz(', $completions[0]->getDisplay());
        $this->assertSame('method', $completions[0]->getKind());
        $this->assertSame('string $a): int', $completions[0]->getExtendedDisplay());
    }

    public function test_MethodCall_private()
    {
        $class = (new Class_())
            ->setName('\\C');
        $method = (new Method())
            ->setName('qaz')
            ->setClass($class)
            ->setDocReturnType(Type::int_())
            ->setAccessibility(ClassLike::M_PRIVATE);
        $var1 = new Expr\Variable('a', ['type' => Type::object_('\\C')]);
        $id = 'q';
        $expr = new Expr\MethodCall($var1, $id, []);

        $completions = $this->complete([$expr], [$method]);

        $this->assertCount(0, $completions);
    }

    public function test_MethodCall_private_insideClass()
    {
        $class = (new Class_())
            ->setName('\\C');
        $method = (new Method())
            ->setName('qaz')
            ->setClass($class)
            ->setDocReturnType(Type::int_())
            ->setAccessibility(ClassLike::M_PRIVATE);
        $var1 = new Expr\Variable('a', ['type' => Type::object_('\\C')]);
        $id = 'q';
        $expr = new Expr\MethodCall($var1, $id, []);
        $ctxcls = new Stmt\Class_('C');
        $ctxcls->setAttribute('namespacedName', '\\C');

        $completions = $this->complete([$expr, $ctxcls], [$method]);

        $this->assertCount(1, $completions);
    }

    public function test_MethodCall_self()
    {
        $class = (new Class_())
            ->setName('\\C');
        $method = (new Method())
            ->setName('qaz')
            ->setClass($class)
            ->setDocReturnType(Type::int_());
        $cls = new Name('self');
        $cls->setAttribute('resolved', new Name\FullyQualified('C'));
        $id = 'q';
        $expr = new Expr\StaticCall($cls, $id, []);
        $ctxmeth = new Stmt\ClassMethod('mm');
        $ctxcls = new Stmt\Class_('C');
        $ctxcls->setAttribute('namespacedName', '\\C');

        $completions = $this->complete([$expr, $ctxmeth, $ctxcls], [$method]);

        $this->assertCount(1, $completions);
    }

    public function test_MethodCall_magic()
    {
        $class = (new Class_())
            ->setName('\\C');
        $method = (new Method())
            ->setName('__construct')
            ->setClass($class);
        $var1 = new Expr\Variable('a', ['type' => Type::object_('\\C')]);
        $id = 'q';
        $expr = new Expr\MethodCall($var1, $id, []);

        $completions = $this->complete([$expr], [$method]);

        $this->assertCount(0, $completions);
    }

    public function test_StaticCall()
    {
        $class = (new Class_())
            ->setName('\\C');
        $method = (new Method())
            ->setName('qaz')
            ->setClass($class)
            ->setStatic(true)
            ->setDocReturnType(Type::object_('\\X\\Y'));
        $prop = (new Property())
            ->setName('$wsx')
            ->setClass($class)
            ->setStatic(true)
            ->setType(Type::string_());
        $const = (new ClassConst())
            ->setName('EDC')
            ->setClass($class);

        $cls = new Name\FullyQualified('C');
        $id = 'q';
        $expr = new Expr\StaticCall($cls, $id, []);

        $completions = $this->complete([$expr], [$method], [$prop], [$const]);

        $this->assertCount(3, $completions);

        $this->assertSame('qaz()', $completions[0]->getInsertion());
        $this->assertSame('qaz()', $completions[0]->getDisplay());
        $this->assertSame('static_method', $completions[0]->getKind());
        $this->assertSame(': Y', $completions[0]->getExtendedDisplay());

        $this->assertSame('EDC', $completions[1]->getInsertion());
        $this->assertSame('EDC', $completions[1]->getDisplay());
        $this->assertSame('class_const', $completions[1]->getKind());

        $this->assertSame('$wsx', $completions[2]->getInsertion());
        $this->assertSame('$wsx', $completions[2]->getDisplay());
        $this->assertSame('static_property', $completions[2]->getKind());
        $this->assertSame('string', $completions[2]->getExtendedDisplay());
    }
}
