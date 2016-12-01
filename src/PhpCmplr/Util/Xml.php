<?php

namespace PhpCmplr\Util;

use DOMDocument;

class Xml
{
    /**
     * @param string $string
     *
     * @return DOMDocument
     */
    public static function load($string)
    {
        $xml = new DOMDocument();
        $xml->validateOnParse = false;
        $xml->resolveExternals = false;
        $xml->recover = true;
        $xml->strictErrorChecking = false;

        if (trim($string) === '') {
            return $xml;
        }

        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        try {
            libxml_clear_errors();

            if ($xml->loadXML($string, LIBXML_NONET | (defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
                $xml->normalizeDocument();
            }
        } finally {
            libxml_use_internal_errors($internalErrors);
            libxml_disable_entity_loader($disableEntities);
        }

        return $xml;
    }
}
