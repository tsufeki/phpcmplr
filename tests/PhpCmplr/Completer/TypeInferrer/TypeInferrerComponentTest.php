<?php

namespace Tests\PhpCmplr\Completer\TypeInferrer;

use PhpLenientParser\Node\Expr;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Parser\NameResolverComponent;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\TypeInferrer\TypeInferrerComponent;

class TypeInferrerComponentTest extends \PHPUnit_Framework_TestCase
{
    public function test_getType()
    {
        $expr = new Expr\Variable('qaz', ['type' => Type::int_()]);
        $container = new Container();
        $parser = $this->getMockBuilder(ParserComponent::class)->disableOriginalConstructor()->getMock();
        $parser->method('getNodes')->willReturn([]);
        $parser->method('getNodesAtOffset')->with($this->equalTo(7))->willReturn([$expr]);
        $resolver = $this->getMockBuilder(NameResolverComponent::class)->disableOriginalConstructor()->getMock();
        $container->set('parser', $parser);
        $container->set('name_resolver', $resolver);
        $this->assertTrue(Type::int_()->equals((new TypeInferrerComponent($container))->getType(7)));
    }

    protected function infer(array $nodes)
    {
        $container = new Container();
        $parser = $this->getMockBuilder(ParserComponent::class)->disableOriginalConstructor()->getMock();
        $parser->method('getNodes')->willReturn($nodes);
        $resolver = $this->getMockBuilder(NameResolverComponent::class)->disableOriginalConstructor()->getMock();
        $container->set('parser', $parser);
        $container->set('name_resolver', $resolver);
        (new TypeInferrerComponent($container))->run();
    }

    public function test_Assign()
    {
        $var1 = new Expr\Variable('a', ['type' => Type::int_()]);
        $var2 = new Expr\Variable('b');
        $expr = new Expr\Assign($var2, $var1);
        $this->infer([$expr]);
        $this->assertTrue($expr->getAttribute('type')->equals(Type::int_()));
    }

    public function test_Ternary()
    {
        $var1 = new Expr\Variable('a', ['type' => Type::array_()]);
        $var2 = new Expr\Variable('b', ['type' => Type::int_()]);
        $var3 = new Expr\Variable('c', ['type' => Type::float_()]);
        $expr = new Expr\Ternary($var1, $var2, $var3);
        $this->infer([$expr]);
        $this->assertTrue($expr->getAttribute('type')->equals(Type::alternatives([Type::int_(), Type::float_()])));
        $expr = new Expr\Ternary($var1, null, $var3);
        $this->infer([$expr]);
        $this->assertTrue($expr->getAttribute('type')->equals(Type::alternatives([Type::array_(), Type::float_()])));
    }
}
