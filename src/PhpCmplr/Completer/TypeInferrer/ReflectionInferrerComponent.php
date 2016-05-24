<?php

namespace PhpCmplr\Completer\TypeInferrer;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

use PhpCmplr\Completer\NodeVisitorComponent;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\Parser\DocTag\ArrayType;
use PhpCmplr\Completer\Parser\DocTag\ObjectType;
use PhpCmplr\Completer\Parser\DocTag\AlternativesType;

class ReflectionInferrerComponent extends NodeVisitorComponent
{
    private $reflection;

    /**
     * @param Type   $objectType
     * @param string $methodName
     * @param bool   $staticContext
     *
     * @return Type
     */
    protected function methodReturnType(Type $objectType, $methodName, $staticContext = false)
    {
        if ($objectType instanceof AlternativesType) {
            $types = [];
            foreach ($objectType->getAlternatives() as $altType) {
                $returnType = $this->methodReturnType($altType, $methodName);
                if (!$returnType->equals(Type::mixed_())) {
                    $types[] = $returnType;
                }
            }
            return $types !== [] ? Type::alternatives($types) : Type::mixed_();

        } elseif ($objectType instanceof ObjectType) {
            $method = $this->reflection->findMethod($objectType->getClass(), $methodName);
            if ($method !== null && (!$staticContext || $method->isStatic())) {
                return $method->getDocReturnType();
            }
        }

        return Type::mixed_();
    }

    /**
     * @param Type   $objectType
     * @param string $propertyName
     * @param bool   $staticContext
     *
     * @return Type
     */
    protected function propertyType(Type $objectType, $propertyName, $staticContext = false)
    {
        if ($objectType instanceof AlternativesType) {
            $types = [];
            foreach ($objectType->getAlternatives() as $altType) {
                $type = $this->propertyType($altType, $propertyName);
                if (!$type->equals(Type::mixed_())) {
                    $types[] = $type;
                }
            }
            return $types !== [] ? Type::alternatives($types) : Type::mixed_();

        } elseif ($objectType instanceof ObjectType) {
            $property = $this->reflection->findProperty($objectType->getClass(), $propertyName);
            if ($property !== null && ($staticContext === $property->isStatic())) {
                return $property->getType();
            }
        }

        return Type::mixed_();
    }

    /**
     * @param Type   $objectType
     * @param string $constName
     *
     * @return Type
     */
    protected function classConstType(Type $objectType, $constName)
    {
        return Type::mixed_(); // TODO
    }

    public function leaveNode(Node $node)
    {
        if (!($node instanceof Expr)) {
            return;
        }

        $type = null;
        if ($node instanceof Expr\FuncCall) {
            if ($node->name instanceof Name) {
                $functions = $this->reflection->findFunction(Type::nameToString($node->name));
                $types = [];
                foreach ($functions as $function) {
                    $types[] = $function->getDocReturnType();
                }
                $type = $types !== [] ? Type::alternatives($types) : Type::mixed_();
            } else {
                $type = $this->methodReturnType($node->name->getAttribute('type'), '__invoke');
            }

        // TODO: ConstFetch
        // TODO: Variable
        } elseif ($node instanceof Expr\MethodCall) {
            if (is_string($node->name)) {
                $type = $this->methodReturnType($node->var->getAttribute('type'), $node->name);
            } else {
                $type = Type::mixed_();
            }

        } elseif ($node instanceof Expr\StaticCall) {
            if ($node->class instanceof Name && is_string($node->name)) {
                $type = $this->methodReturnType(Type::object_(Type::nameToString($node->class)), $node->name, true);
            } else {
                $type = Type::mixed_();
            }

        } elseif ($node instanceof Expr\PropertyFetch) {
            if (is_string($node->name)) {
                $type = $this->propertyType($node->var->getAttribute('type'), $node->name);
            } else {
                $type = Type::mixed_();
            }

        } elseif ($node instanceof Expr\StaticPropertyFetch) {
            if ($node->class instanceof Name && is_string($node->name)) {
                $type = $this->propertyType(Type::object_(Type::nameToString($node->class)), $node->name, true);
            } else {
                $type = Type::mixed_();
            }

        } elseif ($node instanceof Expr\ClassConstFetch) {
            if ($node->class instanceof Name) {
                $type = $this->classConstType(Type::object_(Type::nameToString($node->class)), $node->name);
            } else {
                $type = Type::mixed_();
            }

        } elseif ($node instanceof Expr\ArrayDimFetch) {
            $arrayType = $node->var->getAttribute('type');
            $altTypes = [$arrayType];
            if ($arrayType instanceof AlternativesType) {
                $altTypes = $arrayType->getAlternatives();
            }
            $types = [];
            foreach ($altTypes as $altType) {
                if ($altType instanceof ArrayType) {
                    $types[] = $altType->getValueType();
                } elseif ($altType instanceof ObjectType) {
                    $types[] = $this->methodReturnType($altType, 'offsetGet'); // TODO: check for ArrayAccess
                }
            }
            return $types !== [] ? Type::alternatives($types) : Type::mixed_();

        } elseif ($node instanceof Expr\Array_) {
            $type = Type::array_();

        } elseif ($node instanceof Expr\New_) {
            if ($node->class instanceof Name) {
                $type = Type::object_(Type::nameToString($node->class));
            } else {
                $type = Type::object_();
            }

        } elseif ($node instanceof Expr\Clone_) {
            $type = $node->expr->getAttribute('type');

        } elseif ($node instanceof Expr\Closure) {
            $type = Type::object_('\\Closure');

        } elseif ($node instanceof Expr\Cast\Array_) {
            $exprType = $node->var->getAttribute('type');
            $altTypes = [$exprType];
            if ($exprType instanceof AlternativesType) {
                $altTypes = $exprType->getAlternatives();
            }
            $types = [];
            foreach ($altTypes as $altType) {
                if ($altType instanceof ArrayType) {
                    $types[] = $altType;
                } else {
                    $types[] = Type::array_(); // TODO primitives: (array)int --> int[]
                }
            }
            return $types !== [] ? Type::alternatives($types) : Type::mixed_();

        } elseif ($node instanceof Expr\Cast\Object_) {
            $exprType = $node->var->getAttribute('type');
            $altTypes = [$exprType];
            if ($exprType instanceof AlternativesType) {
                $altTypes = $exprType->getAlternatives();
            }
            $types = [];
            foreach ($altTypes as $altType) {
                if ($altType instanceof ObjectType) {
                    $types[] = $altType;
                } else {
                    $types[] = Type::object_('\\stdClass');
                }
            }
            return $types !== [] ? Type::alternatives($types) : Type::mixed_();

        } elseif ($node instanceof Expr\Include_) {
            $type = Type::mixed_();

        // TODO: Yield_
        // TODO: YieldFrom
        }

        if ($type !== null) {
            $node->setAttribute('type', $type);
        }
    }

    protected function doRun()
    {
        $this->reflection = $this->container->get('reflection');
        parent::doRun();
    }
}
