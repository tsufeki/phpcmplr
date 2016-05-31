<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\Completer\Project;

/**
 * Get diagnostics for one file.
 *
 * Returns:
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
class Diagnostics extends Load
{
    const SCHEMA = <<<'END'
{
    "type": "object",
    "properties": {
        "path": {"type": "string"}
    },
    "required": ["path"]
}
END;

    public function __construct($path = '/diagnostics')
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

        $container = $project->getFile($data->path);

        if ($container === null) {
            return new \stdClass();
        }

        $diagsData = [];
        $file = $container->get('file');
        foreach ($container->get('diagnostics')->getDiagnostics() as $diag) {
            $diagData = new \stdClass();
            $diagData->start = $this->makeLocation($diag->getStart(), $file);
            $diagData->end = $this->makeLocation($diag->getEnd(), $file);
            $diagData->description = $diag->getDescription();
            $diagsData[] = $diagData;
        }

        $result = new \stdClass();
        $result->diagnostics = $diagsData;
        return $result;
    }
}
