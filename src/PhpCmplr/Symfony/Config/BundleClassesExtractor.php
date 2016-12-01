<?php

namespace PhpCmplr\Symfony\Config;

use PhpCmplr\Core\NodeVisitorComponent;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpCmplr\Core\Type\Type;

class BundleClassesExtractor extends NodeVisitorComponent
{
    /**
     * @var string[]
     */
    private $classes;

    /**
     * @return string[]
     */
    public function getClasses()
    {
        $this->run();

        return $this->classes;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->container->get('name_resolver')->run();
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\New_ && is_object($node->class) && $node->class instanceof Name) {
            $class = Type::nameToString($node->class);
            if (!in_array(strtolower($class), ['self', 'parent', 'static'])) {
                $this->classes[] = $class;
            }
        }
    }
}
