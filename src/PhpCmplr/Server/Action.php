<?php

namespace PhpCmplr\Server;

use PhpCmplr\Completer\Project;
use PhpCmplr\Completer\Location;
use PhpCmplr\Completer\SourceFile;
use PhpCmplr\Util\Json;
use PhpCmplr\Util\JsonLoadException;
use PhpCmplr\Util\JsonDumpException;

/**
 * Base action class.
 *
 * Provides JSON decoding/encoding and validation.
 *
 * Protocol notes:
 * - All requests are POST.
 * - Request Content-Type must be 'application/json'.
 * - Requests and responses' bodies are JSON objects.
 * - Locations in a file look like this: {"line": 11, "col": 22} and are 1-based.
 * - All string offsets (including "col" above) are counted in bytes, not UTF-8
 *   characters.
 * - (start, end) ranges are inclusive.
 */
abstract class Action implements ActionInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var object|null
     */
    private $schema = null;

    /**
     * @var mixed
     */
    private $logger = null;

    /**
     * @param string $path   Path which will invoke this action.
     */
    public function __construct($path)
    {
        $this->path = $path;
        $schema = $this->getSchema();
        if ($schema !== null) {
            $this->schema = Json::loadSchema($schema);
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get JSON Schema for request body.
     *
     * @return string|null
     */
    protected function getSchema()
    {
        return null;
    }

    /**
     * @param string[] $schemas
     *
     * @return string
     */
    protected function combineSchemas(...$schemas)
    {
        return '{"allOf": [' . implode(', ', $schemas) . ']}';
    }

    public function handleRequest($body, Project $project)
    {
        try {
            $data = Json::load($body, $this->schema);
            $responseData = $this->handle($data, $project);
            $response = Json::dump($responseData);
            return $response;
        } catch (JsonLoadException $e) {
            throw new HttpException(400);
        } catch (JsonDumpException $e) {
            throw new HttpException(500);
        }
    }

    /**
     * Override this.
     *
     * @param mixed   $data
     * @param Project $project
     *
     * @return object Response object which must be serializable to JSON.
     * @throws HttpException
     */
    protected function handle($data, Project $project)
    {
        return new \stdClass();
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * 
     *
     * @param Location   $location
     * @param SourceFile $file
     * @param bool       $withPath
     *
     * @return object
     */
    protected function makeLocation(Location $location, SourceFile $file, $withPath = false)
    {
        $loc = new \stdClass();
        list($loc->line, $loc->col) = $location->getLineAndColumn($file);
        if ($withPath) {
            $loc->path = $location->getPath();
        }

        return $loc;
    }
}