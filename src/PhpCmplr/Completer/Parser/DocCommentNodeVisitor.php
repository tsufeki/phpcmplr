<?php

namespace PhpCmplr\Completer\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use PhpCmplr\Completer\Parser\DocTag\DocTag;

class DocCommentNodeVisitor extends NodeVisitorAbstract
{
    /**
     * @param string $docComment
     *
     * @return array [short description string, long description string, DocTag[][]]
     */
    protected function parse($docComment)
    {
        $shortDescription = '';
        $longDescription = '';
        $annotations = [];

        $docBlock = trim(preg_replace([
            '~^/\\*\\*~',
            '~\\*/$~'
        ], '', $docComment));
        $current = &$shortDescription;
        $inShortDescription = true;
        foreach (explode("\n", $docBlock) as $line) {
            $line = preg_replace('~^\\*\\s?~', '', trim($line));

            // End of short description.
            if ($line === '' && $inShortDescription) {
                $current = &$longDescription;
                $inShortDescription = false;
                continue;
            }

            // @annotation
            if (preg_match('~^\\s*@([\\S]+)\\s*(.*)~', $line, $matches)) {
                $name = $matches[1];
                $current = &$annotations[$name][];
                $current = $matches[2];
                $inShortDescription = false;
                continue;
            }

            // Continuation.
            $current .= "\n" . $line;
        }

        array_walk_recursive($annotations, function (&$value) { $value = trim($value); });
        foreach ($annotations as $aname => &$alist) {
            foreach ($alist as &$annot) {
                $annot = DocTag::get($aname, $annot);
                unset($annot);
            }
            unset($alist);
        }
        return [trim($shortDescription) ?: null, trim($longDescription) ?: null, $annotations];
    }

    public function enterNode(Node $node)
    {
        if ($node->hasAttribute('comments')) {
            $lastDocComment = null;
            foreach ($node->getAttribute('comments') as $comment) {
                if ($comment instanceof \PhpParser\Comment\Doc) {
                    $lastDocComment = $comment;
                }
            }
            if ($lastDocComment) {
                list($shortDescription, $longDescription, $annotations) = $this->parse($lastDocComment->getText());
                if (!empty($shortDescription)) {
                    $node->setAttribute('shortDescription', $shortDescription);
                }
                if (!empty($longDescription)) {
                    $node->setAttribute('longDescription', $longDescription);
                }
                if (!empty($annotations)) {
                    $node->setAttribute('annotations', $annotations);
                }
            }
        }
    }
}
