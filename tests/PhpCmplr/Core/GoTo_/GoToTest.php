<?php

namespace Tests\PhpCmplr\Core\GoTo_;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Name;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\SourceFile\OffsetLocation;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\NameResolver\NameResolver;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\DocComment\Tag\Tag;
use PhpCmplr\Core\Reflection\ReflectionInterface;
use PhpCmplr\Core\Reflection\Reflection;
use PhpCmplr\Core\Reflection\Element\Method;
use PhpCmplr\Core\Reflection\Element\Property;
use PhpCmplr\Core\Reflection\Element\Class_;
use PhpCmplr\Core\TypeInferrer\TypeInferrer;
use PhpCmplr\Core\GoTo_\GoTo_;
use PhpCmplr\Core\GoTo_\GoToMemberDefinition;
use PhpCmplr\Core\GoTo_\GoToClassDefinition;

/**
 * @covers \PhpCmplr\Core\GoTo_\GoTo_
 * @covers \PhpCmplr\Core\GoTo_\GoToClassDefinition
 * @covers \PhpCmplr\Core\GoTo_\GoToMemberDefinition
 */
class GoToTest extends \PHPUnit_Framework_TestCase
{
    public function test_MethodCall()
    {
        $loc = new OffsetLocation('/qaz.php', 5);
        $method = (new Method())->setLocation($loc);
        $var1 = new Expr\Variable('a');
        $expr = new Expr\MethodCall($var1, 'f', [], ['reflections' => [$method]]);

        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser
            ->method('getNodesAtOffset')
            ->with($this->equalTo(5))
            ->willReturn([$expr]);
        $container->set('parser', $parser);
        $typeinfer = $this->getMockBuilder(TypeInferrer::class)->disableOriginalConstructor()->getMock();
        $container->set('typeinfer', $typeinfer);

        $container->set('goto.member_definition', new GoToMemberDefinition($container), ['goto']);
        $goto = new GoTo_($container);
        $this->assertSame([$loc], $goto->getGoToLocations(5));
    }

    public function test_StaticCall()
    {
        $loc = new OffsetLocation('/qaz.php', 5);
        $method = (new Method())->setLocation($loc)->setStatic(true);
        $cls = new Name\FullyQualified('A\\B\\C');
        $expr = new Expr\StaticCall($cls, 'f', [], ['reflections' => [$method]]);

        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser
            ->method('getNodesAtOffset')
            ->with($this->equalTo(5))
            ->willReturn([$cls, $expr]);
        $container->set('parser', $parser);
        $typeinfer = $this->getMockBuilder(TypeInferrer::class)->disableOriginalConstructor()->getMock();
        $container->set('typeinfer', $typeinfer);

        $container->set('goto.member_definition', new GoToMemberDefinition($container), ['goto']);
        $goto = new GoTo_($container);
        $this->assertSame([$loc], $goto->getGoToLocations(5));
    }

    public function test_New()
    {
        $loc = new OffsetLocation('/qaz.php', 5);
        $cls = (new Class_())->setLocation($loc);
        $name = new Name\FullyQualified('A\\B\\C');
        $expr = new Expr\New_($name, []);

        $container = new Container();
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser
            ->method('getNodesAtOffset')
            ->with($this->equalTo(5))
            ->willReturn([$name, $expr]);
        $container->set('parser', $parser);
        $nameResolver = $this->getMockBuilder(NameResolver::class)->disableOriginalConstructor()->getMock();
        $nameResolver
            ->method('run')
            ->willReturn(null);
        $container->set('name_resolver', $nameResolver);
        $reflection = $this->getMockBuilder(Reflection::class)->disableOriginalConstructor()->getMock();
        $reflection
            ->method('findClass')
            ->with($this->equalTo('\\A\\B\\C'))
            ->willReturn([$cls]);
        $reflection
            ->method('findFunction')
            ->with($this->equalTo('\\A\\B\\C'))
            ->willReturn([]);
        $reflection
            ->method('findConst')
            ->with($this->equalTo('\\A\\B\\C'))
            ->willReturn([]);
        $container->set('reflection', $reflection);

        $container->set('goto.name_definition', new GoToClassDefinition($container), ['goto']);
        $goto = new GoTo_($container);
        $this->assertSame([$loc], $goto->getGoToLocations(5));
    }
}
