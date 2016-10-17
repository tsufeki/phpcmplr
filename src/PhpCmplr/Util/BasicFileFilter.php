<?php

namespace PhpCmplr\Util;

use SplFileInfo;

class BasicFileFilter implements FileFilterInterface
{
    /**
     * @var string[]|null
     */
    private $extensions;

    /**
     * @var int|null
     */
    private $maxSize;

    /**
     * @var string[]|null
     */
    private $types;

    /**
     * @param string[]|null $extensions
     * @param int|null      $maxSize
     * @param string[]|null $types      'file'|'dir'|'link'
     */
    public function __construct(array $extensions = null, $maxSize = null, $types = ['file'])
    {
        $this->extensions = $extensions;
        $this->maxSize = $maxSize;
        $this->types = $types;
    }

    public function filter(SplFileInfo $fileInfo)
    {
        return ($this->extensions === null || in_array($fileInfo->getExtension(), $this->extensions)) &&
               ($this->maxSize === null || $fileInfo->getSize() <= $this->maxSize) &&
               ($this->types === null || in_array($fileInfo->getType(), $this->types));
    }
}
