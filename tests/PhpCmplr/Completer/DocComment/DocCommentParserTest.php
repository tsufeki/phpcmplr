<?php

namespace Tests\PhpCmplr\Completer\DocComment;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile\SourceFile;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\DocComment\DocCommentParser;
use PhpCmplr\Completer\DocComment\Tag\Tag;
use PhpCmplr\Completer\DocComment\Tag\VarTag;
use PhpCmplr\Completer\DocComment\Tag\ParamTag;
use PhpCmplr\Completer\DocComment\Tag\ReturnTag;
use PhpCmplr\Completer\DocComment\Tag\ThrowsTag;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Type\ArrayType;
use PhpCmplr\Completer\Type\ObjectType;
use PhpCmplr\Completer\Parser\PositionsReconstructor;

/**
 * @covers \PhpCmplr\Completer\DocComment\DocCommentParser
 * @covers \PhpCmplr\Completer\DocComment\Tag\IdentifierTag
 * @covers \PhpCmplr\Completer\DocComment\Tag\ParamTag
 * @covers \PhpCmplr\Completer\DocComment\Tag\ReturnTag
 * @covers \PhpCmplr\Completer\DocComment\Tag\Tag
 * @covers \PhpCmplr\Completer\DocComment\Tag\ThrowsTag
 * @covers \PhpCmplr\Completer\DocComment\Tag\TypedTag
 * @covers \PhpCmplr\Completer\DocComment\Tag\VarTag
 * @covers \PhpCmplr\Completer\Type\AlternativesType
 * @covers \PhpCmplr\Completer\Type\ArrayType
 * @covers \PhpCmplr\Completer\Type\ObjectType
 * @covers \PhpCmplr\Completer\Type\Type
 */
class DocCommentParserTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', new SourceFile($container, $path, $contents));
        $container->set('parser', $parser = new Parser($container));
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        return [$parser, new DocCommentParser($container)];
    }

    public function test_run()
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
        $this->assertSame(10, $annot['var'][0]->getStartPos());
        $this->assertSame(20, $annot['var'][0]->getEndPos());
    }

    public function test_run_multi()
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

    public function test_run_invalidType()
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

    public function test_run_throws()
    {
        list($parser, $doc) = $this->loadFile('<?php /** @throws \Exception */ function f() {}');
        $doc->run();
        $nodes = $parser->getNodes();
        $annot = $nodes[0]->getAttribute('annotations');
        $this->assertCount(1, $annot);
        $this->assertCount(1, $annot['throws']);
        $this->assertInstanceOf(ThrowsTag::class, $annot['throws'][0]);
        $this->assertSame('throws', $annot['throws'][0]->getName());
        $this->assertSame('\\Exception', $annot['throws'][0]->getText());
        $this->assertInstanceOf(ObjectType::class, $annot['throws'][0]->getType());
        $this->assertSame('\\Exception', $annot['throws'][0]->getType()->getClass());
        $this->assertNull($annot['throws'][0]->getDescription());
    }

    public function test_run_unknownTag()
    {
        list($parser, $doc) = $this->loadFile('<?php /** @version 17 */ function f() {}');
        $doc->run();
        $nodes = $parser->getNodes();
        $annot = $nodes[0]->getAttribute('annotations');
        $this->assertCount(1, $annot);
        $this->assertCount(1, $annot['version']);
        $this->assertInstanceOf(Tag::class, $annot['version'][0]);
        $this->assertSame('version', $annot['version'][0]->getName());
        $this->assertSame('17', $annot['version'][0]->getText());
    }

    public function test_run_description()
    {
        $source = <<<'END'
<?php
/**
 * @param resource|callable|self|$this|array|object|mixed|parent|self
 * @return float|null Qaz
 * @throws \Exception Wsx
 */
function f() {}
END;
        list($parser, $doc) = $this->loadFile($source);
        $doc->run();
        $nodes = $parser->getNodes();
        $annot = $nodes[0]->getAttribute('annotations');
        $this->assertCount(3, $annot);
        $this->assertSame('Qaz', $annot['return'][0]->getDescription());
        $this->assertSame('Wsx', $annot['throws'][0]->getDescription());
    }
}
