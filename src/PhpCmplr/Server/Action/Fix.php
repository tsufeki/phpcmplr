<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\PhpCmplr;
use PhpCmplr\Core\Diagnostics\Diagnostic;
use PhpCmplr\Core\Diagnostics\Fix as FixObject;
use PhpCmplr\Core\Diagnostics\FixChunk;
use PhpCmplr\Core\Diagnostics\Diagnostics as DiagnosticsComponent;

/**
 * Get fix for the nearest diagnostic on the given line.
 *
 * Returns:
 * {
 *   "fixes": [
 *     {
 *       "description": description,
 *       "chunks: [
 *         {
 *           "start": start location,
 *           "end": end location,
 *           "replacement": replacement string
 *         },
 *         ...
 *       ]
 *     },
 *     ...
 *   ]
 * }
 */
class Fix extends Load
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

    public function __construct($path = '/fix')
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
        $fixesData = [];

        if ($container !== null) {
            $file = $container->get('file');
            $nearestDiag = null;
            $nearestDistance = 999999;
            /** @var DiagnosticsComponent */
            $diagnostics = $container->get('diagnostics');
            foreach ($diagnostics->getDiagnostics() as $diag) {
                // TODO: not only the first range
                $range = $diag->getRanges()[0];
                list($line1, $col1) = $range->getStart()->getLineAndColumn($file);
                list($line2, $col2) = $range->getEnd()->getLineAndColumn($file);
                list($line, $col) = [$data->location->line, $data->location->col];

                if (($line > $line1 || ($line == $line1 && $col >= $col1)) &&
                        ($line < $line2 || ($line == $line2 && $col <= $col2))) {
                    $distance = 0;
                } elseif ($line == $line1 && $line == $line2) {
                    $distance = min(abs($col1 - $col), abs($col2 - $col));
                } elseif ($line == $line1) {
                    $distance = $col1 - $col;
                } elseif ($line == $line2) {
                    $distance = $col - $col2;
                } else {
                    continue;
                }

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestDiag = $diag;
                }
            }

            if ($nearestDiag !== null) {
                /** @var FixObject $fix */
                foreach ($nearestDiag->getFixes() as $fix) {
                    $fixData = new \stdClass();
                    $fixData->description = $fix->getDescription();
                    $fixData->chunks = [];
                    /** @var FixChunk $chunk */
                    foreach ($fix->getChunks() as $chunk) {
                        $chunkData = new \stdClass();
                        $chunkData->start = $this->makeLocation($chunk->getRange()->getStart(), $file, true);
                        $chunkData->end = $this->makeLocation($chunk->getRange()->getEnd(), $file, true);
                        $chunkData->replacement = $chunk->getReplacement();
                        $fixData->chunks[] = $chunkData;
                    }
                    $fixesData[] = $fixData;
                }
            }
        }

        $result = new \stdClass();
        $result->fixes = $fixesData;
        return $result;
    }
}
