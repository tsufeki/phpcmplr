<?php

namespace Tests\PhpCmplr\Completer;

use PhpCmplr\Completer\SourceFile;

class SourceFileTest extends \PHPUnit_Framework_TestCase
{
    protected $sourceFile;

    public function setUp()
    {
        $this->sourceFile = new SourceFile('qaz.php');
        $this->sourceFile->load('<?php '."\n\n".'$a = 7 + *f("wsx");');
    }

    public function testDiagnostics()
    {
        $diags = $this->sourceFile->getDiagnostics();
        $this->assertSame(1, count($diags));
        $this->assertSame('qaz.php', $diags[0]->getFile()->getPath());
        $this->assertSame(3, $diags[0]->getStart()->getLine());
        $this->assertSame(10, $diags[0]->getStart()->getColumn());
        $this->assertSame(3, $diags[0]->getEnd()->getLine());
        $this->assertSame(10, $diags[0]->getEnd()->getColumn());
        $this->assertSame("Syntax error, unexpected '*'", $diags[0]->getDescription());
    }
}
