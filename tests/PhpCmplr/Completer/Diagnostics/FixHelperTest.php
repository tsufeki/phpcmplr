<?php

namespace Tests\PhpCmplr\Completer\Diagnostics;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Diagnostics\FixHelper;

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
}
