<?php

namespace Tests\PhpCmplr\Completer\Parser;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Parser\DocCommentComponent;
use PhpCmplr\Completer\Parser\DocTag\VarTag;
use PhpCmplr\Completer\Parser\DocTag\ParamTag;
use PhpCmplr\Completer\Parser\DocTag\ReturnTag;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\Parser\DocTag\ArrayType;
use PhpCmplr\Completer\Parser\DocTag\ObjectType;

class DocCommentComponentTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', $parser = new ParserComponent($container));
        return [$parser, new DocCommentComponent($container)];
    }

    public function test_parse()
    {
        list($parser, $doc) = $this->loadFile('<?php /** @var string */ $x;');
        $doc->run();
        $nodes = $parser->getNodes();
        $annot = $nodes[0]->getAttribute('annotations');
        $this->assertCount(1, $annot);
        $this->assertCount(1, $annot['var']);
        $this->assertInstanceOf(VarTag::class, $annot['var'][0]);
        $this->assertSame('var', $annot['var'][0]->getName());
        $this->assertSame('string', $annot['var'][0]->getText());
        $this->assertInstanceOf(Type::class, $annot['var'][0]->getType());
        $this->assertSame('string', $annot['var'][0]->getType()->getName());
        $this->assertNull($annot['var'][0]->getIdentifier());
        $this->assertNull($annot['var'][0]->getDescription());
    }

    public function test_parse_multi()
    {
        $source = <<<'END'
<?php
    /**
     * short short.
     *
     * long long    
     *     Long
     * @param int[] $x arg
     * arg
     * @param bool $y
     *
     * @return A\B
     */
    function f() {}
END;
        list($parser, $doc) = $this->loadFile($source);
        $doc->run();
        $nodes = $parser->getNodes();
        $this->assertSame('short short.', $nodes[0]->getAttribute('shortDescription'));
        $this->assertSame("long long\n    Long", $nodes[0]->getAttribute('longDescription'));

        $annot = $nodes[0]->getAttribute('annotations');
        $this->assertCount(2, $annot);
        $this->assertCount(2, $annot['param']);

        $this->assertInstanceOf(ParamTag::class, $annot['param'][0]);
        $this->assertSame('param', $annot['param'][0]->getName());
        $this->assertSame("int[] \$x arg\narg", $annot['param'][0]->getText());
        $this->assertInstanceOf(ArrayType::class, $annot['param'][0]->getType());
        $this->assertSame('array', $annot['param'][0]->getType()->getName());
        $this->assertInstanceOf(Type::class, $annot['param'][0]->getType()->getValueType());
        $this->assertSame('int', $annot['param'][0]->getType()->getValueType()->getName());
        $this->assertSame('$x', $annot['param'][0]->getIdentifier());
        $this->assertSame("arg\narg", $annot['param'][0]->getDescription());

        $this->assertInstanceOf(ParamTag::class, $annot['param'][1]);
        $this->assertSame('param', $annot['param'][1]->getName());
        $this->assertSame('bool $y', $annot['param'][1]->getText());
        $this->assertInstanceOf(Type::class, $annot['param'][1]->getType());
        $this->assertSame('bool', $annot['param'][1]->getType()->getName());
        $this->assertSame('$y', $annot['param'][1]->getIdentifier());
        $this->assertNull($annot['param'][1]->getDescription());

        $this->assertCount(1, $annot['return']);

        $this->assertInstanceOf(ReturnTag::class, $annot['return'][0]);
        $this->assertSame('return', $annot['return'][0]->getName());
        $this->assertSame('A\\B', $annot['return'][0]->getText());
        $this->assertInstanceOf(ObjectType::class, $annot['return'][0]->getType());
        $this->assertSame('object', $annot['return'][0]->getType()->getName());
        $this->assertSame('A\\B', $annot['return'][0]->getType()->getClass());
        $this->assertNull($annot['return'][0]->getDescription());
    }

    public function test_parse_invalidType()
    {
        list($parser, $doc) = $this->loadFile('<?php /** @var wrong%type */ $x;');
        $doc->run();
        $nodes = $parser->getNodes();
        $annot = $nodes[0]->getAttribute('annotations');
        $this->assertCount(1, $annot);
        $this->assertCount(1, $annot['var']);
        $this->assertInstanceOf(VarTag::class, $annot['var'][0]);
        $this->assertInstanceOf(Type::class, $annot['var'][0]->getType());
        $this->assertSame('mixed', $annot['var'][0]->getType()->getName());
        $this->assertNull($annot['var'][0]->getIdentifier());
        $this->assertNull($annot['var'][0]->getDescription());
    }
}
