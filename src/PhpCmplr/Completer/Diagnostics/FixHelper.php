<?php

namespace PhpCmplr\Completer\Diagnostics;

use PhpCmplr\Completer\Component;
use PhpCmplr\Completer\SourceFile\SourceFileInterface;

class FixHelper extends Component
{
    /**
     * @var SourceFileInterface
     */
    private $file;

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

    protected function doRun()
    {
        $this->file = $this->container->get('file');
    }
}
