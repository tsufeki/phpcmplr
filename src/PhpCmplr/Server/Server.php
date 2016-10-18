<?php

namespace PhpCmplr\Server;

use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as EventLoopFactory;
use React\Socket\Server as ServerSocket;
use React\Http\Server as HttpServer;
use React\Http\Request;
use React\Http\Response;
use React\Http\ResponseCodes;
use React\Http\StreamingBodyParser\Factory as BodyParserFactory;

use PhpCmplr\PhpCmplr;

/**
 * HTTP server.
 */
class Server
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var ServerSocket
     */
    private $socket;

    /**
     * @var HttpServer
     */
    private $http;

    /**
     * @var PhpCmplr
     */
    private $phpcmplr;

    /**
     * @var mixed
     */
    private $logger;

    /**
     * @var ActionInterface[]
     */
    private $actions;

    /**
     * @param PhpCmplr      $phpcmplr
     * @param mixed         $logger
     * @param LoopInterface $loop
     * @param array         $options
     */
    public function __construct(PhpCmplr $phpcmplr, $logger, LoopInterface $loop, array $options)
    {
        $this->host = $options['host'];
        $this->port = $options['port'];
        $this->phpcmplr = $phpcmplr;
        $this->logger = $logger;
        $this->loop = $loop;
        $this->actions = [];
    }

    /**
     * @param ActionInterface $action
     *
     * @return $this
     */
    public function addAction(ActionInterface $action)
    {
        $this->actions[$action->getPath()] = $action;
        $action->setLogger($this->logger);

        return $this;
    }

    /**
     * Start the server.
     */
    public function run()
    {
        $this->logger->info("Server: listening on $this->host:$this->port");
        $this->socket = new ServerSocket($this->loop);
        $this->http = new HttpServer($this->socket);

        $this->http->on('request', function (Request $request, Response $response) {
            $bodyParser = BodyParserFactory::create($request);

            $bodyParser->on('body', function ($body) use ($request, $response) {
                $this->handle($request, $body, $response);
            });
        });

        $this->socket->listen($this->port, $this->host);
    }

    /**
     * Handle request.
     *
     * @param Request  $request
     * @param string   $requestBody
     * @param Response $response
     */
    public function handle(Request $request, $requestBody, Response $response)
    {
        $status = 200;
        $responseBody = '{}';

        try {
            if ($request->getMethod() !== 'POST') {
                throw new HttpException(405);
            }

            if (!array_key_exists($request->getPath(), $this->actions)) {
                throw new HttpException(404);
            }

            $this->logger->info('Server: request ' . $request->getPath());
            $responseBody = $this->actions[$request->getPath()]->handleRequest(
                $requestBody,
                $this->phpcmplr);

        } catch (HttpException $e) {
            $this->logger->notice($e->getMessage(), ['exception' => $e]);
            $status = $e->getStatus();
            $responseBody = json_encode([
                'error' => $e->getStatus(),
                'message' => ResponseCodes::$statusTexts[$e->getStatus()],
            ]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $status = 500;
            $responseBody = json_encode([
                'error' => 500,
                'message' => ResponseCodes::$statusTexts[500] . ': ' . $e->getMessage(),
            ]);
        } catch (\Error $e) { // PHP7
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $status = 500;
            $responseBody = json_encode([
                'error' => 500,
                'message' => ResponseCodes::$statusTexts[500] . ': ' . $e->getMessage(),
            ]);
        }

        $response->writeHead($status, [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($responseBody),
        ]);
        $response->end($responseBody);
    }

    /**
     * Quit the server.
     */
    public function quit()
    {
        $this->logger->info("Server: quit");
        $this->socket->shutdown();
    }
}
