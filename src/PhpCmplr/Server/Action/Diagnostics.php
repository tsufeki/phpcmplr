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
            $diagData->start = new \stdClass();
            list($diagData->start->line, $diagData->start->col) =
                $file->getLineAndColumn($diag->getStart());
            $diagData->end = new \stdClass();
            list($diagData->end->line, $diagData->end->col) =
                $file->getLineAndColumn($diag->getEnd());
            $diagData->description = $diag->getDescription();
            $diagsData[] = $diagData;
        }

        $result = new \stdClass();
        $result->diagnostics = $diagsData;
        return $result;
    }
}
