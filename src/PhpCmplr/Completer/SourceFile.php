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
     * Load and parse file contents.
     *
     * @param string $contents
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
    }

    /**
     * @param ParserError $error
     *
     * @return Diagnostic
     */
    protected function makeDiagnostic(ParserError $error)
    {
        $start = new Location(
            $error->getStartLine() >= 1 ? $error->getStartLine() : 1,
            $error->hasColumnInfo() ? $error->getStartColumn($this->contents) : 1
        );
        $end = new Location(
            $error->getEndLine() >= 1 ? $error->getEndLine() : 1,
            $error->hasColumnInfo() ? $error->getEndColumn($this->contents) : 1
        );
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
