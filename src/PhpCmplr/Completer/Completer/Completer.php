<?php

namespace PhpCmplr\Completer\Completer;

use PhpCmplr\Completer\Component;

class Completer extends Component
{
    /**
     * @var CompleterInterface
     */
    private $components;

    public function complete($offset)
    {
        $this->run();
        $completions = [];
        foreach ($this->components as $component) {
            $completions = array_merge($completions, $component->complete($offset));
        }

        return $completions;
    }

    protected function doRun()
    {
        $this->components = $this->container->getByTag('completer');
    }
}
