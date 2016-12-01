<?php

namespace PhpCmplr\Util;

final class CommonTokens
{
    const T_DEC_INTEGER = 0x0001;
    const T_HEX_INTEGER = 0x0002;
    const T_OCT_INTEGER = 0x0004;
    const T_BIN_INTEGER = 0x0008;
    const T_FLOAT = 0x0010;
    const T_BOOLEAN = 0x0080;
    const T_NULL = 0x0100;
    const T_IDENTIFIER = 0x0200;

    const T_INTEGER = self::T_DEC_INTEGER | self::T_HEX_INTEGER | self::T_OCT_INTEGER | self::T_BIN_INTEGER;
    const T_VALUE = self::T_INTEGER | self::T_FLOAT | self::T_BOOLEAN | self::T_NULL;
    const T_ALL = 0xffff;

    const REGEX = [
        self::T_DEC_INTEGER => '/[-+]?(?:0|[1-9][0-9]*)/',
        self::T_HEX_INTEGER => '/[-+]?0[xX][0-9a-fA-F]+/',
        self::T_OCT_INTEGER => '/[-+]?0[0-9]+/',
        self::T_BIN_INTEGER => '/[-+]?0[bB]([01]+)/',
        self::T_FLOAT => '/[-+]?(?:(?:\\.[0-9]+|[0-9]+\\.[0-9]*)(?:[eE][-+]?[0-9]+)?|[0-9]+[eE][-+]?[0-9]+)/',
        self::T_BOOLEAN => '/true|false/i',
        self::T_NULL => '/null/i',
        self::T_IDENTIFIER => '/[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*/',
    ];

    private function __construct() { }

    /**
     * @param string $string
     * @param int    $tokens self::T_*
     *
     * @return mixed
     */
    public static function getValue($string, $tokens = self::T_VALUE)
    {
        foreach (self::REGEX as $tok => $regex) {
            if (($tok & $tokens) && preg_match($regex, $string, $matches) && $matches[0] === $string) {
                return self::evaluate($tok, $matches);
            }
        }

        return $string;
    }

    /**
     * @param int      $token
     * @param string[] $matches
     *
     * @return mixed
     */
    private static function evaluate($token, array $matches)
    {
        switch ($token) {
            case self::T_NULL:
                return null;
            case self::T_BOOLEAN:
                return strtolower($matches[0]) === 'true';
            case self::T_DEC_INTEGER:
            case self::T_HEX_INTEGER:
            case self::T_OCT_INTEGER:
                return intval($matches[0], 0);
            case self::T_BIN_INTEGER:
                return bindec($matches[1]);
            case self::T_FLOAT:
                return floatval($matches[0]);
            default:
                return $matches[0];
        }
    }
}
