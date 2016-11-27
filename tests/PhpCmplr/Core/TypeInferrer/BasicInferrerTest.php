<?php

namespace Tests\PhpCmplr\Core\TypeInferrer;

use PhpParser\Node\Expr;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\NameResolver\NameResolver;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\TypeInferrer\TypeInferrer;
use PhpCmplr\Core\TypeInferrer\BasicInferrer;

/**
 * @covers \PhpCmplr\Core\TypeInferrer\BasicInferrer
 */
class BasicInferrerTest extends \PHPUnit_Framework_TestCase
{
    protected function infer(array $nodes)
    {
        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser->method('getNodes')->willReturn($nodes);
        $resolver = $this->getMockBuilder(NameResolver::class)->disableOriginalConstructor()->getMock();
        $container->set('parser', $parser);
        $container->set('name_resolver', $resolver);
        $container->set('typeinfer.basic', new BasicInferrer($container), ['typeinfer.visitor']);
        (new TypeInferrer($container))->run();
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
