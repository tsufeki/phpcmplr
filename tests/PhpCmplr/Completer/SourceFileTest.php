<?php

namespace Tests\PhpCmplr\Completer;

use PhpCmplr\Completer\SourceFile;

class SourceFileTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $file = new SourceFile($path);
        return $file->load($contents);
    }

    public function test_getOffset()
    {
        $this->assertSame(0, $this->loadFile("")->getOffset(1, 1));
        $this->assertSame(0, $this->loadFile("")->getOffset(2, 1));
        $this->assertSame(0, $this->loadFile("")->getOffset(1, 3));

        $this->assertSame(1, $this->loadFile("qaz")->getOffset(1, 2));
        $this->assertSame(2, $this->loadFile("qaz\nwsx")->getOffset(1, 3));
        $this->assertSame(4, $this->loadFile("qaz\nwsx")->getOffset(2, 1));
        $this->assertSame(3, $this->loadFile("qaz\nwsx")->getOffset(1, 4));
        $this->assertSame(6, $this->loadFile("qaz\nwsx")->getOffset(2, 3));

        $this->assertSame(0, $this->loadFile("\nqaz\n\nwsx\n")->getOffset(1, 1));
        $this->assertSame(2, $this->loadFile("\nqaz\n\nwsx\n")->getOffset(2, 2));
        $this->assertSame(8, $this->loadFile("\nqaz\n\nwsx\n")->getOffset(4, 3));
        $this->assertSame(9, $this->loadFile("\nqaz\n\nwsx\n")->getOffset(5, 1));
    }

    public function test_getLineAndColumn()
    {
        $this->assertSame([1, 1], $this->loadFile("")->getLineAndColumn(0));
        $this->assertSame([1, 1], $this->loadFile("")->getLineAndColumn(1));
        $this->assertSame([1, 1], $this->loadFile("")->getLineAndColumn(2));

        $this->assertSame([1, 2], $this->loadFile("qaz")->getLineAndColumn(1));
        $this->assertSame([1, 3], $this->loadFile("qaz")->getLineAndColumn(2));
        $this->assertSame([1, 2], $this->loadFile("qaz\n")->getLineAndColumn(1));
        $this->assertSame([1, 3], $this->loadFile("qaz\n")->getLineAndColumn(2));
        $this->assertSame([1, 4], $this->loadFile("qaz\n")->getLineAndColumn(3));

        $this->assertSame([1, 2], $this->loadFile("qaz\nwsx")->getLineAndColumn(1));
        $this->assertSame([1, 4], $this->loadFile("qaz\nwsx")->getLineAndColumn(3));
        $this->assertSame([2, 2], $this->loadFile("qaz\nwsx")->getLineAndColumn(5));
        $this->assertSame([2, 3], $this->loadFile("qaz\nwsx")->getLineAndColumn(6));

        $this->assertSame([1, 1], $this->loadFile("\nqaz\n\nwsx\n")->getLineAndColumn(0));
        $this->assertSame([2, 2], $this->loadFile("\nqaz\n\nwsx\n")->getLineAndColumn(2));
        $this->assertSame([4, 3], $this->loadFile("\nqaz\n\nwsx\n")->getLineAndColumn(8));
        $this->assertSame([4, 4], $this->loadFile("\nqaz\n\nwsx\n")->getLineAndColumn(9));
    }

    public function test_getDiagnostics()
    {
        $file = $this->loadFile('<?php '."\n\n".'$a = 7 + *f("wsx");', 'qaz.php');
        $diags = $file->getDiagnostics();
        $this->assertSame(1, count($diags));
        $this->assertSame('qaz.php', $diags[0]->getFile()->getPath());
        $this->assertSame($file, $diags[0]->getFile());
        $this->assertSame(17, $diags[0]->getStart());
        $this->assertSame(17, $diags[0]->getEnd());
        $this->assertSame([3, 10], $file->getLineAndColumn($diags[0]->getStart()));
        $this->assertSame([3, 10], $file->getLineAndColumn($diags[0]->getEnd()));
        $this->assertSame("Syntax error, unexpected '*'", $diags[0]->getDescription());
    }
}
