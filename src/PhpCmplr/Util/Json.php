<?php

namespace PhpCmplr\Util;

use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Uri\Retrievers\PredefinedArray;
use JsonSchema\RefResolver;
use JsonSchema\Validator;

class Json
{
    public static function loadSchema($string)
    {
        $uri = 'http://phpcmplr/loadedschema.json';
        $uriRetriever = new UriRetriever();
        $uriRetriever->setUriRetriever(new PredefinedArray([
            $uri => $string,
        ]));
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
            throw new JsonLoadException();
        }

        if ($schema !== null) {
            $validator = new Validator();
            $validator->check($object, $schema);
            if (!$validator->isValid()) {
                throw new JsonLoadException();
            }
        }

        return $object;
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
            throw new JsonDumpException();
        }

        return $string;
    }
}
