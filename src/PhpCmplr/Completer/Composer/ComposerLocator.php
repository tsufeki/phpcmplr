<?php

namespace PhpCmplr\Completer\Composer;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Reflection\Locator;

class ComposerLocator extends Component implements Locator
{
    /**
     * @var ComposerPackage
     */
    protected $package;

    public function getPathsForClass($fullyQualifiedName)
    {
        $this->run();
        return $this->package === null ? [] : $this->package->getPathsForClass($fullyQualifiedName);
    }

    public function getPathsForFunction($fullyQualifiedName)
    {
        return [];
    }

    public function getPathsForConst($fullyQualifiedName)
    {
        return [];
    }

    protected function doRun()
    {
        $this->package = null;
        try {
            $this->package = ComposerPackage::get(
                $this->container->get('file')->getPath(),
                $this->container->get('io'));
        } catch (\Exception $e) {
            // TODO: log it
        }
    }
}
