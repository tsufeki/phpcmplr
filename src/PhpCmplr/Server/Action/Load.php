<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\PhpCmplr;
use PhpCmplr\Server\Action;

/**
 * Load some files into the server.
 */
class Load extends Action
{
    const SCHEMA = <<<'END'
{
    "type": "object",
    "properties": {
        "files": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "path": {"type": "string"},
                    "contents": {"type": "string"}
                },
                "required": ["path", "contents"],
                "additionalProperties": false
            }
        }
    }
}
END;

    public function __construct($path = '/load')
    {
        parent::__construct($path);
    }

    protected function getSchema()
    {
        return self::SCHEMA;
    }

    protected function handle($data, PhpCmplr $phpcmplr)
    {
        if (isset($data->files)) {
            foreach ($data->files as $fileData) {
                $phpcmplr->addFile($fileData->path, $fileData->contents);
            }
        }

        return new \stdClass();
    }
}
