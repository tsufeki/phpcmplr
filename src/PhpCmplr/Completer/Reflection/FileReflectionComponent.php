<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\NodeTraverserComponent;

class FileReflectionComponent extends NodeTraverserComponent implements ReflectionComponentInterface
{
    /**
     * @var FileReflectionNodeVisitor
     */
    private $visitor;

    /**
     * @var ClassLike[]
     */
    private $classes;

    /**
     * @var Function_[]
     */
    private $functions;

    /**
     * @var Const_[]
     */
    private $consts;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->visitor = new FileReflectionNodeVisitor($this->container->get('file')->getPath());
        $this->addVisitor($this->visitor);
    }

    public function findClass($fullyQualifiedName)
    {
        $this->run();
        return empty($this->classes[$fullyQualifiedName]) ? [] : [$this->classes[$fullyQualifiedName]];
    }

    public function findFunction($fullyQualifiedName)
    {
        $this->run();
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

    protected function doRun()
    {
        $this->container->get('name_resolver')->run();
        parent::doRun();
        $this->classes = $this->visitor->getClasses();
        $this->functions = $this->visitor->getFunctions();
        $this->consts = $this->visitor->getConsts();
    }
}
