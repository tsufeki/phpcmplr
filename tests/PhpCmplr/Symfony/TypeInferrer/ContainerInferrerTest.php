<?php

namespace Tests\PhpCmplr\Symfony\TypeInferrer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Node\Name;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\NameResolver\NameResolver;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\Reflection\Reflection;
use PhpCmplr\Core\Reflection\Element\Class_;
use PhpCmplr\Core\Reflection\Element\Method;
use PhpCmplr\Symfony\Config\Config;
use PhpCmplr\Symfony\Config\ConfigLoader;
use PhpCmplr\Symfony\TypeInferrer\ContainerInferrer;
use PhpCmplr\Symfony\Config\Service;

/**
 * @covers \PhpCmplr\Symfony\TypeInferrer\ContainerInferrer
 */
class ContainerInferrerTest extends \PHPUnit_Framework_TestCase
{
    protected function infer(array $nodes, $reflection, array $services)
    {
        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser
            ->method('getNodes')
            ->willReturn($nodes);
        $resolver = new NameResolver($container);
        $container->set('parser', $parser);
        $container->set('name_resolver', $resolver);
        $container->set('reflection', $reflection);
        $config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $config
            ->method('getService')
            ->will($this->returnValueMap($services));
        $configLoader = $this->getMockBuilder(ConfigLoader::class)->disableOriginalConstructor()->getMock();
        $configLoader
            ->method('getConfig')
            ->willReturn($config);
        $container->set('symfony.config', $configLoader);
        $resolver->run();
        (new ContainerInferrer($container))->run();
    }

    public function test_MethodCall()
    {
        $method = (new Method())
            ->setStatic(false)
            ->setClass(((new Class_())->setName('\\Z')));
        $refl = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $refl
            ->expects($this->once())
            ->method('isSubclass')
            ->with(
                $this->equalTo('\\Z'),
                $this->equalTo(ContainerInferrer::CONTAINER_GET_CLASSES[0]))
            ->willReturn(true);
        $service = (new Service('qaz'))->setClass('\\X');
        $var1 = new Expr\Variable('a', ['type' => Type::object_(ContainerInferrer::CONTAINER_GET_CLASSES[0])]);
        $expr = new Expr\MethodCall($var1, 'get',
            [new Node\Arg(new Scalar\String_('qaz'))],
            ['reflections' => [$method]]
        );
        $this->infer([$expr], $refl, [['qaz', $service]]);
        $this->assertTrue($expr->getAttribute('type')->equals(Type::object_('\\X')));
    }
}
