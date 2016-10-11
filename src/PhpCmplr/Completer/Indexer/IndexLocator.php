<?php

namespace PhpCmplr\Completer\Indexer;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\Reflection\LocatorInterface;

class IndexLocator extends Component implements LocatorInterface
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    public function getPathsForClass($fullyQualifiedName)
    {
        $data = $this->indexer->getData('reflection');
        if (isset($data['fqnames']['class'][strtolower($fullyQualifiedName)])) {
            return $data['fqnames']['class'][strtolower($fullyQualifiedName)];
        }

        return [];
    }

    public function getPathsForFunction($fullyQualifiedName)
    {
        $data = $this->indexer->getData('reflection');
        if (isset($data['fqnames']['function'][strtolower($fullyQualifiedName)])) {
            return $data['fqnames']['function'][strtolower($fullyQualifiedName)];
        }

        return [];
    }

    public function getPathsForConst($fullyQualifiedName)
    {
        $data = $this->indexer->getData('reflection');
        if (isset($data['fqnames']['const'][$fullyQualifiedName])) {
            return $data['fqnames']['const'][$fullyQualifiedName];
        }

        return [];
    }


    protected function doRun()
    {
        $this->indexer = $this->container->get('indexer');
    }
}
