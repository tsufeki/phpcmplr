<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Parser\DocTag\Type;
use PhpCmplr\Completer\Parser\DocTag\ObjectType;

class ReflectionComponent extends Component implements ReflectionComponentInterface
{
    /**
     * @var ReflectionComponentInterface[]
     */
    private $reflectionComponents;

    /**
     * @var Method[][] Class name => Method[].
     */
    private $classMethodsCache = [];

    /**
     * @var Property[][] Class name => Property[].
     */
    private $classPropertiesCache = [];

    /**
     * @var Const_[][] Class name => Const_[].
     */
    private $classConstsCache = [];

    public function findClass($fullyQualifiedName)
    {
        $this->run();
        $classes = [];
        foreach ($this->reflectionComponents as $component) {
            $classes = array_merge($classes, $component->findClass($fullyQualifiedName));
        }

        return $classes;
    }

    public function findFunction($fullyQualifiedName)
    {
        $this->run();
        $functions = [];
        foreach ($this->reflectionComponents as $component) {
            $functions = array_merge($functions, $component->findFunction($fullyQualifiedName));
        }

        // TODO: merge returnType and docReturnType, and param types
        return $functions;
    }

    public function findConst($fullyQualifiedName)
    {
        $this->run();
        $consts = [];
        foreach ($this->reflectionComponents as $component) {
            $consts = array_merge($consts, $component->findConst($fullyQualifiedName));
        }

        return $consts;
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @return bool
     */
    public function isClass($fullyQualifiedName) {
        $classes = $this->findClass($fullyQualifiedName);
        return count($classes) >= 1 && $classes[0] instanceof Class_;
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @return bool
     */
    public function isInterface($fullyQualifiedName) {
        $classes = $this->findClass($fullyQualifiedName);
        return count($classes) >= 1 && $classes[0] instanceof Interface_;
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @return bool
     */
    public function isTrait($fullyQualifiedName) {
        $classes = $this->findClass($fullyQualifiedName);
        return count($classes) >= 1 && $classes[0] instanceof Trait_;
    }

    /**
     * @param Method[] $methods
     * @param Method   $method
     */
    protected function mergeMethod(array &$methods, Method $method)
    {
        // TODO: Do a better job here?
        $method->setDocReturnType(Type::alternatives([$method->getDocReturnType(), $method->getReturnType()]));
        $params = [];
        foreach ($method->getParams() as $param) {
            $param->setDocType(Type::alternatives([$param->getDocType(), $param->getTypeHint()]));
            $params[$param->getName()] = $param;
        }
        if (isset($methods[strtolower($method->getName())])) {
            $baseMethod = $methods[strtolower($method->getName())];
            $method->setDocReturnType(Type::alternatives([$method->getDocReturnType(), $baseMethod->getDocReturnType()]));
            foreach ($baseMethod->getParams() as $baseParam) {
                if (array_key_exists($baseParam->getName(), $params)) {
                    $param = $params[$baseParam->getName()];
                    $param->setDocType(Type::alternatives([$param->getDocType(), $baseParam->getDocType()]));
                }
            }
        }
        $methods[strtolower($method->getName())] = $method;
    }

    /**
     * @param Method[] $methods
     * @param Class_   $class
     */
    protected function addMethodsFromBaseClass(array &$methods, Class_ $class)
    {
        if ($class->getExtends() && $this->isClass($class->getExtends())) {
            foreach ($this->findAllMethods($class->getExtends(), false) as $method) {
                $this->mergeMethod($methods, clone $method);
            }
        }
    }

    /**
     * @param Method[] $methods
     * @param Class_   $class
     */
    protected function addMethodsFromImplementedInterfaces(array &$methods, Class_ $class)
    {
        foreach ($class->getImplements() as $interfaceName) {
            if ($this->isInterface($interfaceName)) {
                foreach($this->findAllMethods($interfaceName, false) as $method) {
                    $method = clone $method;
                    $method->setAbstract(true);
                    $this->mergeMethod($methods, $method);
                }
            }
        }
    }

    /**
     * @param Method[]   $methods
     * @param Interface_ $class
     */
    protected function addMethodsFromExtendedInterfaces(array &$methods, Interface_ $class)
    {
        foreach ($class->getExtends() as $interfaceName) {
            if ($this->isInterface($interfaceName)) {
                foreach($this->findAllMethods($interfaceName, false) as $method) {
                    $this->mergeMethod($methods, clone $method);
                }
            }
        }
    }

    /**
     * @param Method[]      $methods
     * @param Class_|Trait_ $class
     */
    protected function addMethodsFromUsedTraits(array &$methods, ClassLike $class)
    {
        $traitMethods = [];

        foreach ($class->getTraits() as $traitName) {
            if ($this->isTrait($traitName)) {
                foreach ($this->findAllMethods($traitName, false) as $method) {
                    $traitMethods[strtolower($traitName)][strtolower($method->getName())] = $method;
                }
            }
        }

        foreach ($class->getTraitAliases() as $alias) {
            if (isset($traitMethods[strtolower($alias->getTrait())][strtolower($alias->getMethod())])) {
                $method = $traitMethods[strtolower($alias->getTrait())][strtolower($alias->getMethod())];
                $method = clone $method;
                $method->setName($alias->getNewName());
                if ($alias->getNewAccessibility() !== null) {
                    $method->setAccessibility($alias->getNewAccessibility());
                }
                $this->mergeMethod($methods, $method);
            }
        }

        foreach ($class->getTraitInsteadOfs() as $insteadOf) {
            foreach ($insteadOf->getInsteadOfs() as $insteadOfTrait) {
                unset($traitMethods[strtolower($insteadOfTrait)][strtolower($insteadOf->getMethod())]);
            }
        }

        foreach ($traitMethods as $traitTraitMethods) {
            foreach ($traitTraitMethods as $method) {
                $this->mergeMethod($methods, clone $method);
            }
        }
    }

    /**
     * @param Method[]  $methods
     * @param ClassLike $class
     */
    protected function addOwnMethods(array &$methods, ClassLike $class)
    {
        foreach ($class->getMethods() as $method) {
            $this->mergeMethod($methods, clone $method);
        }
    }

    /**
     * @internal
     *
     * @param Type $type
     *
     * @return Type
     */
    public function resolveType(Type $type) {
        if ($type instanceof ObjectType) {
            if ($type->getClass() === 'self') {
                return Type::object_($class->getName());
            } elseif ($type->getClass() === 'parent' && $parent !== null) {
                return Type::object_($parent);
            } elseif ($type->getClass() === 'static' && $withStatic) {
                return Type::object_($class->getName());
            }
        }
        return $type;
    }

    /**
     * Resolve `self`, `parent` and optionally `static` types.
     *
     * @param Method[]  $methods
     * @param ClassLike $class
     * @param bool      $withStatic Whether to resolve `static` as $class.
     */
    protected function resolveMethodTypes(array &$methods, ClassLike $class, $withStatic)
    {
        $parent = ($class instanceof Class_ && $class->getExtends()) ? $class->getExtends() : null;
        $transformer = [$this, 'resolveType'];

        foreach ($methods as $method) {
            $method->setReturnType($method->getReturnType()->walk($transformer));
            $method->setDocReturnType($method->getDocReturnType()->walk($transformer));
            foreach ($method->getParams() as $param) {
                $param->setTypeHint($param->getTypeHint()->walk($transformer));
                $param->setDocType($param->getDocType()->walk($transformer));
            }
        }
    }

    /**
     * Find all methods of a class, including base classes, used traits and implemented interfaces.
     *
     * @param string $className
     * @param bool   $bottom    Internal. Whether at the bottom of inheritance tree.
     *
     * @return Method[]
     */
    public function findAllMethods($className, $bottom = true)
    {
        if (array_key_exists(strtolower($className), $this->classMethodsCache)) {
            return $this->classMethodsCache[strtolower($className)];
        }

        $classes = $this->findClass($className);
        if (!$classes) {
            return [];
        }

        $class = $classes[0];
        /** @var Method[] $methods name => Method. */
        $methods = [];

        if ($class instanceof Class_) {
            $this->addMethodsFromImplementedInterfaces($methods, $class);
            $this->addMethodsFromBaseClass($methods, $class);
        }

        if ($class instanceof Interface_) {
            $this->addMethodsFromExtendedInterfaces($methods, $class);
        }

        if ($class instanceof Class_ || $class instanceof Trait_) {
            $this->addMethodsFromUsedTraits($methods, $class);
        }

        $this->addOwnMethods($methods, $class);

        $this->resolveMethodTypes($methods, $class, $bottom);

        return $this->classMethodsCache[strtolower($className)] = $methods;
    }

    /**
     * @param string $className
     * @param string $methodName
     *
     * @return Method
     */
    public function findMethod($className, $methodName)
    {
        $methods = $this->findAllMethods($className);
        if (array_key_exists(strtolower($methodName), $methods)) {
            return $methods[strtolower($methodName)];
        }

        return null;
    }

    /**
     * @param Property[] $properties
     * @param Property   $property
     */
    protected function mergeProperty(array &$properties, $property)
    {
        $properties[$property->getName()] = $property;
    }

    /**
     * @param Property[] $properties
     * @param Class_     $class
     */
    protected function addPropertiesFromBaseClass(array &$properties, Class_ $class)
    {
        if ($class->getExtends() && $this->isClass($class->getExtends())) {
            foreach ($this->findAllProperties($class->getExtends(), false) as $property) {
                $this->mergeProperty($properties, clone $property);
            }
        }
    }

    /**
     * @param Property[]    $properties
     * @param Class_|Trait_ $class
     */
    protected function addPropertiesFromUsedTraits(array &$properties, ClassLike $class)
    {
        foreach ($class->getTraits() as $traitName) {
            if ($this->isTrait($traitName)) {
                foreach ($this->findAllProperties($traitName, false) as $property) {
                    $this->mergeProperty($properties, clone $property);
                }
            }
        }
    }

    /**
     * @param Property[] $properties
     * @param ClassLike  $class
     */
    protected function addOwnProperties(array &$properties, ClassLike $class)
    {
        foreach ($class->getProperties() as $property) {
            $this->mergeProperty($properties, clone $property);
        }
    }

    /**
     * Resolve `self`, `parent` and optionally `static` types.
     *
     * @param Property[] $properties
     * @param ClassLike  $class
     * @param bool       $withStatic Whether to resolve `static` as $class.
     */
    protected function resolvePropertyTypes(array &$properties, ClassLike $class, $withStatic)
    {
        $parent = ($class instanceof Class_ && $class->getExtends()) ? $class->getExtends() : null;
        $transformer = [$this, 'resolveType'];

        foreach ($properties as $property) {
            $property->setType($property->getType()->walk($transformer));
        }
    }

    /**
     * Find all properties of a class, including base classes and used traits.
     *
     * @param string $className
     * @param bool   $bottom    Internal. Whether at the bottom of inheritance tree.
     *
     * @return Property[]
     */
    public function findAllProperties($className, $bottom = true)
    {
        if (array_key_exists(strtolower($className), $this->classPropertiesCache)) {
            return $this->classPropertiesCache[strtolower($className)];
        }

        $classes = $this->findClass($className);
        if (!$classes) {
            return [];
        }

        $class = $classes[0];
        /** @var Property[] $properties name => Property. */
        $properties = [];

        if ($class instanceof Class_) {
            $this->addPropertiesFromBaseClass($properties, $class);
        }

        if ($class instanceof Class_ || $class instanceof Trait_) {
            $this->addPropertiesFromUsedTraits($properties, $class);
        }

        $this->addOwnProperties($properties, $class);

        $this->resolvePropertyTypes($properties, $class, $bottom);

        return $this->classPropertiesCache[strtolower($className)] = $properties;
    }

    /**
     * @param string $className
     * @param string $propertyName
     *
     * @return Property
     */
    public function findProperty($className, $propertyName)
    {
        $properties = $this->findAllProperties($className);
        if (array_key_exists(strtolower($propertyName), $properties)) {
            return $properties[strtolower($propertyName)];
        }

        return null;
    }

    /**
     * @param Const_[] $consts
     * @param Const_   $const
     */
    protected function mergeConst(array &$consts, Const_ $const)
    {
        $consts[$const->getName()] = $const;
    }

    /**
     * @param Const_[] $consts
     * @param Class_   $class
     */
    protected function addConstsFromBaseClass(array &$consts, Class_ $class)
    {
        if ($class->getExtends() && $this->isClass($class->getExtends())) {
            foreach ($this->findAllClassConsts($class->getExtends(), false) as $const) {
                $this->mergeConst($consts, clone $const);
            }
        }
    }

    /**
     * @param Const_[] $consts
     * @param Class_   $class
     */
    protected function addConstsFromImplementedInterfaces(array &$consts, Class_ $class)
    {
        foreach ($class->getImplements() as $interfaceName) {
            if ($this->isInterface($interfaceName)) {
                foreach($this->findAllClassConsts($interfaceName, false) as $const) {
                    $this->mergeConst($consts, clone $const);
                }
            }
        }
    }

    /**
     * @param Const_[]   $consts
     * @param Interface_ $class
     */
    protected function addConstsFromExtendedInterfaces(array &$consts, Interface_ $class)
    {
        foreach ($class->getExtends() as $interfaceName) {
            if ($this->isInterface($interfaceName)) {
                foreach($this->findAllClassConsts($interfaceName, false) as $const) {
                    $this->mergeConst($consts, clone $const);
                }
            }
        }
    }

    /**
     * @param Const_[]  $consts
     * @param ClassLike $class
     */
    protected function addOwnConsts(array &$consts, ClassLike $class)
    {
        foreach ($class->getConsts() as $const) {
            $this->mergeConst($consts, clone $const);
        }
    }

    /**
     * Find all consts of a class, including base classes and implemented interfaces.
     *
     * @param string $className
     *
     * @return Const_[]
     */
    public function findAllClassConsts($className)
    {
        if (array_key_exists(strtolower($className), $this->classConstsCache)) {
            return $this->classConstsCache[strtolower($className)];
        }

        $classes = $this->findClass($className);
        if (!$classes) {
            return [];
        }

        $class = $classes[0];
        /** @var Const[] $consts name => Const. */
        $consts = [];

        if ($class instanceof Class_) {
            $this->addConstsFromImplementedInterfaces($consts, $class);
            $this->addConstsFromBaseClass($consts, $class);
        }

        if ($class instanceof Interface_) {
            $this->addConstsFromExtendedInterfaces($consts, $class);
        }

        $this->addOwnConsts($consts, $class);

        return $this->classConstsCache[strtolower($className)] = $consts;
    }

    /**
     * @param string $className
     * @param string $constName
     *
     * @return Const_
     */
    public function findClassConst($className, $constName)
    {
        $consts = $this->findAllClassConsts($className);
        if (array_key_exists(strtolower($constName), $consts)) {
            return $consts[strtolower($constName)];
        }

        return null;
    }

    protected function doRun()
    {
        $this->reflectionComponents = $this->container->getByTag('reflection.component');
    }
}
