<?php

namespace Tests\PhpCmplr\Completer\GoTo_;

use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Name;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile\OffsetLocation;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\NameResolver\NameResolver;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\DocComment\Tag\Tag;
use PhpCmplr\Completer\Reflection\ReflectionInterface;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\Element\Method;
use PhpCmplr\Completer\Reflection\Element\Property;
use PhpCmplr\Completer\Reflection\Element\Class_;
use PhpCmplr\Completer\TypeInferrer\TypeInferrer;
use PhpCmplr\Completer\GoTo_\GoToComponent;
use PhpCmplr\Completer\GoTo_\GoToMemberDefinitionComponent;
use PhpCmplr\Completer\GoTo_\GoToClassDefinitionComponent;

class GoToComponentTest extends \PHPUnit_Framework_TestCase
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
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $parser
            ->method('getNodesAtOffset')
            ->with($this->equalTo(5))
            ->willReturn([$cls, $expr]);
        $container->set('parser', $parser);
        $typeinfer = $this->getMockBuilder(TypeInferrer::class)->disableOriginalConstructor()->getMock();
        $container->set('typeinfer', $typeinfer);

        $container->set('goto.member_definition', new GoToMemberDefinitionComponent($container), ['goto']);
        $goto = new GoToComponent($container);
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

        $container->set('goto.name_definition', new GoToClassDefinitionComponent($container), ['goto']);
        $goto = new GoToComponent($container);
        $this->assertSame([$loc], $goto->getGoToLocations(5));
    }
}
