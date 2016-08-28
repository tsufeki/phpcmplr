<?php

namespace Tests\PhpCmplr\Completer\Completer;

use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Name;
use PhpLenientParser\Node\Identifier;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\Element\Method;
use PhpCmplr\Completer\Reflection\Element\Property;
use PhpCmplr\Completer\Reflection\Element\ClassConst;
use PhpCmplr\Completer\TypeInferrer\TypeInferrer;
use PhpCmplr\Completer\Completer\CompleterComponent;

class CompleterComponentTest extends \PHPUnit_Framework_TestCase
{
    public function test_MethodCall()
    {
        $method = (new Method())->setName('qaz')->setDocReturnType(Type::int_());
        $var1 = new Expr\Variable('a', ['type' => Type::object_('\\C')]);
        $id = new Identifier('q');
        $expr = new Expr\MethodCall($var1, $id, []);

        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser
            ->method('getNodesAtOffset')
            ->with($this->equalTo(5), $this->equalTo(true))
            ->willReturn([$id, $expr]);
        $container->set('parser', $parser);
        $typeinfer = $this->getMockBuilder(TypeInferrer::class)->disableOriginalConstructor()->getMock();
        $typeinfer
            ->method('run')
            ->willReturn(null);
        $container->set('typeinfer', $typeinfer);
        $reflection = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $reflection
            ->method('findAllMethods')
            ->with($this->equalTo('\\C'))
            ->willReturn([$method]);
        $reflection
            ->method('findAllProperties')
            ->with($this->equalTo('\\C'))
            ->willReturn([]);
        $container->set('reflection', $reflection);

        $completer = new CompleterComponent($container);
        $completions = $completer->complete(5);

        $this->assertCount(1, $completions);
        $this->assertSame('qaz', $completions[0]->getInsertion());
        $this->assertSame('qaz()', $completions[0]->getDisplay());
        $this->assertSame('method', $completions[0]->getKind());
        $this->assertSame('int', $completions[0]->getType());
    }

    public function test_StaticCall()
    {
        $method = (new Method())->setName('qaz')->setStatic(true)->setDocReturnType(Type::object_('\\X\\Y'));
        $prop = (new Property())->setName('$wsx')->setStatic(true)->setType(Type::string_());
        $const = (new ClassConst())->setName('EDC');

        $cls = new Name\FullyQualified('C');
        $id = new Identifier('q');
        $expr = new Expr\StaticCall($cls, $id, []);

        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser
            ->method('getNodesAtOffset')
            ->with($this->equalTo(5), $this->equalTo(true))
            ->willReturn([$id, $expr]);
        $container->set('parser', $parser);
        $typeinfer = $this->getMockBuilder(TypeInferrer::class)->disableOriginalConstructor()->getMock();
        $typeinfer
            ->method('run')
            ->willReturn(null);
        $container->set('typeinfer', $typeinfer);
        $reflection = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $reflection
            ->method('findAllMethods')
            ->with($this->equalTo('\\C'))
            ->willReturn([$method]);
        $reflection
            ->method('findAllProperties')
            ->with($this->equalTo('\\C'))
            ->willReturn([$prop]);
        $reflection
            ->method('findAllClassConsts')
            ->with($this->equalTo('\\C'))
            ->willReturn([$const]);
        $container->set('reflection', $reflection);

        $completer = new CompleterComponent($container);
        $completions = $completer->complete(5);

        $this->assertCount(3, $completions);

        $this->assertSame('qaz', $completions[0]->getInsertion());
        $this->assertSame('qaz()', $completions[0]->getDisplay());
        $this->assertSame('static_method', $completions[0]->getKind());
        $this->assertSame('Y', $completions[0]->getType());

        $this->assertSame('EDC', $completions[1]->getInsertion());
        $this->assertSame('EDC', $completions[1]->getDisplay());
        $this->assertSame('class_const', $completions[1]->getKind());

        $this->assertSame('$wsx', $completions[2]->getInsertion());
        $this->assertSame('$wsx', $completions[2]->getDisplay());
        $this->assertSame('static_property', $completions[2]->getKind());
        $this->assertSame('string', $completions[2]->getType());
    }
}
