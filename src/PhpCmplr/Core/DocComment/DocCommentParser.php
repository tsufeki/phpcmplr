<?php

namespace PhpCmplr\Core\DocComment;

use PhpParser\Node;
use PhpParser\Comment;

use PhpCmplr\Core\NodeVisitorComponent;
use PhpCmplr\Core\DocComment\Tag\Tag;

class DocCommentParser extends NodeVisitorComponent
{
    /**
     * @param string $docComment
     * @param int    $startFilePos
     *
     * @return array [short description string, long description string, Tag[][]]
     */
    protected function parse($docComment, $startFilePos)
    {
        $pos = $startFilePos;
        if (!empty($docComment) && substr_compare($docComment, '/**', 0, 3) === 0) {
            $pos += 3;
            $docComment = substr_replace($docComment, '', 0, 3);
        }
        if (!empty($docComment) && substr_compare($docComment, '*/', -2, 2) === 0) {
            $docComment = substr_replace($docComment, '', -2, 2);
        }

        $parts = [['text' => '', 'kind' => 'short']];
        $inShortDescription = true;
        foreach (explode("\n", $docComment) as $line) {
            $trimmed = preg_replace('~^\\*\\s?~', '', trim($line));

            // End of short description.
            if ($trimmed === '' && $inShortDescription) {
                if ($parts[0]['text'] !== '') {
                    $inShortDescription = false;
                    $parts[] = ['text' => '', 'kind' => 'long'];
                }

            // @annotation
            } elseif (preg_match('~^((\\s*\\*?\\s*)@([\\S]+)\\s*)(.*)~', $line, $matches)) {
                $inShortDescription = false;
                $parts[] = [
                    'text' => trim($matches[4]),
                    'kind' => 'annotation',
                    'name' => $matches[3],
                    'pos' => $pos + strlen($matches[2]),
                    'textPos' => $pos + strlen($matches[1]),
                ];

            } else {
                $parts[count($parts) - 1]['text'] .= "\n" . $trimmed;
            }

            $pos += strlen($line) + 1;
        }

        $shortDescription = '';
        $longDescription = '';
        $annotations = [];
        foreach ($parts as $part) {
            switch ($part['kind']) {
            case 'short':
                $shortDescription = trim($part['text']) ?: null;
                break;
            case 'long':
                $longDescription = trim($part['text']) ?: null;
                break;
            case 'annotation':
                $annotations[$part['name']][] = Tag::get(
                    $part['name'],
                    trim($part['text']),
                    $part['pos'],
                    $part['textPos']
                );
                break;
            }
        }

        return [$shortDescription, $longDescription, $annotations];
    }

    public function enterNode(Node $node)
    {
        if ($node->hasAttribute('comments')) {
            /** @var Comment\Doc|null */
            $lastDocComment = null;
            foreach ($node->getAttribute('comments') as $comment) {
                if ($comment instanceof Comment\Doc) {
                    $lastDocComment = $comment;
                }
            }
            if ($lastDocComment) {
                list($shortDescription, $longDescription, $annotations) = $this->parse(
                    $lastDocComment->getText(),
                    $lastDocComment->getFilePos()
                );
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
