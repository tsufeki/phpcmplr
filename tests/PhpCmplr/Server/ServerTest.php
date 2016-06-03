<?php

namespace Tests\PhpCmplr\Server;

use React\Http\Request;
use React\Http\Response;

use PhpCmplr\PhpCmplr;
use PhpCmplr\Completer\ContainerFactoryInterface;
use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Project;
use PhpCmplr\Completer\SourceFile;
use PhpCmplr\Completer\Parser\ParserComponent;
use PhpCmplr\Completer\Diagnostics\DiagnosticsComponent;
use PhpCmplr\Server\Server;
use PhpCmplr\Server\Action;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    protected function mockRequest($path, $body, $method = 'POST')
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request
            ->method('getMethod')
            ->willReturn($method);
        $request
            ->method('getPath')
            ->willReturn($path);
        $request
            ->method('getBody')
            ->willReturn($body);
        return $request;
    }

    protected function mockResponse($status, $body)
    {
        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $response
            ->expects($this->once())
            ->method('writeHead')
            ->with($this->equalTo($status), $this->anything());
        $response
            ->expects($this->once())
            ->method('end')
            ->with(new \PHPUnit_Framework_Constraint_JsonMatches($body));
        return $response;
    }

    public function setUp()
    {
        $this->phpcmplr = new PhpCmplr(7373);
        $this->server = $this->phpcmplr->getServer();
    }

    public function test_ping()
    {
        $this->server->handle(
            $this->mockRequest('/ping', '{}'),
            $this->mockResponse(200, '{}'));
    }

    public function test_load_diagnostics()
    {
        $data = new \stdClass();
        $fileData = new \stdClass();
        $fileData->path = 'qaz.php';
        $fileData->contents = '<?php '."\n\n".'$a = 7 + *f("wsx");';
        $data->files = [$fileData];

        $this->server->handle(
            $this->mockRequest('/load', json_encode($data)),
            $this->mockResponse(200, '{}'));

        $data = new \stdClass();
        $data->path = 'qaz.php';

        $result = new \stdClass();
        $diagData = new \stdClass();
        $diagData->start = new \stdClass();
        $diagData->start->line = 3;
        $diagData->start->col = 10;
        $diagData->end = new \stdClass();
        $diagData->end->line = 3;
        $diagData->end->col = 10;
        $diagData->description = "Syntax error, unexpected '*'";
        $result->diagnostics = [$diagData];

        $this->server->handle(
            $this->mockRequest('/diagnostics', json_encode($data)),
            $this->mockResponse(200, json_encode($result)));

        $data->files = [$fileData];

        $this->server->handle(
            $this->mockRequest('/diagnostics', json_encode($data)),
            $this->mockResponse(200, json_encode($result)));
    }

    public function test_goto()
    {
        $data = new \stdClass();
        $fileData = new \stdClass();
        $fileData->path = 'qaz.php';
        $fileData->contents = '<?php function f(){} f();';
        $data->files = [$fileData];
        $data->location = new \stdClass();
        $data->location->path = 'qaz.php';
        $data->location->line = 1;
        $data->location->col = 22;

        $result = new \stdClass();
        $goto = new \stdClass();
        $goto->path = 'qaz.php';
        $goto->line = 1;
        $goto->col = 7;
        $result->goto = [$goto];

        $this->server->handle(
            $this->mockRequest('/goto', json_encode($data)),
            $this->mockResponse(200, json_encode($result)));
    }

    public function test_NotFound()
    {
        $result = new \stdClass();
        $result->error = 404;
        $result->message = 'Not Found';

        $this->server->handle(
            $this->mockRequest('/notfound', '{}'),
            $this->mockResponse(404, json_encode($result)));
    }

    public function test_BadRequest()
    {
        $result = new \stdClass();
        $result->error = 400;
        $result->message = 'Bad Request';

        $this->server->handle(
            $this->mockRequest('/load', '-----'),
            $this->mockResponse(400, json_encode($result)));

        $data = new \stdClass();
        $data->files = 42;

        $this->server->handle(
            $this->mockRequest('/load', json_encode($data)),
            $this->mockResponse(400, json_encode($result)));
    }

    public function test_MethodNotAllowed()
    {
        $result = new \stdClass();
        $result->error = 405;
        $result->message = 'Method Not Allowed';

        $this->server->handle(
            $this->mockRequest('/load', '{}', 'GET'),
            $this->mockResponse(405, json_encode($result)));
    }
}
