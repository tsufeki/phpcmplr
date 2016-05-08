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

use PhpParser\Error as ParserError;
use PhpParser\Lexer\Emulative as Lexer;
use PhpParser\ParserFactory;
use PhpParser\Parser;
use PhpParser\Node;

class SourceFile
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * This must always be a full, absolute path.
     *
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $contents;

    /**
     * @var Node[]
     */
    protected $ast;

    /**
     * @var Diagnostic[]
     */
    protected $diagnostics;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->parser = (new ParserFactory())->create(
            ParserFactory::PREFER_PHP7,
            new Lexer(['usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos']]),
            ['throwOnError' => false]
        );
        $this->diagnostics = [];
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get offset (0-based) inside file of the character at given line and column (1-based).
     *
     * @param int $line
     * @param int $column
     *
     * @return int
     */
    public function getOffset($line, $column)
    {
        $maxOffset = max(0, strlen($this->contents) - 1);
        $offset = 0;
        for ($i = 0; $i < $line - 1; $i++) {
            $offset = strpos($this->contents, "\n", $offset);
            if ($offset === false) {
                return $maxOffset;
            }
            $offset++; // newline character
        }
        $offset += $column - 1;
        if ($offset > $maxOffset) {
            return $maxOffset;
        }
        return $offset;
    }

    /**
     * Get line and and column (1-based) for the given offset (0-based).
     *
     * @param int $offset
     *
     * @return int[] [line, column]
     */
    public function getLineAndColumn($offset)
    {
        $offset = max(0, min($offset, strlen($this->contents) - 1));
        $line = 0;
        $currentOffset = 0;
        $lastOffset = 0;
        while ($currentOffset <= $offset) {
            $lastOffset = $currentOffset;
            $currentOffset = strpos($this->contents, "\n", $currentOffset);
            $line++;
            if ($currentOffset === false) {
                break;
            }
            $currentOffset++; // newline character
        }
        return [$line, 1 + max(0, $offset - $lastOffset)];
    }

    /**
     * Load and parse file contents.
     *
     * @param string $contents
     *
     * @return $this
     */
    public function load($contents)
    {
        $this->contents = $contents;
        $this->ast = [];
        $this->diagnostics = [];

        try {
            $this->ast = $this->parser->parse($this->contents);
            foreach ($this->parser->getErrors() as $error) {
                $this->diagnostics[] = $this->makeDiagnostic($error);
            }
        } catch (ParserError $error) {
            $this->diagnostics[] = $this->makeDiagnostic($error);
        }

        return $this;
    }

    /**
     * @param ParserError $error
     *
     * @return Diagnostic
     */
    protected function makeDiagnostic(ParserError $error)
    {
        $attributes = $error->getAttributes();
        $start = array_key_exists('startFilePos', $attributes) ? $attributes['startFilePos'] : 0;
        $end = array_key_exists('endFilePos', $attributes) ? $attributes['endFilePos'] : $start;
        return new Diagnostic($this, $start, $end, $error->getRawMessage());
    }

    /**
     * @return Diagnostic[]
     */
    public function getDiagnostics()
    {
        return $this->diagnostics;
    }
}
