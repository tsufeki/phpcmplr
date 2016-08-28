<?php

namespace PhpCmplr\Completer\GoTo_;

use PhpLenientParser\Node\Expr;
use PhpLenientParser\Node\Stmt;
use PhpLenientParser\Node\Param;
use PhpLenientParser\Node\Name;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Type\Type;
use PhpCmplr\Completer\Reflection\ReflectionComponent;

class GoToClassDefinitionComponent extends Component implements GoToComponentInterface
{
    /**
     * @var ReflectionComponent
     */
    private $reflection;

    public function getGoToLocations($offset, $nodes)
    {
        $this->run();

        $name = null;
        $node = null;
        if (count($nodes) >= 2 && $nodes[0] instanceof Name) {
            $name = Type::nameToString($nodes[0]);
            $node = $nodes[1];
        }

        $locations = [];

        if ($node !== null && (
                $node instanceof Expr\Instanceof_ ||
                $node instanceof Expr\New_ ||
                $node instanceof Stmt\Catch_ ||

                $node instanceof Stmt\Function_ || // Name in the return type
                $node instanceof Stmt\ClassMethod ||
                $node instanceof Expr\Closure ||
                $node instanceof Param || // type hint

                $node instanceof Stmt\Class_ ||
                $node instanceof Stmt\Interface_ ||
                $node instanceof Stmt\TraitUse ||
                $node instanceof Stmt\TraitUseAdaptation)) {

            foreach ($this->reflection->findClass($name) as $class) {
                if ($class->getLocation() !== null) {
                    $locations[] = $class->getLocation();
                }
            }
        }

        return $locations;
    }

    protected function doRun()
    {
        $this->container->get('name_resolver')->run();
        $this->reflection = $this->container->get('reflection');
    }
}
