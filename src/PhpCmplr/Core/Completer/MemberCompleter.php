<?php

namespace PhpCmplr\Core\Completer;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Name;

use PhpCmplr\Core\Component;
use PhpCmplr\Core\Parser\Parser;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Core\Type\ObjectType;
use PhpCmplr\Core\Type\AlternativesType;
use PhpCmplr\Core\Reflection\Reflection;
use PhpCmplr\Core\Reflection\Element\Class_;
use PhpCmplr\Core\Reflection\Element\Function_;
use PhpCmplr\Core\Reflection\Element\Method;
use PhpCmplr\Core\Reflection\Element\Variable;
use PhpCmplr\Core\Reflection\Element\Property;
use PhpCmplr\Core\Reflection\Element\Const_;
use PhpCmplr\Core\Reflection\Element\ClassConst;

class MemberCompleter extends Component implements CompleterInterface
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Reflection
     */
    private $reflection;

    /**
     * @param Type        $objectType
     * @param string|null $contextClass
     * @param bool        $staticContext
     *
     * @return Method[]
     */
    protected function findMethods(Type $objectType, $contextClass = null, $staticContext = false)
    {
        $methods = [];

        if ($objectType instanceof AlternativesType) {
            foreach ($objectType->getAlternatives() as $altType) {
                $methods = array_merge($methods, $this->findMethods($altType, $staticContext));
            }

        } elseif ($objectType instanceof ObjectType) {
            foreach ($this->reflection->findAllMethods($objectType->getClass()) as $method) {
                if (!$staticContext || $method->isStatic()) {
                    $methods[] = $method;
                }
            }
            $methods = $this->reflection->filterAvailableMembers($objectType->getClass(), $methods, $contextClass);
        }

        return $methods;
    }

    /**
     * @param Type        $objectType
     * @param string|null $contextClass
     * @param bool        $staticContext
     *
     * @return Property[]
     */
    protected function findProperties(Type $objectType, $contextClass = null, $staticContext = false)
    {
        $properties = [];

        if ($objectType instanceof AlternativesType) {
            foreach ($objectType->getAlternatives() as $altType) {
                $properties = array_merge($properties, $this->findProperties($altType, $staticContext));
            }

        } elseif ($objectType instanceof ObjectType) {
            foreach ($this->reflection->findAllProperties($objectType->getClass()) as $property) {
                if ($staticContext === $property->isStatic()) {
                    $properties[] = $property;
                }
            }
            $properties = $this->reflection->filterAvailableMembers($objectType->getClass(), $properties, $contextClass);
        }

        return $properties;
    }

    /**
     * @param Type $objectType
     *
     * @return ClassConst[]
     */
    protected function findClassConsts(Type $objectType)
    {
        $consts = [];

        if ($objectType instanceof AlternativesType) {
            foreach ($objectType->getAlternatives() as $altType) {
                $consts = array_merge($consts, $this->findClassConsts($altType));
            }

        } elseif ($objectType instanceof ObjectType) {
            foreach ($this->reflection->findAllClassConsts($objectType->getClass()) as $const) {;
                $consts[] = $const;
            }
        }

        return $consts;
    }

    /**
     * @param Method[] $methods
     *
     * @return Completion[]
     */
    public function formatMethods(array $methods)
    {
        $completions = [];
        /** @var Method $method */
        foreach ($methods as $method) {
            $completion = new Completion();
            $zeroParams = count($method->getParams()) === 0;
            $completion->setInsertion($method->getName() . ($zeroParams ? '()' : '('));
            $completion->setDisplay($completion->getInsertion());
            $completion->setKind($method->isStatic() ? Completion::KIND_STATIC_METHOD : Completion::KIND_METHOD);
            $completion->setExtendedDisplay(
                $method->getParamsAsString(true) . ($zeroParams ? '' : ')') .
                ': ' . $method->getDocReturnType()->toString(true)
            );
            $completions[] = $completion;
        }
        return $completions;
    }

    /**
     * @param Property[] $properties
     * @param bool       $staticContext
     *
     * @return Completion[]
     */
    public function formatProperties(array $properties, $staticContext = false)
    {
        $completions = [];
        foreach ($properties as $property) {
            $completion = new Completion();
            $completion->setInsertion($staticContext ? $property->getName() : ltrim($property->getName(), '$'));
            $completion->setDisplay($completion->getInsertion());
            $completion->setKind($property->isStatic() ? Completion::KIND_STATIC_PROPERTY : Completion::KIND_PROPERTY);
            $completion->setExtendedDisplay($property->getType()->toString(true));
            $completions[] = $completion;
        }
        return $completions;
    }

    /**
     * @param ClassConst[] $consts
     *
     * @return Completion[]
     */
    public function formatClassConsts(array $consts)
    {
        $completions = [];
        foreach ($consts as $const) {
            $completion = new Completion();
            $completion->setInsertion($const->getName());
            $completion->setDisplay($completion->getInsertion());
            $completion->setKind(Completion::KIND_CLASS_CONST);
            $completions[] = $completion;
        }
        return $completions;
    }

    public function complete($offset)
    {
        $this->run();
        $nodes = $this->parser->getNodesAtOffset($offset, true);

        $node = null;
        if (count($nodes) > 0) {
            $node = $nodes[0];
            if ($node instanceof Expr\Error) {
                $node = count($nodes) > 1 ? $nodes[1] : null;
            }
        }

        $completions = [];
        $ctxClass = null;
        foreach ($nodes as $ctxNode) {
            if ($ctxNode instanceof Stmt\ClassLike) {
                $ctxClass = $ctxNode->hasAttribute('namespacedName')
                    ? Type::nameToString($ctxNode->getAttribute('namespacedName'))
                    : $node->name;
                break;
            }
        }

        if ($node instanceof Expr\MethodCall || $node instanceof Expr\PropertyFetch) {
            $methods = $this->findMethods($node->var->getAttribute('type'), $ctxClass);
            $properties = $this->findProperties($node->var->getAttribute('type'), $ctxClass);

            $methods = array_filter($methods, function (Method $method) {
                return substr_compare($method->getName(), '__', 0, 2) !== 0;
            });

            $completions = array_merge(
                $this->formatMethods($methods),
                $this->formatProperties($properties));

        } elseif ($node instanceof Expr\StaticCall || $node instanceof Expr\StaticPropertyFetch ||
                $node instanceof Expr\ClassConstFetch) {
            // TODO: Support static call on an object: $object::staticMethod() etc.
            // TODO: Filter out non-static members outside the inheritance
            //       chain - while static calls to them are allowed in PHP, they
            //       are pretty useless.
            $staticOnly = true;
            foreach ($nodes as $ctxNode) {
                if ($ctxNode instanceof Stmt\ClassMethod) {
                    $staticOnly = $ctxNode->isStatic();
                    break;
                }
            }

            $methods = $this->findMethods(Type::object_(Type::nameToString($node->class)), $ctxClass, $staticOnly);
            $properties = $this->findProperties(Type::object_(Type::nameToString($node->class)), $ctxClass, $staticOnly);
            $consts = $this->findClassConsts(Type::object_(Type::nameToString($node->class)));

            $methods = array_filter($methods, function (Method $method) {
                return substr_compare($method->getName(), '__', 0, 2) !== 0;
            });

            $completions = array_merge(
                $this->formatMethods($methods),
                $this->formatClassConsts($consts),
                $this->formatProperties($properties, true));
        }

        return $completions;
    }

    protected function doRun()
    {
        $this->parser = $this->container->get('parser');
        $this->reflection = $this->container->get('reflection');
        $this->container->get('typeinfer')->run();
    }
}
