<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Container;
use PhpCmplr\Completer\NodeTraverserComponent;

class FileReflectionComponent extends NodeTraverserComponent
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

    /**
     * Get all classes, interfaces and traits from this file.
     *
     * @return ClassLike[]
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Get all functions from this file.
     *
     * @return Function_[]
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    protected function doRun()
    {
        $this->container->get('name_resolver')->run();
        parent::doRun();
        $this->classes = $this->visitor->getClasses();
        $this->functions = $this->visitor->getFunctions();
    }
}
