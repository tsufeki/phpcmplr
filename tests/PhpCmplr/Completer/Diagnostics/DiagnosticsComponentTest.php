<?php

namespace Tests\PhpCmplr\Completer\Diagnostics;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\SourceFile;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Diagnostics\DiagnosticsComponent;

class DiagnosticsComponentTest extends \PHPUnit_Framework_TestCase
{
    protected function loadFile($contents, $path = 'qaz.php')
    {
        $container = new Container();
        $container->set('file', $file = new SourceFile($container, $path, $contents));
        $container->set('parser', new ParserComponent($container));
        return [$file, new DiagnosticsComponent($container)];
    }

    public function test_getDiagnostics()
    {
        list($file, $diagsComponent) = $this->loadFile('<?php '."\n\n".'$a = 7 + *f("wsx");', 'qaz.php');
        $diags = $diagsComponent->getDiagnostics();
        $this->assertSame(1, count($diags));
        $this->assertSame('qaz.php', $diags[0]->getFile());
        $this->assertSame(17, $diags[0]->getStart());
        $this->assertSame(17, $diags[0]->getEnd());
        $this->assertSame([3, 10], $file->getLineAndColumn($diags[0]->getStart()));
        $this->assertSame([3, 10], $file->getLineAndColumn($diags[0]->getEnd()));
        $this->assertSame("Syntax error, unexpected '*'", $diags[0]->getDescription());
    }
}
