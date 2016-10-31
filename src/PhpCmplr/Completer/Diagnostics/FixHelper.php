<?php

namespace PhpCmplr\Completer\Diagnostics;

use PhpParser\Node\Stmt;
use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\SourceFile\SourceFileInterface;
use PhpCmplr\Completer\SourceFile\Location;
use PhpCmplr\Completer\SourceFile\OffsetLocation;
use PhpCmplr\Completer\SourceFile\LineAndColumnLocation;
use PhpCmplr\Completer\SourceFile\Range;
use PhpCmplr\Completer\Diagnostics\Fix;
use PhpCmplr\Completer\Diagnostics\FixChunk;
use PhpCmplr\Completer\Diagnostics\FixHelper;
use PhpCmplr\Completer\Parser\ParserInterface;

class FixHelper extends Component
{
    /**
     * @var SourceFileInterface
     */
    private $file;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var Range
     */
    private $useInsertRange;

    /**
     * @var string
     */
    private $useInsertText;

    /**
     * @param string[] $lines
     *
     * @return int[] [tabs, spaces]
     */
    public function getIndentOfLines(array $lines)
    {
        if (count($lines) === 0) {
            return [0, 0];
        }

        $minTabs = 999999;
        foreach ($lines as $line) {
            $tabs = strspn($line, "\t");
            if ($tabs < $minTabs) {
                $minTabs = $tabs;
            }
        }

        $minSpaces = 999999;
        foreach ($lines as $line) {
            $spaces = strspn($line, ' ', $minTabs);
            if ($spaces < $minSpaces) {
                $minSpaces = $spaces;
            }
        }

        return [$minTabs, $minSpaces];
    }

    /**
     * Create whitespace string of given number of tabs ans spaces.
     *
     * @param array $indent [tabs, spaces]
     *
     * @return string
     */
    public function makeIndent(array $indent)
    {
        return str_repeat("\t", $indent[0]) . str_repeat(' ', $indent[1]);
    }

    /**
     * @param string $fqname
     *
     * @return Fix
     */
    public function getUseFix($fqname, Location $location)
    {
        $this->run();

        if ($this->useInsertRange === null) {
            /** @var Stmt\Namespace_|null */
            $namespace = null;
            foreach ($this->parser->getNodesAtOffset($location->getOffset($this->file)) as $ancestor) {
                if ($ancestor instanceof Stmt\Namespace_) {
                    $namespace = $ancestor;
                    break;
                }
            }

            $lastUse = null;
            $stmts = $namespace === null ? $this->parser->getNodes() : $namespace->stmts;
            foreach ($stmts as $stmt) {
                if ($stmt instanceof Stmt\Use_ || $stmt instanceof Stmt\GroupUse) {
                    $lastUse = $stmt;
                }
            }

            if ($lastUse !== null) {
                /** @var Range */
                $range = Range::fromNode($lastUse, $this->file->getPath());
                $startLine = $range->getStart()->getLineAndColumn($this->file)[0];
                $insertLocation = OffsetLocation::move($this->file, $range->getEnd());
                $indent = $this->getIndentOfLines([$this->file->getLine($startLine)]);
                $this->useInsertText = "\n" . $this->makeIndent($indent) . "use %s;";
            } else {
                $insertLocation = LineAndColumnLocation::moveToStartOfLine(
                    $this->file,
                    Range::fromNode($stmts[0], $this->file->getPath())->getStart()
                );
                $this->useInsertText = "use %s;\n\n";
            }

            $this->useInsertRange = new Range(
                $insertLocation,
                OffsetLocation::move($this->file, $insertLocation, -1)
            );
        }

        $fqname = ltrim($fqname, '\\');
        return new Fix(
            [new FixChunk(
                $this->useInsertRange,
                sprintf($this->useInsertText, $fqname)
            )],
            'use ' . $fqname . ';'
        );
    }

    protected function doRun()
    {
        $this->file = $this->container->get('file');
        $this->parser = $this->container->get('parser');
    }
}
