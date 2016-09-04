<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Type\ObjectType;

use PhpCmplr\Completer\Reflection\Element\ClassConst;
use PhpCmplr\Completer\Reflection\Element\ClassLike;
use PhpCmplr\Completer\Reflection\Element\Class_;
use PhpCmplr\Completer\Reflection\Element\Const_;
use PhpCmplr\Completer\Reflection\Element\Element;
use PhpCmplr\Completer\Reflection\Element\Function_;
use PhpCmplr\Completer\Reflection\Element\Interface_;
use PhpCmplr\Completer\Reflection\Element\Method;
use PhpCmplr\Completer\Reflection\Element\Param;
use PhpCmplr\Completer\Reflection\Element\Property;
use PhpCmplr\Completer\Reflection\Element\TraitAlias;
use PhpCmplr\Completer\Reflection\Element\TraitInsteadOf;
use PhpCmplr\Completer\Reflection\Element\Trait_;
use PhpCmplr\Completer\Reflection\Element\Variable;

class Reflection extends Component
{
    /**
     * @var ReflectionInterface[]
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
     * @var ClassConst[][] Class name => ClassConst[].
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
            foreach ($this->findAllMethods($class->getExtends()) as $method) {
                if ($method->getAccessibility() !== ClassLike::M_PRIVATE) {
                    $this->mergeMethod($methods, clone $method);
                }
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
                foreach($this->findAllMethods($interfaceName) as $method) {
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
                foreach($this->findAllMethods($interfaceName) as $method) {
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
                foreach ($this->findAllMethods($traitName) as $method) {
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
     * Find all methods of a class, including base classes, used traits and implemented interfaces.
     *
     * @param string $className
     *
     * @return Method[]
     */
    public function findAllMethods($className)
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
            foreach ($this->findAllProperties($class->getExtends()) as $property) {
                if ($property->getAccessibility() !== ClassLike::M_PRIVATE) {
                    $this->mergeProperty($properties, clone $property);
                }
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
                foreach ($this->findAllProperties($traitName) as $property) {
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
     * Find all properties of a class, including base classes and used traits.
     *
     * @param string $className
     *
     * @return Property[]
     */
    public function findAllProperties($className)
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
        if (array_key_exists($propertyName, $properties)) {
            return $properties[$propertyName];
        }

        return null;
    }

    /**
     * @param ClassConst[] $consts
     * @param ClassConst   $const
     */
    protected function mergeConst(array &$consts, ClassConst $const)
    {
        $consts[$const->getName()] = $const;
    }

    /**
     * @param ClassConst[] $consts
     * @param Class_       $class
     */
    protected function addConstsFromBaseClass(array &$consts, Class_ $class)
    {
        if ($class->getExtends() && $this->isClass($class->getExtends())) {
            foreach ($this->findAllClassConsts($class->getExtends()) as $const) {
                $this->mergeConst($consts, clone $const);
            }
        }
    }

    /**
     * @param ClassConst[] $consts
     * @param Class_       $class
     */
    protected function addConstsFromImplementedInterfaces(array &$consts, Class_ $class)
    {
        foreach ($class->getImplements() as $interfaceName) {
            if ($this->isInterface($interfaceName)) {
                foreach($this->findAllClassConsts($interfaceName) as $const) {
                    $this->mergeConst($consts, clone $const);
                }
            }
        }
    }

    /**
     * @param ClassConst[] $consts
     * @param Interface_   $class
     */
    protected function addConstsFromExtendedInterfaces(array &$consts, Interface_ $class)
    {
        foreach ($class->getExtends() as $interfaceName) {
            if ($this->isInterface($interfaceName)) {
                foreach($this->findAllClassConsts($interfaceName) as $const) {
                    $this->mergeConst($consts, clone $const);
                }
            }
        }
    }

    /**
     * @param ClassConst[] $consts
     * @param ClassLike    $class
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
     * @return ClassConst[]
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
     * @return ClassConst
     */
    public function findClassConst($className, $constName)
    {
        $consts = $this->findAllClassConsts($className);
        if (array_key_exists($constName, $consts)) {
            return $consts[$constName];
        }

        return null;
    }

    protected function doRun()
    {
        $this->reflectionComponents = $this->container->getByTag('reflection');
    }
}
