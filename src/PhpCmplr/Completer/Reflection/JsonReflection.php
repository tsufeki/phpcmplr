<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\Type\Type;

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
use PhpCmplr\Completer\Reflection\Element\Trait_;
use PhpCmplr\Completer\Reflection\Element\Variable;

class JsonReflection extends Component implements ReflectionInterface
{
    const TYPE_ALIASES = [
        'number' => 'int|float',
        'scalar' => 'int|float|bool|string',
    ];

    /**
     * @var string
     */
    private $path;

    /**
     * @var ClassLike[]
     */
    private $classes = [];

    /**
     * @var Function_[]
     */
    private $functions = [];

    /**
     * @var Consts_[]
     */
    private $consts = [];

    /**
     * @param Container $container
     * @param string $path
     */
    public function __construct(Container $container, $path)
    {
        parent::__construct($container);
        $this->path = $path;
    }

    public function findClass($fullyQualifiedName)
    {
        $this->run();
        $fullyQualifiedName = strtolower($fullyQualifiedName);
        return empty($this->classes[$fullyQualifiedName]) ? [] : [$this->classes[$fullyQualifiedName]];
    }

    public function findFunction($fullyQualifiedName)
    {
        $this->run();
        $fullyQualifiedName = strtolower($fullyQualifiedName);
        return empty($this->functions[$fullyQualifiedName]) ? [] : [$this->functions[$fullyQualifiedName]];
    }

    public function findConst($fullyQualifiedName)
    {
        $this->run();
        return empty($this->consts[$fullyQualifiedName]) ? [] : [$this->consts[$fullyQualifiedName]];
    }

    /**
     * Get all classes, interfaces and traits from this file.
     *
     * @return ClassLike[]
     */
    public function getClasses()
    {
        $this->run();
        return array_values($this->classes);
    }

    /**
     * Get all functions from this file.
     *
     * @return Function_[]
     */
    public function getFunctions()
    {
        $this->run();
        return array_values($this->functions);
    }

    /**
     * Get all non-class consts from this file.
     *
     * @return Const_[]
     */
    public function getConsts()
    {
        $this->run();
        return array_values($this->consts);
    }

    protected function getType($typeString)
    {
        if (array_key_exists($typeString, static::TYPE_ALIASES)) {
            $typeString = static::TYPE_ALIASES[$typeString];
        }

        return empty($typeString) ? Type::mixed_() : Type::fromString($typeString);
    }

    /**
     * @param Element $element
     * @param array   $data
     */
    protected function handleElement(Element $element,  array $data)
    {
        $element->setName($data['name']);
    }

    protected function handleConst(Const_ $const, array $data)
    {
        $this->handleElement($const, $data);
    }

    protected function handleFunction(Function_ $function, array $data)
    {
        $this->handleElement($function, $data);
        $function->setReturnType(Type::mixed_());
        $function->setDocReturnType($this->getType($data['return_type']));
        $function->setReturnByRef($data['return_by_ref']);

        foreach ($data['params'] as $paramData) {
            $param = new Param();
            $param->setName($paramData['name']);
            $param->setTypeHint(Type::mixed_());
            $param->setDocType($this->getType($paramData['type']));
            $param->setOptional($paramData['optional']);
            $param->setByRef($paramData['by_ref']);
            $param->setVariadic($paramData['variadic']);
            $function->addParam($param);
        }
    }

    protected function handleMember($member, $data)
    {
        $access = ClassLike::M_PUBLIC;
        if (in_array('protected', $data['modifiers'])) {
            $access = ClassLike::M_PROTECTED;
        } elseif (in_array('private', $data['modifiers'])) {
            $access = ClassLike::M_PRIVATE;
        }
        $member->setAccessibility($access);

        $member->setStatic(in_array('static', $data['modifiers']));
    }

    protected function handleClassLike(ClassLike $class, $data)
    {
        $this->handleElement($class, $data);

        foreach ($data['constants'] as $constData) {
            $const = new ClassConst();
            $this->handleConst($const, $constData);
            $const->setClass($class);
            $class->addConst($const);
        }

        foreach ($data['properties'] as $propData) {
            $prop = new Property();
            $this->handleElement($prop, $propData);
            $prop->setType($this->getType($propData['type']));
            $prop->setClass($class);
            $class->addProperty($prop);
        }

        foreach ($data['methods'] as $methodData) {
            $method = new Method();
            $this->handleFunction($method, $methodData);
            $this->handleMember($method, $methodData);
            $method->setAbstract(in_array('abstract', $methodData['modifiers']));
            $method->setFinal(in_array('final', $methodData['modifiers']));
            $method->setClass($class);
            $class->addMethod($method);
        }
    }

    protected function handleClass(Class_ $class, $data)
    {
        $this->handleClassLike($class, $data);
        $class->setAbstract(in_array('abstract', $data['modifiers']));
        $class->setFinal(in_array('final', $data['modifiers']));
        $class->setExtends($data['extends']);
        foreach ($data['implements'] as $implements) {
            $class->addImplements($implements);
        }
    }

    protected function handleInterface(Interface_ $interface, $data)
    {
        $this->handleClassLike($interface, $data);
        foreach ($data['extends'] as $extends) {
            $interface->addExtends($extends);
        }
    }

    protected function addBackslash(Element $element)
    {
        $element->setName('\\' . $element->getName());
    }

    protected function doRun()
    {
        // TODO: validation.
        // TODO: traits.
        $data = json_decode($this->container->get('io')->read($this->path), true);

        $functionAliases = [];
        foreach ($data['functions'] as $functionData) {
            if ($functionData['kind'] === 'alias') {
                $functionAliases[$functionData['name']] = $functionData['aliased_name'];
            } else {
                $function = new Function_();
                $this->handleFunction($function, $functionData);
                $this->addBackslash($function);
                $this->functions[strtolower($function->getName())] = $function;
            }
        }
        foreach ($functionAliases as $alias => $name) {
            $function = clone $this->findFunction('\\' . $name)[0];
            $function->setName($alias);
            $this->addBackslash($function);
            $this->functions[strtolower($function->getName())] = $function;
        }

        foreach ($data['classes'] as $classData) {
            $class = new Class_();
            $this->handleClass($class, $classData);
            $this->addBackslash($class);
            $this->classes[strtolower($class->getName())] = $class;
        }

        foreach ($data['interfaces'] as $interfaceData) {
            $interface = new Interface_();
            $this->handleInterface($interface, $interfaceData);
            $this->addBackslash($interface);
            $this->interfaces[strtolower($interface->getName())] = $interface;
        }

        foreach ($data['constants'] as $constData) {
            $const = new Const_();
            $this->handleConst($const, $constData);
            $this->addBackslash($const);
            $this->consts[$const->getName()] = $const;
        }
    }
}
