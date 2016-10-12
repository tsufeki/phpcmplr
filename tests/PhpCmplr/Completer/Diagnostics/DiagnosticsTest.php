<?php

namespace Tests\PhpCmplr\Completer\Diagnostics;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile\SourceFile;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\Diagnostics\Diagnostics;

class DiagnosticsTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', $file = new SourceFile($container, $path, $contents));
        $container->set('parser', new Parser($container), ['diagnostics']);
        return [$file, new Diagnostics($container)];
    }

    public function test_getDiagnostics()
    {
        list($file, $diagsComponent) = $this->loadFile('<?php '."\n\n".'$a = 7 + *f("wsx");', 'qaz.php');
        $diags = $diagsComponent->getDiagnostics();
        $this->assertCount(1, $diags);
        $this->assertCount(1, $diags[0]->getRanges());
        $this->assertCount(1, $diags[0]->getFixes());
        $range = $diags[0]->getRanges()[0];
        $this->assertSame('qaz.php', $diags[0]->getPath());
        $this->assertSame(17, $range->getStart()->getOffset());
        $this->assertSame(17, $range->getEnd()->getOffset());
        $this->assertSame([3, 10], $range->getStart()->getLineAndColumn($file));
        $this->assertSame([3, 10], $range->getEnd()->getLineAndColumn($file));
        $this->assertSame("Syntax error, unexpected '*'", $diags[0]->getDescription());
    }
}
