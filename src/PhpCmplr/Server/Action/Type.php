<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\PhpCmplr;

/**
 * Get type of expression at location.
 *
 * Returns:
 * {
 *     "type": string|null
 * }
 */
class Type extends Load
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

    public function __construct($path = '/type')
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
        $type = null;

        if ($container !== null) {
            $file = $container->get('file');
            $offset = $file->getOffset($data->location->line, $data->location->col);
            $type = $container->get('typeinfer')->getType($offset);
        }

        $result = new \stdClass();
        $result->type = $type !== null ? $type->toString() : null;
        return $result;
    }
}
