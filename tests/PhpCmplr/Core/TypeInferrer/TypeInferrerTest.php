<?php

namespace Tests\PhpCmplr\Core\TypeInferrer;

use PhpParser\Node\Expr;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\NameResolver\NameResolver;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\TypeInferrer\TypeInferrer;

/**
 * @covers \PhpCmplr\Core\TypeInferrer\TypeInferrer
 */
class TypeInferrerTest extends \PHPUnit_Framework_TestCase
{
    public function test_getType()
    {
        $expr = new Expr\Variable('qaz', ['type' => Type::int_()]);
        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser->method('getNodes')->willReturn([]);
        $parser->method('getNodesAtOffset')->with($this->equalTo(7))->willReturn([$expr]);
        $resolver = $this->getMockBuilder(NameResolver::class)->disableOriginalConstructor()->getMock();
        $container->set('parser', $parser);
        $container->set('name_resolver', $resolver);
        $this->assertTrue(Type::int_()->equals((new TypeInferrer($container))->getType(7)));
    }
}
