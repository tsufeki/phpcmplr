<?php

namespace Tests\PhpCmplr\Completer\Diagnostics;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Diagnostics\FixHelper;
use PhpCmplr\Completer\SourceFile\SourceFile;
use PhpCmplr\Completer\Parser\PositionsReconstructor;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\SourceFile\OffsetLocation;

/**
 * @coversDefaultClass \PhpCmplr\Completer\Diagnostics\FixHelper
 */
class FixHelperTest extends \PHPUnit_Framework_TestCase
{
    public function getFixHelper()
    {
        $container = new Container();
        return new FixHelper($container);
    }

    /**
     * @covers ::getIndentOfLines
     * @dataProvider getData_getIndentOfLines
     */
    public function test_getIndentOfLines($indent, $lines)
    {
        $helper = $this->getFixHelper();
        $this->assertSame($indent, $helper->getIndentOfLines($lines));
    }

    public function getData_getIndentOfLines()
    {
        return [
            [
                [1, 0],
                [
                    "\t  ",
                    "\t\t  ",
                    "\t  ",
                ],
            ],
            [
                [1, 2],
                [
                    "\t    ",
                    "\t   ",
                    "\t  ",
                ],
            ],
            [
                [0, 1],
                [
                    "  ",
                    "   ",
                    " ",
                ],
            ],
        ];
    }

    /**
     * @covers ::makeIndent
     */
    public function test_makeIndent()
    {
        $helper = $this->getFixHelper();
        $this->assertSame("\t\t   ", $helper->makeIndent([2, 3]));
    }

    /**
     * @covers ::getUseFix
     * @dataProvider getData_getUseFix
     */
    public function test_getUseFix($source, $result, $fqname, $offset)
    {
        $container = new Container();
        $container->set('file', $file = new SourceFile($container, 'qaz.php', $source));
        $container->set('parser.positions_reconstructor', new PositionsReconstructor($container));
        $parser = new Parser($container);
        $container->set('parser', $parser);
        $fixHelper = new FixHelper($container);
        $fix = $fixHelper->getUseFix($fqname, new OffsetLocation('qaz.php', $offset));
        $this->assertCount(1, $fix->getChunks());
        $chunk = $fix->getChunks()[0];
        $start = $chunk->getRange()->getStart()->getOffset($file);
        $end = $chunk->getRange()->getEnd()->getOffset($file);
        $this->assertSame(1, $start - $end);
    }

    public function getData_getUseFix()
    {
        return [
            [
                "namespace N;\n\nCC;\n",
                "namespace N;\n\nuse X;\n\nCC;\n",
                "X",
                14,
            ],
            [
                "namespace N;\n\nuse X;\n\nCC;\n",
                "namespace N;\n\nuse X;\nuse Y;\n\nCC;\n",
                "\\Y",
                22,
            ],
            [
                "namespace N {\n\n  use X;\n\n  CC;\n}\n",
                "namespace N {\n\n  use X;\n  use Y;\n\n  CC;\n}\n",
                "\\Y",
                27,
            ],
        ];
    }
}
