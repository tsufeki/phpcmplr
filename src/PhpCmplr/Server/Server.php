<?php

namespace PhpCmplr\Server;

use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as EventLoopFactory;
use React\Socket\Server as ServerSocket;
use React\Http\Server as HttpServer;
use React\Http\Request;
use React\Http\Response;
use React\Http\ResponseCodes;

use PhpCmplr\Completer\Project;

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
     * @var Project
     */
    private $project;

    /**
     * @var mixed
     */
    private $logger;

    /**
     * @var ActionInterface[]
     */
    private $actions;

    /**
     * @param Project $project
     * @param mixed   $logger
     * @param int     $port
     * @param string  $host
     */
    public function __construct(Project $project, $logger, $port, $host = '127.0.0.1')
    {
        $this->host = $host;
        $this->port = $port;
        $this->project = $project;
        $this->logger = $logger;
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
        $this->logger->info("Starting server on $this->host:$this->port");
        $this->loop = EventLoopFactory::create();
        $this->socket = new ServerSocket($this->loop);
        $this->http = new HttpServer($this->socket);

        $this->http->on('request', function (Request $request, Response $response) {
            $this->handle($request, $response);
        });

        $this->socket->listen($this->port, $this->host);
        $this->loop->run();
    }

    /**
     * Handle request.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function handle(Request $request, Response $response)
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

            $responseBody = $this->actions[$request->getPath()]->handleRequest(
                $request->getBody(),
                $this->project);

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
        $this->logger->info("Quitting server");
        $this->socket->shutdown();
    }
}
