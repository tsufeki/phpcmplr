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

    /**
     * Find semantic information about a class, interface or trait.
     *
     * @param string $fullyQualifiedName
     *
     * @return ClassLike[]
     */
    public function findClass($fullyQualifiedName)
    {
        $this->run();
        $classes = [];
        foreach ($this->reflectionComponents as $component) {
            $classes = array_merge($classes, $component->findClass($fullyQualifiedName));
        }

        return $this->unique($classes);
    }

    /**
     * Find semantic information about a function (not a method).
     *
     * @param string $fullyQualifiedName
     *
     * @return Function_[]
     */
    public function findFunction($fullyQualifiedName)
    {
        $this->run();
        $functions = [];
        foreach ($this->reflectionComponents as $component) {
            $functions = array_merge($functions, $component->findFunction($fullyQualifiedName));
        }

        // TODO: merge returnType and docReturnType, and param types
        return $this->unique($functions);
    }

    /**
     * Find semantic information about a non-class const.
     *
     * @param string $fullyQualifiedName
     *
     * @return Const_[]
     */
    public function findConst($fullyQualifiedName)
    {
        $this->run();
        $consts = [];
        foreach ($this->reflectionComponents as $component) {
            $consts = array_merge($consts, $component->findConst($fullyQualifiedName));
        }

        return $this->unique($consts);
    }

    /**
     * @param Element[] $elements
     *
     * @return Element[]
     */
    private function unique(array $elements)
    {
        $uniq = [];
        foreach ($elements as $element) {
            $uniq[$element->getName()] = $element;
        }

        return array_values($uniq);
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
     * @param string $subclassName
     * @param string $superclassName
     *
     * @return bool
     */
    public function isSubclass($subclassName, $superclassName)
    {
        if ($subclassName === $superclassName) {
            return true;
        }

        $subclasses = $this->findClass($subclassName);
        if (count($subclasses) === 0) {
            return false;
        }

        $subclass = $subclasses[0];
        if ($subclass instanceof Class_) {
            if ($subclass->getExtends() !== null && $this->isSubclass($subclass->getExtends(), $superclassName)) {
                return true;
            }
            foreach ($subclass->getImplements() as $interfaceName) {
                if ($this->isSubclass($interfaceName, $superclassName)) {
                    return true;
                }
            }
        } elseif ($subclass instanceof Interface_) {
            foreach ($subclass->getExtends() as $interfaceName) {
                if ($this->isSubclass($interfaceName, $superclassName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Type $type1
     * @param Type $type2
     *
     * @return Type|null
     */
    protected function getCommonTypeStrict(Type $type1, Type $type2)
    {
        if ($type1->equals(Type::mixed_()) ) {
            return $type2;
        }
        if ($type2->equals(Type::mixed_()) ) {
            return $type1;
        }
        if ($type1->equals($type2)) {
            return $type1;
        }

        if ($type1->getName() === 'array' && $type2->getName() === 'array') {
            $valueType = $this->getCommonTypeStrict($type1->getValueType(), $type2->getValueType());
            $keyType = $this->getCommonTypeStrict($type1->getKeyType(), $type2->getKeyType());
            if ($valueType !== null && $keyType !== null) {
                return Type::array_($valueType, $keyType);
            }
        }

        if ($type1->getName() === 'object' && $type2->getName() === 'object') {
            if ($type1->getClass() === null || ($type2->getClass() !== null &&
                    $this->isSubclass($type2->getClass(), $type1->getClass()))) {
                return $type2;
            }
            if ($type2->getClass() === null || ($type1->getClass() !== null &&
                    $this->isSubclass($type1->getClass(), $type2->getClass()))) {
                return $type1;
            }
        }

        if ($type1->getName() === 'alternatives' || $type2->getName() === 'alternatives') {
            $alts1 = $type1->getName() === 'alternatives' ? $type1->getAlternatives() : [$type1];
            $alts2 = $type2->getName() === 'alternatives' ? $type2->getAlternatives() : [$type2];
            $result = [];
            foreach ($alts1 as $alt1) {
                foreach ($alts2 as $alt2) {
                    $common = $this->getCommonTypeStrict($alt1, $alt2);
                    if ($common !== null) {
                        $result[] = $common;
                    }
                }
            }
            if (!empty($result)) {
                return Type::alternatives($result);
            }
        }

        return null;
    }

    /**
     * @param Type $type1
     * @param Type $type2
     *
     * @return Type|null
     */
    protected function getCommonType(Type $type1, Type $type2)
    {
        $common = $this->getCommonTypeStrict($type1, $type2);
        if ($common === null) {
            $common = Type::alternatives([$type1, $type2]);
        }

        return $common;
    }

    /**
     * @param Method[] $methods
     * @param Method   $method
     */
    protected function mergeMethod(array &$methods, Method $method)
    {
        $method->setDocReturnType($this->getCommonType($method->getDocReturnType(), $method->getReturnType()));
        $params = [];
        foreach ($method->getParams() as $param) {
            $param->setDocType($this->getCommonType($param->getDocType(), $param->getTypeHint()));
            $params[$param->getName()] = $param;
        }
        if (isset($methods[strtolower($method->getName())])) {
            $baseMethod = $methods[strtolower($method->getName())];
            $method->setDocReturnType($this->getCommonType($method->getDocReturnType(), $baseMethod->getDocReturnType()));
            foreach ($baseMethod->getParams() as $baseParam) {
                if (array_key_exists($baseParam->getName(), $params)) {
                    $param = $params[$baseParam->getName()];
                    $newType = $baseParam->getDocType();
                    if (!$param->getDocType()->equals(Type::mixed_())) {
                        $newType = Type::alternatives([$param->getDocType(), $newType]);
                    }
                    $param->setDocType($newType);
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
        $method = null;
        $methods = $this->findAllMethods($className);
        if (array_key_exists(strtolower($methodName), $methods)) {
            $method = $methods[strtolower($methodName)];
        } elseif (array_key_exists(strtolower('__call'), $methods)) {
            $call = $methods[strtolower('__call')];
            $method = new Method();
            $method->setName('*');
            $method->setReturnType($call->getReturnType());
            $method->setDocReturnType($call->getDocReturnType());
            $method->setLocation($call->getLocation());
        } elseif (array_key_exists(strtolower('__callStatic'), $methods)) {
            $call = $methods[strtolower('__callStatic')];
            $method = new Method();
            $method->setStatic(true);
            $method->setName('*');
            $method->setReturnType($call->getReturnType());
            $method->setDocReturnType($call->getDocReturnType());
            $method->setLocation($call->getLocation());
        }

        return $method;
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
        $property = null;
        $properties = $this->findAllProperties($className);
        if (array_key_exists($propertyName, $properties)) {
            $property = $properties[$propertyName];
        } elseif (($get = $this->findMethod($className, '__get')) !== null) {
            $property = new Property();
            $property->setName('*');
            $property->setType($get->getDocReturnType());
            $property->setLocation($get->getLocation());
        } elseif (strtolower($className) === '\\stdclass') {
            $property = new Property();
            $property->setName('*');
            $property->setType(Type::fromString('\\stdClass|\\stdClass[]|mixed'));
        }

        return $property;
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

        if (!($class instanceof Trait_)) {
            $classClassConst = new ClassConst();
            $classClassConst->setName('class');
            $classClassConst->setClass($class);
            $this->mergeConst($consts, $classClassConst);
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
        /** @var ClassConst[] $consts name => Const. */
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

    /**
     * @param Method[]|Property[] $members
     * @param string|null         $contextClassName
     *
     * @return Method[]|Property[]
     */
    public function filterAvailableMembers(array $members, $contextClassName = null)
    {
        // TODO: Take used traits into account
        $result = [];
        /** @var Method|Property $member */
        foreach ($members as $member) {
            switch ($member->getAccessibility()) {
                case ClassLike::M_PUBLIC:
                    $result[] = $member;
                    break;

                case ClassLike::M_PROTECTED:
                    if ($contextClassName !== null && (
                        $this->isSubclass($contextClassName, $member->getClass()->getName()) ||
                        $this->isSubclass($member->getClass()->getName(), $contextClassName)
                    )) {
                        $result[] = $member;
                    }
                    break;

                case ClassLike::M_PRIVATE:
                    if ($contextClassName === $member->getClass()->getName()) {
                        $result[] = $member;
                    }
                    break;
            }
        }

        return $result;
    }

    protected function doRun()
    {
        $this->reflectionComponents = $this->container->getByTag('reflection');
    }
}
