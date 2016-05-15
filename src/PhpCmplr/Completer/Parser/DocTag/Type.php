<?php

namespace PhpCmplr\Completer\Parser\DocTag;

class Type
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    protected function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return Type 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Type 
     */
    public static function int_()
    {
        return new Type('int');
    }

    /**
     * @return Type 
     */
    public static function float_()
    {
        return new Type('float');
    }

    /**
     * @return Type 
     */
    public static function bool_()
    {
        return new Type('bool');
    }

    /**
     * @return Type 
     */
    public static function string_()
    {
        return new Type('string');
    }

    /**
     * @return Type 
     */
    public static function null_()
    {
        return new Type('null');
    }

    /**
     * @return Type 
     */
    public static function resource_()
    {
        return new Type('resource');
    }

    /**
     * @return Type 
     */
    public static function callable_()
    {
        return new Type('callable');
    }

    /**
     * @return Type 
     */
    public static function mixed_()
    {
        return new Type('mixed');
    }

    /**
     * @param Type|null $valueType
     * @param Type|null $keyType
     *
     * @return ArrayType
     */
    public static function array_(Type $valueType = null, Type $keyType = null)
    {
        return new ArrayType($valueType ?: static::mixed_(), $keyType ?: static::mixed_());
    }

    /**
     * @param string|null $class Fully qualified class name.
     *
     * @return ObjectType
     */
    public static function object_($class = null)
    {
        return new ObjectType($class);
    }

    /**
     * @param Type[] $alternatives
     *
     * @return AlternativesType
     */
    public static function alternatives(array $alternatives)
    {
        return new AlternativesType($alternatives);
    }

    /**
     * Parse phpdoc type.
     *
     * @param string $type
     *
     * @return Type
     */
    public static function fromString($type)
    {
        $type = trim($type);

        if (empty($type)) {
            return static::mixed_();
        }

        $alternatives = explode('|', $type);
        if (count($alternatives) > 1) {
            $typeAlternatives = [];
            foreach ($alternatives as $alt) {
                $typeAlternatives[] = static::fromString($alt);
            }
            return static::alternatives($typeAlternatives);
        }

        if (substr($type, -2) === '[]') {
            return static::array_(static::fromString(substr($type, 0, -2) ?: ''));
        }

        switch ($type) {
            case 'integer':
            case 'int':
                return static::int_();
            case 'float':
            case 'double':
                return static::float_();
            case 'bool':
            case 'boolean':
            case 'false':
            case 'true':
                return static::bool_();
            case 'string':
                return static::string_();
            case 'null':
            case 'void':
                return static::null_();
            case 'resource':
                return static::resource_();
            case 'callable':
                return static::callable_();
            case 'mixed':
            case 'any':
                return static::mixed_();
            case 'array':
                return static::array_();
            case 'object':
                return static::object_();
            case 'self':
            case '$this':
                return static::object_('self');
            case 'static':
            case 'parent':
                return static::object_($type);
            default:
                if (preg_match("/^(\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$/", $type) !== 1) {
                    return static::mixed_();
                }
                return static::object_($type);
        }
    }
}
