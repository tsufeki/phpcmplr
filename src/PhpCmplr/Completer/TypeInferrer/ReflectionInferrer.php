<?php

namespace PhpCmplr\Completer\TypeInferrer;

use PhpLenientParser\Node;
use PhpLenientParser\Node\Name;
use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Identifier;

use PhpCmplr\Completer\NodeVisitorComponent;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Type\ArrayType;
use PhpCmplr\Completer\Type\ObjectType;
use PhpCmplr\Completer\Type\AlternativesType;
use PhpCmplr\Completer\Reflection\Reflection;
use PhpCmplr\Completer\Reflection\Element\Class_;
use PhpCmplr\Completer\Reflection\Element\Function_;
use PhpCmplr\Completer\Reflection\Element\Method;
use PhpCmplr\Completer\Reflection\Element\Variable;
use PhpCmplr\Completer\Reflection\Element\Property;
use PhpCmplr\Completer\Reflection\Element\Const_;
use PhpCmplr\Completer\Reflection\Element\ClassConst;

class ReflectionInferrer extends NodeVisitorComponent
{
    /**
     * @var Reflection
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
        $this->reflection = $this->container->get('reflection');
        $this->reflection->run();
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
     * @param Type   $objectType
     * @param string $methodName
     * @param bool   $staticContext
     *
     * @return Method[]
     */
    protected function findMethods(Type $objectType, $methodName, $staticContext = false)
    {
        $methods = [];

        if ($objectType instanceof AlternativesType) {
            foreach ($objectType->getAlternatives() as $altType) {
                $methods = array_merge($methods, $this->findMethods($altType, $methodName, $staticContext));
            }

        } elseif ($objectType instanceof ObjectType) {
            $method = $this->reflection->findMethod($objectType->getClass(), $methodName);
            if ($method !== null && (!$staticContext || $method->isStatic())) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    /**
     * @param Function_[]|Method[] $functions
     *
     * @return Type
     */
    protected function functionsReturnType(array $functions)
    {
        $types = [];
        foreach ($functions as $function) {
            $type = $function->getDocReturnType();
            if (!$type->equals(Type::mixed_())) {
                $types[] = $type;
            }
        }

        return $types !== [] ? Type::alternatives($types) : Type::mixed_();
    }

    /**
     * @param Type   $objectType
     * @param string $propertyName
     * @param bool   $staticContext
     *
     * @return Property[]
     */
    protected function findProperties(Type $objectType, $propertyName, $staticContext = false)
    {
        $properties = [];

        if ($objectType instanceof AlternativesType) {
            foreach ($objectType->getAlternatives() as $altType) {
                $properties = array_merge($properties, $this->findProperties($altType, $propertyName, $staticContext));
            }

        } elseif ($objectType instanceof ObjectType) {
            $property = $this->reflection->findProperty($objectType->getClass(), $propertyName);
            if ($property !== null && ($staticContext === $property->isStatic())) {
                $properties[] = $property;
            }
        }

        return $properties;
    }

    /**
     * @param Variable[]|Property[] $vars
     *
     * @return Type
     */
    protected function variablesType(array $vars)
    {
        $types = [];
        foreach ($vars as $var) {
            $type = $var->getType();
            if (!$type->equals(Type::mixed_())) {
                $types[] = $type;
            }
        }

        return $types !== [] ? Type::alternatives($types) : Type::mixed_();
    }

    /**
     * @param Type   $objectType
     * @param string $constName
     *
     * @return ClassConst[]
     */
    protected function findClassConsts(Type $objectType, $constName)
    {
        return []; // TODO
    }

    /**
     * @param Const_[] $consts
     *
     * @return Type
     */
    protected function constsType(array $consts)
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
                    if (empty($name)) {
                        $var = $node;
                        if ($var instanceof Stmt\Foreach_) {
                            $var = $var->valueVar;
                        } elseif ($var instanceof Stmt\For_) {
                            $var = $var->init;
                        } elseif ($var instanceof Stmt\Global_ && count($var->vars) === 1) {
                            $var = $var->vars[0];
                        }
                        if ($var instanceof Expr\Assign || $var instanceof Expr\AssignRef ||
                                $var instanceof Expr\AssignOp) {
                            $var = $var->var;
                        }
                        if ($var instanceof Expr\Variable && is_string($var->name)) {
                            $name = '$' . $var->name;
                        }
                    }
                    if (!empty($name)) {
                        $this->getCurrentFunctionScope()[$name] = $varTag->getType();
                    }
                }
            }
        }

        if ($node instanceof Name) {
            $self = $this->getCurrentClass();
            if (!empty($self) && !empty($self->getClass())) {
                $resolved = null;

                // Treating `static` as `self` is the best we can do here.
                if ($node->toString() === 'self' || $node->toString() === 'static') {
                    $resolved = $self->getClass();
                } elseif ($node->toString() === 'parent') {
                    $selfClass = $this->reflection->findClass($self->getClass());
                    if (!empty($selfClass) && $selfClass[0] instanceof Class_ && !empty($selfClass[0]->getExtends())) {
                        $resolved = $selfClass[0]->getExtends();
                    }
                }

                if (!empty($resolved)) {
                    $node->setAttribute('resolved', new Name\FullyQualified(
                        ltrim($resolved, '\\'), $node->getAttributes()));
                }
            }

        } elseif ($node instanceof Stmt\Function_) {
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
                $method = $this->reflection->findMethod($class->getClass(), $node->name);
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
                $scope['$' . $param->name] = Type::fromString(Type::nameToString($param->type));
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
        $reflections = null;

        if ($node instanceof Expr\Variable) {
            if (is_string($node->name)) {
                if ($node->name === 'this') {
                    $type = $this->getCurrentClass();
                } elseif (array_key_exists('$' . $node->name, $this->getCurrentFunctionScope())) {
                    $type = $this->getCurrentFunctionScope()['$' . $node->name];
                }
            }

        } elseif ($node instanceof Expr\FuncCall) {
            $reflections = [];
            if ($node->name instanceof Name) {
                $reflections = $this->reflection->findFunction(Type::nameToString($node->name));
            } else {
                $reflections = $this->findMethods($node->name->getAttribute('type'), '__invoke');
            }
            $type = $this->functionsReturnType($reflections);

        // TODO: ConstFetch
        } elseif ($node instanceof Expr\MethodCall) {
            $reflections = [];
            if (is_string($node->name) || $node->name instanceof Identifier) {
                $reflections = $this->findMethods($node->var->getAttribute('type'), (string)$node->name);
            }
            $type = $this->functionsReturnType($reflections);

        } elseif ($node instanceof Expr\StaticCall) {
            $reflections = [];
            if ($node->class instanceof Name && (is_string($node->name) || $node->name instanceof Identifier)) {
                $reflections = $this->findMethods(
                    Type::object_(Type::nameToString($node->class)),
                    (string)$node->name,
                    true);
            }
            $type = $this->functionsReturnType($reflections);

        } elseif ($node instanceof Expr\PropertyFetch) {
            $reflections = [];
            if (is_string($node->name) || $node->name instanceof Identifier) {
                $reflections = $this->findProperties($node->var->getAttribute('type'), '$' . (string)$node->name);
            }
            $type = $this->variablesType($reflections);

        } elseif ($node instanceof Expr\StaticPropertyFetch) {
            $reflections = [];
            if ($node->class instanceof Name && is_string($node->name)) {
                $reflections = $this->findProperties(Type::object_(Type::nameToString($node->class)),
                    '$' . $node->name, true);
            }
            $type = $this->variablesType($reflections);

        } elseif ($node instanceof Expr\ClassConstFetch) {
            // TODO ::class
            $reflections = [];
            if ($node->class instanceof Name) {
                $reflections = $this->findClassConsts(
                    Type::object_(Type::nameToString($node->class)),
                    (string)$node->name);
            }
            $type = $this->constsType($reflections);

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
                    $types[] = $this->functionsReturnType($this->findMethods($altType, 'offsetGet'));
                    // TODO: check for ArrayAccess
                }
            }
            $type = $types !== [] ? Type::alternatives($types) : Type::mixed_();

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
            $exprType = $node->expr->getAttribute('type');
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
            $type = $types !== [] ? Type::alternatives($types) : Type::mixed_();

        } elseif ($node instanceof Expr\Cast\Object_) {
            $exprType = $node->expr->getAttribute('type');
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
            $type = $types !== [] ? Type::alternatives($types) : Type::mixed_();

        } elseif ($node instanceof Expr\Include_) {
            $type = Type::mixed_();

        // TODO: Yield_
        // TODO: YieldFrom
        }

        if ($type !== null) {
            $node->setAttribute('type', $type);
        }
        if ($reflections !== null) {
            $node->setAttribute('reflections', $reflections);
        }
    }
}
