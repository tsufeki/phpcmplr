<?php

namespace PhpCmplr\Server\Action;

use PhpCmplr\PhpCmplr;
use PhpCmplr\Core\SourceFile\SourceFileInterface;
use PhpCmplr\Core\Type\Type as TypeType;
use PhpCmplr\Core\TypeInferrer\TypeInferrerInterface;

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
        /** @var TypeType|null */
        $type = null;

        if ($container !== null) {
            /** @var SourceFileInterface */
            $file = $container->get('file');
            $offset = $file->getOffset($data->location->line, $data->location->col);
            /** @var TypeInferrerInterface */
            $typeinfer = $container->get('typeinfer');
            $type = $typeinfer->getType($offset);
        }

        $result = new \stdClass();
        $result->type = $type !== null ? $type->toString() : null;
        return $result;
    }
}
