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
 * Location in a source file.
 */
class Location
{
    /**
     * 1-based line number.
     *
     * @var int
     */
    private $line;

    /**
     * 1-based column number.
     *
     * @var int
     */
    private $column;

    /**
     * @param int $line
     * @param int $column
     */
    public function __construct($line, $column)
    {
        $this->line = $line;
        $this->column = $column;
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return int
     */
    public function getColumn()
    {
        return $this->column;
    }
}
