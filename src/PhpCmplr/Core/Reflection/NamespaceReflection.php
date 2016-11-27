<?php

namespace PhpCmplr\Core\Reflection;

use PhpCmplr\Core\Component;
use PhpCmplr\Core\Container;

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

        return array_unique($fqnames);
    }

    public function findFullyQualifiedFunctions($unqualifiedName)
    {
        $this->run();
        $fqnames = [];
        foreach ($this->reflectionComponents as $component) {
            $fqnames = array_merge($fqnames, $component->findFullyQualifiedFunctions($unqualifiedName));
        }

        return array_unique($fqnames);
    }

    public function findFullyQualifiedConsts($unqualifiedName)
    {
        $this->run();
        $fqnames = [];
        foreach ($this->reflectionComponents as $component) {
            $fqnames = array_merge($fqnames, $component->findFullyQualifiedConsts($unqualifiedName));
        }

        return array_unique($fqnames);
    }

    protected function doRun()
    {
        $this->reflectionComponents = $this->container->getByTag('namespace_reflection');
    }
}
