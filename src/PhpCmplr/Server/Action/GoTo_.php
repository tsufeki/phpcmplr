<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\PhpCmplr;

/**
 * Go to definition of the object at location.
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

    protected function handle($data, PhpCmplr $phpcmplr)
    {
        parent::handle($data, $phpcmplr);

        $container = $phpcmplr->getFile($data->location->path);
        $gotoData = [];

        if ($container !== null) {
            $file = $container->get('file');
            $offset = $file->getOffset($data->location->line, $data->location->col);
            foreach ($container->get('goto')->getGoToLocations($offset) as $location) {
                $gotoContainer = $phpcmplr->getFile($location->getPath());
                if ($gotoContainer === null) {
                    $gotoContainer = $phpcmplr->addFile(
                        $location->getPath(), 
                        $container->get('io')->read($location->getPath()));
                }
                $gotoData[] = $this->makeLocation($location, $gotoContainer->get('file'), true);
            }
        }

        $result = new \stdClass();
        $result->goto = $gotoData;
        return $result;
    }
}
