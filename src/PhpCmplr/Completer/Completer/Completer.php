<?php

namespace PhpCmplr\Completer\Completer;

use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Name;
use PhpLenientParser\Node\Identifier;
use PhpLenientParser\Node\ErrorNode;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Parser\Parser;
use PhpCmplr\Completer\Type\Type;
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

class Completer extends Component implements CompleterInterface
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
     * @param Type $objectType
     * @param bool $staticContext
     *
     * @return Method[]
     */
    protected function findMethods(Type $objectType, $staticContext = false)
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
        }

        return $methods;
    }

    /**
     * @param Type $objectType
     * @param bool $staticContext
     *
     * @return Property[]
     */
    protected function findProperties(Type $objectType, $staticContext = false)
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
        foreach ($methods as $method) {
            $completion = new Completion();
            $completion->setInsertion($method->getName());
            $completion->setDisplay($method->getName() . $method->getParamsAsString(true));
            $completion->setKind($method->isStatic() ? 'static_method' : 'method');
            $completion->setType($method->getDocReturnType()->toString(true));
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
            $completion->setDisplay($property->getName());
            $completion->setKind($property->isStatic() ? 'static_property' : 'property');
            $completion->setType($property->getType()->toString(true));
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
            $completion->setDisplay($const->getName());
            $completion->setKind('class_const');
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
            if ($node instanceof Identifier || $node instanceof ErrorNode\Nothing) {
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
            $methods = $this->findMethods($node->var->getAttribute('type'));
            $properties = $this->findProperties($node->var->getAttribute('type'));

            $methods = $this->reflection->filterAvailableMembers($methods, $ctxClass);
            $properties = $this->reflection->filterAvailableMembers($properties, $ctxClass);

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

            $methods = $this->findMethods(Type::object_(Type::nameToString($node->class)), $staticOnly);
            $properties = $this->findProperties(Type::object_(Type::nameToString($node->class)), $staticOnly);
            $consts = $this->findClassConsts(Type::object_(Type::nameToString($node->class)));

            $methods = $this->reflection->filterAvailableMembers($methods, $ctxClass);
            $properties = $this->reflection->filterAvailableMembers($properties, $ctxClass);

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
