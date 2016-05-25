<?php

namespace PhpCmplr\Completer\TypeInferrer;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

use PhpCmplr\Completer\NodeVisitorComponent;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\Parser\DocTag\ArrayType;
use PhpCmplr\Completer\Parser\DocTag\ObjectType;
use PhpCmplr\Completer\Parser\DocTag\AlternativesType;
use PhpCmplr\Completer\Reflection\ReflectionComponent;
use PhpCmplr\Completer\Reflection\Class_;

class ReflectionInferrerComponent extends NodeVisitorComponent
{
    /**
     * @var ReflectionComponent
     */
    private $reflection;

    /**
     * @var Type[][] int => variable name => Type
     */
    protected $functionScopeStack;

    /**
     * @var ObjectType[]
     */
    protected $classStack;

    public function beforeTraverse(array $nodes)
    {
        $this->functionScopeStack = [[]]; // global scope
        $this->classStack = [];
    }

    /**
     * @return Type[] variable name => Type
     */
    protected function &getCurrentFunctionScope()
    {
        return $this->functionScopeStack[count($this->functionScopeStack) - 1];
    }

    /**
     * @return ObjectType|null
     */
    protected function getCurrentClass()
    {
        return $this->classStack === [] ? null : $this->classStack[count($this->classStack) - 1];
    }

    /**
     * @param ObjectType $type
     *
     * @return ObjectType
     */
    protected function resolveSelfParent(ObjectType $type)
    {
        $resolved = null;
        // Treating `static` as `self` is the best we can do here.
        if ($type->getClass() === 'self' || $type->getClass() === 'static') {
            $resolved = $this->getCurrentClass();
        } elseif ($type->getClass() === 'parent') {
            $self = $this->getCurrentClass();
            if (!empty($self) && !empty($self->getClass())) {
                $selfClass = $this->reflection->findClass($self->getClass());
                if (!empty($selfClass) && $selfClass[0] instanceof Class_ && !empty($selfClass->getExtends())) {
                    $resolved = Type::object_($selfClass->getExtends());
                }
            }
        }

        if ($resolved === null) {
            $resolved = $type;
        }
        return $resolved;
    }

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
            $objectType = $this->resolveSelfParent($objectType);
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
            $objectType = $this->resolveSelfParent($objectType);
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

    public function enterNode(Node $node)
    {
        if (!($node instanceof Stmt\Property)) {
            $annotations = [];
            if ($node->hasAttribute('annotations')) {
                $annotations = $node->getAttribute('annotations');
            }
            if (!empty($annotations['var'])) {
                foreach ($annotations['var'] as $varTag) {
                    $name = $varTag->getIdentifier();
                    if (empty($name) && $node instanceof Expr\Variable && is_string($node->name)) {
                        $name = '$' . $node->name;
                    }
                    if (!empty($name)) {
                        $this->getCurrentFunctionScope()[$name] = $varTag->getType();
                    }
                }
            }
        }

        if ($node instanceof Stmt\Function_) {
            $scope = [];
            $functions = $this->reflection->findFunction($node->hasAttribute('namespacedName')
                ? Type::nameToString($node->getAttribute('namespacedName'))
                : $node->name);
            if (!empty($functions)) {
                foreach ($functions[0]->getParams() as $param) {
                    $scope[$param->getName()] = $param->getDocType();
                }
            }
            $this->functionScopeStack[] = $scope;

        } elseif ($node instanceof Stmt\ClassMethod) {
            $scope = [];
            $class = $this->getCurrentClass();
            if (!empty($class) && !empty($class->getClass())) {
                $method = $this->reflection->findMethod($class, $node->name);
                if (!empty($method)) {
                    foreach ($method->getParams() as $param) {
                        $scope[$param->getName()] = $param->getDocType();
                    }
                }
            }
            $this->functionScopeStack[] = $scope;

        } elseif ($node instanceof Expr\Closure) {
            $scope = [];
            $parentScope = &$this->getCurrentFunctionScope();
            foreach ($node->uses as $use) {
                if (array_key_exists('$' . $use->var, $parentScope)) {
                    $scope['$' . $use->var] = $parentScope['$' . $use->var];
                }
            }
            foreach ($node->params as $param) {
                $scope['$' . $node->name] = Type::fromString(Type::nameToString($param->type));
            }
            $this->functionScopeStack[] = $scope;

        } elseif ($node instanceof Stmt\ClassLike) {
            $className = $node->hasAttribute('namespacedName')
                ? Type::nameToString($node->getAttribute('namespacedName'))
                : $node->name;
            $this->classStack[] = Type::object_($className);
            // for isolation, in case of illegal statements in class def:
            $this->functionScopeStack[] = [];
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Stmt\Function_ || $node instanceof Stmt\ClassMethod ||
                $node instanceof Expr\Closure) {
            array_pop($this->functionScopeStack);

        } elseif ($node instanceof Stmt\ClassLike) {
            array_pop($this->classStack);
            array_pop($this->functionScopeStack);
        }

        if (!($node instanceof Expr)) {
            return;
        }

        $type = null;

        if ($node instanceof Expr\Variable) {
            if (is_string($node->name)) {
                if ($node->name === 'this') {
                    $type = $this->getCurrentClass();
                } elseif (array_key_exists('$' . $node->name, $this->getCurrentFunctionScope())) {
                    $type = $this->getCurrentFunctionScope()['$' . $node->name];
                }
            }

        } elseif ($node instanceof Expr\FuncCall) {
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
                $type = $this->propertyType($node->var->getAttribute('type'), '$' . $node->name);
            } else {
                $type = Type::mixed_();
            }

        } elseif ($node instanceof Expr\StaticPropertyFetch) {
            if ($node->class instanceof Name && is_string($node->name)) {
                $type = $this->propertyType(Type::object_(Type::nameToString($node->class)), '$' . $node->name, true);
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
