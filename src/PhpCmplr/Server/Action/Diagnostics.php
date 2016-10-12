<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\PhpCmplr;
use PhpCmplr\Completer\Diagnostics\Diagnostic;
use PhpCmplr\Completer\Diagnostics\Fix;
use PhpCmplr\Completer\Diagnostics\FixChunk;

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

    protected function handle($data, PhpCmplr $phpcmplr)
    {
        parent::handle($data, $phpcmplr);

        $container = $phpcmplr->getFile($data->path);
        $diagsData = [];

        if ($container !== null) {
            $file = $container->get('file');
            /** @var Diagnostic $diag */
            foreach ($container->get('diagnostics')->getDiagnostics() as $diag) {
                $diagData = new \stdClass();
                // TODO: not only the first range
                $range = $diag->getRanges()[0];
                $diagData->start = $this->makeLocation($range->getStart(), $file);
                $diagData->end = $this->makeLocation($range->getEnd(), $file);
                $diagData->description = $diag->getDescription();

                $diagData->fixes = [];
                /** @var Fix $fix */
                foreach ($diag->getFixes() as $fix) {
                    $fixData = new \stdClass();
                    $fixData->description = $fix->getDescription();
                    $fixData->chunks = [];
                    /** @var FixChunk $chunk */
                    foreach ($fix->getChunks() as $chunk) {
                        $chunkData = new \stdClass();
                        $chunkData->start = $this->makeLocation($chunk->getRange()->getStart(), $file);
                        $chunkData->end = $this->makeLocation($chunk->getRange()->getEnd(), $file);
                        $chunkData->replacement = $chunk->getReplacement();
                        $fixData->chunks[] = $chunkData;
                    }
                    $diagData->fixes[] = $fixData;
                }

                $diagsData[] = $diagData;
            }
        }

        $result = new \stdClass();
        $result->diagnostics = $diagsData;
        return $result;
    }
}
