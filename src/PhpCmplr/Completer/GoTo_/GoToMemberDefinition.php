<?php

namespace PhpCmplr\Completer\GoTo_;

use PhpParser\Node\Name;
use PhpParser\Comment;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Reflection\Element\Element;
use PhpCmplr\Completer\TypeInferrer\TypeInferrerInterface;

class GoToMemberDefinition extends Component implements GoToInterface
{
    public function getGoToLocations($offset, $nodes)
    {
        $this->run();

        $node = null;
        if (count($nodes) > 0) {
            $node = $nodes[0];
            if ($node instanceof Name || $node instanceof Comment) {
                $node = count($nodes) > 1 ? $nodes[1] : null;
            }
        }

        $locations = [];

        if ($node !== null && $node->hasAttribute('reflections')) {
            /** @var Element */
            foreach ($node->getAttribute('reflections') as $refl) {
                $loc = $refl->getLocation();
                if ($loc !== null) {
                    $locations[] = $loc;
                }
            }
        }

        return $locations;
    }

    protected function doRun()
    {
        /** @var TypeInferrerInterface */
        $typeinfer = $this->container->get('typeinfer');
        $typeinfer->run();
    }
}
