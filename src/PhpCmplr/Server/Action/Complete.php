<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\Completer\Project;

/**
 * Get completions for location.
 *
 * Returns:
 * {
 *     "completions": [
 *         {
 *             "insertion": string,
 *             "display": string,
 *             "kind": string, // variable, function, const, property, static_property,
 *                             // method, static_method, class_const, class, interface,
 *                             // trait, namespace.
 *             "type": string|null,
 *             "description": string|null
 *         },
 *         ...
 *     ]
 * }
 */
class Complete extends Load
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

    public function __construct($path = '/complete')
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
        $completionsData = [];

        if ($container !== null) {
            $file = $container->get('file');
            $offset = $file->getOffset($data->location->line, $data->location->col);
            foreach ($container->get('completer')->complete($offset) as $completion) {
                $data = new \stdClass();
                $data->insertion = $completion->getInsertion();
                $data->display = $completion->getDisplay();
                $data->kind = $completion->getKind();
                $data->type = $completion->getType();
                $data->description = $completion->getDescription();
                $completionsData[] = $data;
            }
        }

        $result = new \stdClass();
        $result->completions = $completionsData;
        return $result;
    }
}
