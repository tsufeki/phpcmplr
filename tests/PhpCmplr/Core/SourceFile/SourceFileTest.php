<?php

namespace Tests\PhpCmplr\Core\SourceFile;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\SourceFile\SourceFile;

/**
 * @covers \PhpCmplr\Core\SourceFile\SourceFile
 */
class SourceFileTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        return new SourceFile(new Container(), $path, $contents);
    }

    /**
     * @dataProvider getData_getOffset
     */
    public function test_getOffset($offset, $text, $line, $col)
    {
        $this->assertSame($offset, $this->loadFile($text)->getOffset($line, $col));
    }

    public function getData_getOffset()
    {
        return [
            [0, "", 1, 1],
            [0, "", 2, 1],
            [0, "", 1, 3],

            [1, "qaz", 1, 2],
            [2, "qaz\nwsx", 1, 3],
            [4, "qaz\nwsx", 2, 1],
            [3, "qaz\nwsx", 1, 4],
            [6, "qaz\nwsx", 2, 3],

            [0, "\nqaz\n\nwsx\n", 1, 1],
            [2, "\nqaz\n\nwsx\n", 2, 2],
            [8, "\nqaz\n\nwsx\n", 4, 3],
            [9, "\nqaz\n\nwsx\n", 5, 1],
        ];
    }

    /**
     * @dataProvider getData_getLineAndColumn
     */
    public function test_getLineAndColumn($line, $col, $text, $offset)
    {
        $this->assertSame([$line, $col], $this->loadFile($text)->getLineAndColumn($offset));
    }

    public function getData_getLineAndColumn()
    {
        return [
            [1, 1, "", 0],
            [1, 1, "", 1],
            [1, 1, "", 2],

            [1, 2, "qaz", 1],
            [1, 3, "qaz", 2],
            [1, 2, "qaz\n", 1],
            [1, 3, "qaz\n", 2],
            [1, 4, "qaz\n", 3],

            [1, 2, "qaz\nwsx", 1],
            [1, 4, "qaz\nwsx", 3],
            [2, 2, "qaz\nwsx", 5],
            [2, 3, "qaz\nwsx", 6],

            [1, 1, "\nqaz\n\nwsx\n", 0],
            [2, 2, "\nqaz\n\nwsx\n", 2],
            [4, 3, "\nqaz\n\nwsx\n", 8],
            [4, 4, "\nqaz\n\nwsx\n", 9],
        ];
    }

    /**
     * @dataProvider getData_getLines
     */
    public function test_getLines(array $lines, $text)
    {
        $this->assertSame($lines, iterator_to_array($this->loadFile($text)->getLines()));
    }

    public function getData_getLines()
    {
        return [
            [[], ""],
            [["a"], "a"],
            [["a\n"], "a\n"],
            [["aa\n", "b"], "aa\nb"],
            [["a\n", "bb\n"], "a\nbb\n"],
            [["a\n", "\n"], "a\n\n"],
            [["a\n", "\n", "a"], "a\n\na"],
            [["\n", "aa"], "\naa"],
        ];
    }

    public function test_getLine()
    {
        $this->assertSame("bb\n", $this->loadFile("aaa\nbb\nc\n")->getLine(2));
    }
}
