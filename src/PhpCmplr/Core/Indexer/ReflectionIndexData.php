<?php

namespace PhpCmplr\Core\Indexer;

use PhpCmplr\Core\Component;
use PhpCmplr\Core\SourceFile\SourceFileInterface;
use PhpCmplr\Core\Reflection\FileReflection;
use PhpCmplr\Core\Reflection\Element\Element;

class ReflectionIndexData extends Component implements IndexDataInterface
{
    const KINDS = ['class', 'function', 'const'];

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $indexData;

    public function getKey()
    {
        return 'reflection';
    }

    /**
     * Get last part of fully qualified name.
     *
     * @param string $fqname
     *
     * @return string
     */
    private function getShortName($fqname)
    {
        $parts = explode('\\', $fqname);

        return $parts[count($parts) - 1];
    }

    /**
     * @param array $arr
     * @param mixed $elem
     */
    private function removeElem(array &$arr, $elem)
    {
        $pos = array_search($elem, $arr);
        if ($pos !== false) {
            array_splice($arr, $pos, 1);
        }
    }

    private function init()
    {
        if (!array_key_exists('files', $this->indexData)) {
            $this->indexData['files'] = [];
        }
        foreach (['fqnames', 'names'] as $key) {
            if (!array_key_exists($key, $this->indexData)) {
                $this->indexData[$key] = [];
            }
            foreach (self::KINDS as $kind) {
                if (!array_key_exists($kind, $this->indexData[$key])) {
                    $this->indexData[$key][$kind] = [];
                }
            }
        }
    }

    private function remove()
    {
        if (array_key_exists($this->path, $this->indexData['files'])) {
            foreach (self::KINDS as $kind) {
                if (array_key_exists($kind, $this->indexData['files'][$this->path])) {
                    foreach ($this->indexData['files'][$this->path][$kind] as $fqname => $_) {
                        if (array_key_exists($fqname, $this->indexData['fqnames'][$kind])) {
                            $this->removeElem($this->indexData['fqnames'][$kind][$fqname], $this->path);
                        }
                        $name = $this->getShortName($fqname);
                        if (array_key_exists($name, $this->indexData['names'][$kind])) {
                            $this->removeElem($this->indexData['names'][$kind][$name], $fqname);
                        }
                    }
                }
            }

            unset($this->indexData['files'][$this->path]);
        }
    }

    /**
     * @param Element $element
     * @param string  $kind
     * @param bool    $caseInsensitive
     */
    private function add(Element $element, $kind, $caseInsensitive = true)
    {
        $fqnameOrig = $fqname = $element->getName();
        if ($caseInsensitive) {
            $fqname = strtolower($fqname);
        }
        $this->indexData['fqnames'][$kind][$fqname][] = $this->path;
        $name = $this->getShortName($fqname);
        $this->indexData['names'][$kind][$name][] = $fqname;
        $this->indexData['files'][$this->path][$kind][$fqname] = $fqnameOrig;
    }

    public function update(array &$indexData)
    {
        /** @var SourceFileInterface */
        $file = $this->container->get('file');
        $this->path = $file->getPath();
        $this->indexData =& $indexData;

        $this->init();
        $this->remove();

        if (!$file->isEmpty()) {
            /** @var FileReflection */
            $reflection = $this->container->get('reflection');

            foreach ($reflection->getClasses() as $elem) {
                $this->add($elem, 'class');
            }

            foreach ($reflection->getFunctions() as $elem) {
                $this->add($elem, 'function');
            }

            foreach ($reflection->getConsts() as $elem) {
                $this->add($elem, 'const', false);
            }
        }

        unset($this->indexData);
    }
}
