<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\Completer\Project;

/**
 * Get diagnostics for one file.
 *
 * Returns:
 * {
 *     "goto": [
 *         {
 *             "path": path,
 *             "line": line,
 *             "col": column
 *         },
 *         ...
 *     ]
 * }
 */
class GoTo_ extends Load
{
    const SCHEMA = <<<'END'
{
    "type": "object",
    "properties": {
        "location": {"$ref": "location.json#"}
    },
    "required": ["location"]
}
END;

    public function __construct($path = '/goto')
    {
        parent::__construct($path);
    }

    protected function getSchema()
    {
        return $this->combineSchemas(parent::getSchema(), self::SCHEMA);
    }

    protected function handle($data, Project $project)
    {
        parent::handle($data, $project);

        $container = $project->getFile($data->location->path);

        if ($container === null) {
            return new \stdClass();
        }

        $gotoData = [];
        $file = $container->get('file');
        $offset = $file->getOffset($data->location->line, $data->location->col);
        foreach ($container->get('goto')->getGoToLocations($offset) as $location) {
            $gotoContainer = $project->getFile($location->getPath());
            if ($gotoContainer === null) {
                $gotoContainer = $project->addFile(
                    $location->getPath(), 
                    $container->get('io')->read($location->getPath()));
            }
            $gotoData[] = $this->makeLocation($location, $gotoContainer->get('file'), true);
        }

        $result = new \stdClass();
        $result->goto = $gotoData;
        return $result;
    }
}
