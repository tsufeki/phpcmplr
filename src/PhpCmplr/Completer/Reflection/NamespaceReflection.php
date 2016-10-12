<?php

namespace PhpCmplr\Completer\Reflection;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Container;

class NamespaceReflection extends Component
{
    /**
     * @var NamespaceReflectionInterface[]
     */
    private $reflectionComponents;

    public function findFullyQualifiedClasses($unqualifiedName)
    {
        $this->run();
        $fqnames = [];
        foreach ($this->reflectionComponents as $component) {
            $fqnames = array_merge($fqnames, $component->findFullyQualifiedClasses($unqualifiedName));
        }

        return $fqnames;
    }

    public function findFullyQualifiedFunctions($unqualifiedName)
    {
        $this->run();
        $fqnames = [];
        foreach ($this->reflectionComponents as $component) {
            $fqnames = array_merge($fqnames, $component->findFullyQualifiedFunctions($unqualifiedName));
        }

        return $fqnames;
    }

    public function findFullyQualifiedConsts($unqualifiedName)
    {
        $this->run();
        $fqnames = [];
        foreach ($this->reflectionComponents as $component) {
            $fqnames = array_merge($fqnames, $component->findFullyQualifiedConsts($unqualifiedName));
        }

        return $fqnames;
    }

    protected function doRun()
    {
        $this->reflectionComponents = $this->container->getByTag('namespace_reflection');
    }
}
