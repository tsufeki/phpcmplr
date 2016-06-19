<?php

namespace Tests\PhpCmplr\Completer\GoTo_;

use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Name;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\OffsetLocation;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Parser\NameResolverComponent;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\Parser\DocTag\DocTag;
use PhpCmplr\Completer\Reflection\ReflectionComponentInterface;
use PhpCmplr\Completer\Reflection\ReflectionComponent;
use PhpCmplr\Completer\Reflection\Method;
use PhpCmplr\Completer\Reflection\Property;
use PhpCmplr\Completer\TypeInferrer\TypeInferrerComponent;
use PhpCmplr\Completer\GoTo_\GoToComponent;
use PhpCmplr\Completer\GoTo_\GoToMemberDefinitionComponent;

class GoToComponentTest extends \PHPUnit_Framework_TestCase
{
    public function test_MethodCall()
    {
        $loc = new OffsetLocation('/qaz.php', 5);
        $method = (new Method())->setLocation($loc);
        $var1 = new Expr\Variable('a');
        $expr = new Expr\MethodCall($var1, 'f', [], ['reflections' => [$method]]);

        $container = new Container();
        $parser = $this->getMockBuilder(ParserComponent::class)->disableOriginalConstructor()->getMock();
        $parser
            ->method('getNodesAtOffset')
            ->with($this->equalTo(5))
            ->willReturn([$expr]);
        $container->set('parser', $parser);
        $typeinfer = $this->getMockBuilder(TypeInferrerComponent::class)->disableOriginalConstructor()->getMock();
        $container->set('typeinfer', $typeinfer);

        $container->set('goto.member_definition', new GoToMemberDefinitionComponent($container), ['goto']);
        $goto = new GoToComponent($container);
        $this->assertSame([$loc], $goto->getGoToLocations(5));
    }

    public function test_StaticCall()
    {
        $loc = new OffsetLocation('/qaz.php', 5);
        $method = (new Method())->setLocation($loc)->setStatic(true);
        $cls = new Name\FullyQualified('A\\B\\C');
        $expr = new Expr\StaticCall($cls, 'f', [], ['reflections' => [$method]]);

        $container = new Container();
        $parser = $this->getMockBuilder(ParserComponent::class)->disableOriginalConstructor()->getMock();
        $parser
            ->method('getNodesAtOffset')
            ->with($this->equalTo(5))
            ->willReturn([$cls, $expr]);
        $container->set('parser', $parser);
        $typeinfer = $this->getMockBuilder(TypeInferrerComponent::class)->disableOriginalConstructor()->getMock();
        $container->set('typeinfer', $typeinfer);

        $container->set('goto.member_definition', new GoToMemberDefinitionComponent($container), ['goto']);
        $goto = new GoToComponent($container);
        $this->assertSame([$loc], $goto->getGoToLocations(5));
    }
}
