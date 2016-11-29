<?php

namespace PhpCmplr\Symfony\TypeInferrer;

use PhpParser\Node;
use PhpCmplr\Core\NodeVisitorComponent;
use PhpCmplr\Core\Reflection\Reflection;
use PhpCmplr\Core\Reflection\Element\Method;
use PhpCmplr\Core\Type\Type;
use PhpCmplr\Symfony\Config\ConfigLoader;

class ContainerInferrer extends NodeVisitorComponent
{
    const CONTAINER_GET_CLASSES = [
        '\\Symfony\\Component\\DependencyInjection\\ContainerInterface',
        '\\Symfony\\Bundle\\FrameworkBundle\\Controller\\Controller',
    ];

    /**
     * @var Reflection
     */
    private $reflection;

    /**
     * @var ConfigLoader
     */
    private $symfonyConfig;

    public function beforeTraverse(array $nodes)
    {
        $this->reflection = $this->container->get('reflection');
        $this->symfonyConfig = $this->container->get('symfony.config');
    }

    public function leaveNode(Node $node)
    {
        /** @var Node|Node\Expr\MethodCall $node */
        if ($node instanceof Node\Expr\MethodCall &&
            $node->name === 'get' &&
            !empty($node->args) &&
            $node->args[0]->value instanceof Node\Scalar\String_ &&
            ($methods = $node->getAttribute('reflections')) !== null
        ) {
            /** @var Method */
            foreach ($methods as $method) {
                foreach (static::CONTAINER_GET_CLASSES as $containerClass) {
                    if ($this->reflection->isSubclass(
                        $method->getClass()->getName(),
                        $containerClass
                    )) {
                        $serviceId = $node->args[0]->value->value;
                        $service = $this->symfonyConfig->getConfig()->getService($serviceId);
                        if ($service !== null && !empty($service->getClass())) {
                            $node->setAttribute('type', Type::object_($service->getClass()));
                        }
                        return;
                    }
                }
            }
        }
    }
}
