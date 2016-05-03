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

class Diagnostic
{
    /**
     * @var SourceFile
     */
    private $file;

    /**
     * File offset.
     *
     * @var int
     */
    private $start;

    /**
     * File offset.
     *
     * @var int
     */
    private $end;

    /**
     * @var string
     */
    private $description;

    /**
     * @param SourceFile $file
     * @param int        $start
     * @param int        $end
     * @param string     $description
     */
    public function __construct(SourceFile $file, $start, $end, $description)
    {
        $this->file = $file;
        $this->start = $start;
        $this->end = $end;
        $this->description = $description;
    }

    /**
     * @return SourceFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
