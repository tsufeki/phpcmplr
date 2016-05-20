<?php

namespace PhpCmplr\Completer\Parser\DocTag;

use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Name\Relative;

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
     * @param Type $other
     *
     * @return bool
     */
    public function equals(Type $other)
    {
        return $this->compare($other) === 0;
    }

    /**
     * @param Type $other
     *
     * @return int
     */
    public function compare(Type $other)
    {
        return strcmp($this->getName(), $other->getName());
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
     * @param string|null $class
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
        $normalized = [];
        // Flatten the structure.
        foreach ($alternatives as $alternative) {
            if ($alternative instanceof AlternativesType) {
                $normalized = array_merge($normalized, $alternative->getAlternatives());
            } else {
                $normalized[] = $alternative;
            }
        }
        // Check for trivial cases.
        if (count($normalized) === 0) {
            return self::null_();
        }
        if (count($normalized) === 1) {
            return $normalized[0];
        }
        // Sort.
        usort($normalized, function (Type $x, Type $y) {
            return $x->compare($y);
        });

        return new AlternativesType($normalized);
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

    /**
     * Convert PhpParser's Name to string.
     *
     * @param Name|string $name
     *
     * @return string
     */
    public static function nameToString($name)
    {
        if (empty($name)) {
            return null;
        }

        if (is_string($name)) {
            return $name;
        }

        if ($name instanceof FullyQualified) {
            return '\\' . $name->toString();
        }

        if ($name instanceof Name && $name->hasAttribute('resolved')) {
            return static::nameToString($name->getAttribute('resolved'));
        }

        if ($name instanceof Relative) {
            return 'namespace\\' . $name->toString();
        }

        return $name->toString();
    }

    /**
     * Walk type tree and call transformer for each node.
     *
     * @param callable $transformer Type -> Type
     *
     * @return Type
     */
    public function walk(callable $transformer)
    {
        $type = $transformer($this);

        if ($type instanceof ArrayType) {
            $value = $type->getValueType();
            $key = $type->getKeyType();
            $newValue = $value->walk($transformer);
            $newKey = $key->walk($transformer);
            $type = ($value === $newValue && $key === $newKey) ? $type : Type::array_($newValue, $newKey);

        } elseif ($type instanceof AlternativesType) {
            $alternatives = [];
            $changed = false;
            foreach ($type->getAlternatives() as $alternative) {
                $newAlternative = $alternative->walk($transformer);
                $changed = $changed || $alternative !== $newAlternative;
                $alternatives[] = $newAlternative;
            }
            $type = $changed ? Type::alternatives($alternatives) : $type;
        }

        return $type;
    }
}
