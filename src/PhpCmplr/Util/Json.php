<?php

namespace PhpCmplr\Util;

use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Uri\Retrievers\PredefinedArray;
use JsonSchema\RefResolver;
use JsonSchema\Validator;

class Json
{
    private static $uriPrefix = 'http://phpcmplr/';

    /**
     * @param string   $string
     * @param string[] Predefined schemas: $defs name => schema.
     *
     * @return object
     */
    public static function loadSchema($string, $defs = [])
    {
        $uri = static::$uriPrefix . 'loadedschema.json';
        $schemas = [
            $uri => $string,
        ];
        foreach ($defs as $defName => $def) {
            $schemas[static::$uriPrefix . $defName] = $def;
        }
        $uriRetriever = new UriRetriever();
        $uriRetriever->setUriRetriever(new PredefinedArray($schemas));
        $uriResolver = new UriResolver();
        $refResolver = new RefResolver($uriRetriever, $uriResolver);

        return $refResolver->resolve($uri);
    }

    /**
     * @param string      $string JSON encoded object.
     * @param object|null $schema JSON schema, loaded and resolved by loadSchema().
     *
     * @return object
     * @throws JsonLoadException
     */
    public static function load($string, $schema = null)
    {
        $object = json_decode($string);
        if ($object === null) {
            throw new JsonLoadException(json_last_error_msg());
        }

        if ($schema !== null) {
            $validator = new Validator();
            $validator->check($object, $schema);
            if (!$validator->isValid()) {
                throw new JsonLoadException(sprintf(
                    "Schema error at %s: %s",
                    $validator->getErrors()[0]['property'],
                    $validator->getErrors()[0]['message']
                ));
            }
        }

        return $object;
    }

    /**
     * @param string      $string JSON encoded object.
     *
     * @return array
     * @throws JsonLoadException
     */
    public static function loadAsArray($string)
    {
        $array = json_decode($string, true);
        if ($array === null) {
            throw new JsonLoadException(json_last_error_msg());
        }

        return $array;
    }

    /**
     * @param object $object
     *
     * @return string JSON.
     * @throws JsonDumpException
     */
    public static function dump($object)
    {
        $string = json_encode($object);
        if ($string === false) {
            throw new JsonDumpException(json_last_error_msg());
        }

        return $string;
    }
}
