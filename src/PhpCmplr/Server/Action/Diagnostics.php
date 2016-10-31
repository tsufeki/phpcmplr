<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\PhpCmplr;
use PhpCmplr\Completer\Diagnostics\Diagnostic;
use PhpCmplr\Completer\Diagnostics\Fix;
use PhpCmplr\Completer\Diagnostics\FixChunk;
use PhpCmplr\Completer\Diagnostics\Diagnostics as DiagnosticsComponent;

/**
 * Get diagnostics for one file.
 *
 * Returns:
 * {
 *   "diagnostics": [
 *     {
 *       "start": start location,
 *       "end": end location,
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

    protected function handle($data, PhpCmplr $phpcmplr)
    {
        parent::handle($data, $phpcmplr);

        $container = $phpcmplr->getFile($data->path);
        $diagsData = [];

        if ($container !== null) {
            $file = $container->get('file');
            /** @var DiagnosticsComponent */
            $diagnostics = $container->get('diagnostics');
            foreach ($diagnostics->getDiagnostics() as $diag) {
                $diagData = new \stdClass();
                // TODO: not only the first range
                $range = $diag->getRanges()[0];
                $diagData->start = $this->makeLocation($range->getStart(), $file);
                $diagData->end = $this->makeLocation($range->getEnd(), $file);
                $diagData->description = $diag->getDescription();
                $diagsData[] = $diagData;
            }
        }

        $result = new \stdClass();
        $result->diagnostics = $diagsData;
        return $result;
    }
}
