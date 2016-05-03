<?php

/*
 * phpcmplr
 * Copyright (C) 2016  tsufeki
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PhpCmplr\Completer;

/**
 * Collection of SourceFiles, indexed by path.
 */
class Project
{
    /**
     * @var SourceFile[]
     */
    private $files;

    public function __construct()
    {
        $this->files = [];
    }

    /**
     * @param SourceFile $file
     * @return $this
     */
    public function addFile(SourceFile $file)
    {
        $this->files[$file->getPath()] = $file;
        return $this;
    }

    /**
     * @param SourceFile $file
     * @return $this
     */
    public function removeFile(SourceFile $file)
    {
        unset($this->files[$file->getPath()]);
        return $this;
    }

    /**
     * @param string $path
     * @return SourceFile
     */
    public function getFile($path)
    {
        if (array_key_exists($path, $this->files)) {
            return $this->files[$path];
        }
        return null;
    }

    /**
     * @return SourceFile[]
     */
    public function getFiles()
    {
        return $this->files;
    }
}
