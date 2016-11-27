<?php

namespace PhpCmplr\Core\Completer;

use PhpCmplr\Core\Component;

class Completer extends Component
{
    /**
     * @var CompleterInterface[]
     */
    private $components;

    /**
     * Find completions at offset.
     *
     * @param int $offset
     *
     * @return Completion[]
     */
    public function complete($offset)
    {
        $this->run();
        /** @var Completion[] */
        $completions = [];
        foreach ($this->components as $component) {
            $completions = array_merge($completions, $component->complete($offset));
        }

        $uniq = [];
        foreach ($completions as $completion) {
            $uniq[$completion->getInsertion()] = $completion;
        }

        return array_values($uniq);
    }

    protected function doRun()
    {
        $this->components = $this->container->getByTag('completer');
    }
}
