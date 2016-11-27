<?php

namespace PhpCmplr\Core\Indexer;

use PhpCmplr\Core\Component;
use PhpCmplr\Core\Reflection\NamespaceReflectionInterface;

class IndexNamespaceReflection extends Component implements NamespaceReflectionInterface
{
    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @param string $unqualifiedName
     * @param string $kind
     *
     * @return string[]
     */
    private function findFullyQualified($unqualifiedName, $kind)
    {
        $this->run();
        $result = [];
        $data = $this->indexer->getData('reflection');
        if (isset($data['names'][$kind][$unqualifiedName])) {
            foreach ($data['names'][$kind][$unqualifiedName] as $fqname) {
                if (!empty($data['fqnames'][$kind][$fqname])) {
                    $path = reset($data['fqnames'][$kind][$fqname]);
                    if (isset($data['files'][$path][$kind][$fqname])) {
                        $fqname = $data['files'][$path][$kind][$fqname];
                    }
                }
                $result[] = $fqname;
            }
        }

        return $result;
    }

    public function findFullyQualifiedClasses($unqualifiedName)
    {
        return $this->findFullyQualified(strtolower($unqualifiedName), 'class');
    }

    public function findFullyQualifiedFunctions($unqualifiedName)
    {
        return $this->findFullyQualified(strtolower($unqualifiedName), 'function');
    }

    public function findFullyQualifiedConsts($unqualifiedName)
    {
        return $this->findFullyQualified($unqualifiedName, 'const');
    }

    protected function doRun()
    {
        $this->indexer = $this->container->get('indexer');
    }
}
