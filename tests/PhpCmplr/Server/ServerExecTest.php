<?php

namespace PhpCmplr\Server;

class ServerExecTest extends \PHPUnit_Framework_TestCase
{
    public function test_ping()
    {
        $port = 7474;
        $pipes = [];
        $proc = proc_open(
            'php "' . __DIR__ . '/../../../bin/phpcmplr.php" --port ' . $port,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes);

        foreach ([200, 300, 500, 1000, 2000] as $sleep) {
            usleep($sleep*1000);

            $response = @file_get_contents(
                'http://127.0.0.1:' . $port . '/ping',
                false,
                stream_context_create([
                    'http' => [
                        'header' => "Content-type: application/json\r\n",
                        'method' => 'POST',
                        'content' => '{}',
                    ],
                ])
            );

            if ($response !== false) {
                break;
            }
        }

        $this->assertSame('{}', $response);

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_terminate($proc);
        proc_close($proc);
    }
}
