<?php

namespace PhpCmplr\Core\Indexer;

use PhpCmplr\Core\Component;
use PhpCmplr\Core\Reflection\LocatorInterface;

class IndexLocator extends Component implements LocatorInterface
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    public function getPathsForClass($fullyQualifiedName)
    {
        $this->run();
        $data = $this->indexer->getData('reflection');
        if (isset($data['fqnames']['class'][strtolower($fullyQualifiedName)])) {
            return $data['fqnames']['class'][strtolower($fullyQualifiedName)];
        }

        return [];
    }

    public function getPathsForFunction($fullyQualifiedName)
    {
        $this->run();
        $data = $this->indexer->getData('reflection');
        if (isset($data['fqnames']['function'][strtolower($fullyQualifiedName)])) {
            return $data['fqnames']['function'][strtolower($fullyQualifiedName)];
        }

        return [];
    }

    public function getPathsForConst($fullyQualifiedName)
    {
        $this->run();
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
