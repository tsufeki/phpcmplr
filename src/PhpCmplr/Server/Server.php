<?php

/*
 * phpcmplr
 * Copyright (C) 2016  tsufeki
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PhpCmplr\Server;

use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as EventLoopFactory;
use React\Socket\Server as ServerSocket;
use React\Http\Server as HttpServer;
use React\Http\Request;
use React\Http\Response;
use React\Http\ResponseCodes;

use PhpCmplr\Completer\Project;
use PhpCmplr\Completer\SourceFile;

/**
 * HTTP server.
 *
 * All requests are POST.
 * Request Content-Type must be 'application/json'.
 * Requests and responses' bodies are JSON.
 * Offsets into a file are 0-based.
 * (start, end) ranges are inclusive.
 *
 * /ping command returns {}.
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
     * @param int    $port
     * @param string $host
     */
    public function __construct($port, $host = '127.0.0.1')
    {
        $this->host = $host;
        $this->port = $port;
        $this->project = new Project();
    }

    /**
     * Start the server.
     */
    public function run()
    {
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
    protected function handle(Request $request, Response $response)
    {
        $status = 200;
        $responseJson = '{}';

        try {
            if ($request->getMethod() !== 'POST') {
                throw new HttpException(405);
            }

            $data = json_decode($request->getBody());
            if ($data === null || !is_object($data)) {
                throw new HttpException(400);
            }

            $responseData = new \stdClass();

            switch ($request->getPath()) {
                case '/ping':
                    break;
                case '/load':
                    $responseData = $this->load($data);
                    break;
                case '/diagnostics':
                    $responseData = $this->diagnostics($data);
                    break;
                case '/quit':
                    $responseData = $this->quit($data);
                    break;
                default:
                    throw new HttpException(404);
            }

            $responseJson = json_encode($responseData);
            if ($responseJson === false) {
                throw new HttpException(500);
            }

        } catch (HttpException $e) {
            $status = $e->getStatus();
            $responseJson = json_encode([
                'error' => $e->getStatus(),
                'message' => ResponseCodes::$statusTexts[$e->getStatus()],
            ]);
        } catch (Exception $e) {
            $status = 500;
            $responseJson = json_encode([
                'error' => 500,
                'message' => ResponseCodes::$statusTexts[500] . ': ' . $e->getMessage(),
            ]);
        }

        $response->writeHead($status, [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($responseJson),
        ]);
        $response->end($responseJson);
    }

    /**
     * Return property value if it exists and check the type.
     *
     * @param mixed       $data
     * @param string      $property
     * @param string|null $type     'object'|'array'|'string'|'int'|'bool'|null
     *
     * @return mixed
     * @throw HttpException 400
     */
    private static function getPropertyOr400($data, $property, $type = null)
    {
        if (!isset($data->$property)) {
            throw new HttpException(400);
        }

        $value = $data->$property;
        switch ($type) {
            case 'object':
                if (!is_object($value)) {
                    throw new HttpException(400);
                }
                break;
            case 'array':
                if (!is_array($value)) {
                    throw new HttpException(400);
                }
                break;
            case 'string':
                if (!is_string($value)) {
                    throw new HttpException(400);
                }
                break;
            case 'int':
                if (!is_int($value)) {
                    throw new HttpException(400);
                }
                break;
            case 'bool':
                if (!is_bool($value)) {
                    throw new HttpException(400);
                }
                break;
        }

        return $value;
    }

    //
    // Commands.
    //

    /**
     * /load Load files.
     *
     * $data structure:
     * {
     *   "files": [
     *     {
     *       "path": full and absolute path,
     *       "contents": file contents as a string,
     *     },
     *     ...
     *  ]
     * }
     *
     * Return: {} on success.
     */
    public function load($data)
    {
        foreach (self::getPropertyOr400($data, 'files', 'array') as $fileData) {
            $path = self::getPropertyOr400($fileData, 'path', 'string');
            $contents = self::getPropertyOr400($fileData, 'contents', 'string');

            $file = $this->project->getFile($path);
            if ($file === null) {
                $file = new SourceFile($path);
                $this->project->addFile($file);
            }

            $file->load($contents);
        }

        return new \stdClass();
    }

    /**
     * /diagnostics Get diagnostics for a file.
     *
     * $data structure:
     * {
     *   "path": path of source file to get diagnostics for
     * }
     *
     * Return:
     * {
     *   "diagnostics": [
     *     {
     *       "start": start offset,
     *       "end": end offset,
     *       "description": description
     *     },
     *     ...
     *   ]
     * }
     */
    public function diagnostics($data)
    {
        $path = self::getPropertyOr400($data, 'path', 'string');
        $file = $this->project->getFile($path);

        if ($file === null) {
            return new \stdClass();
        }

        $diagsData = [];
        foreach ($file->getDiagnostics() as $diag) {
            $diagData = new \stdClass();
            $diagData->start = $diag->getStart();
            $diagData->end = $diag->getEnd();
            $diagData->description = $diag->getDescription();
            $diagsData[] = $diagData;
        }

        $result = new \stdClass();
        $result->diagnostics = $diagsData;
        return $result;
    }

    /**
     * /quit Quit the server.
     *
     * Return: {}
     */
    public function quit($data = null)
    {
        $this->socket->shutdown();
        return new \stdClass();
    }
}
