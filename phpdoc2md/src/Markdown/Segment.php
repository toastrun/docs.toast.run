<?php

namespace Phpdoc2md\Markdown;

use PhpParser\Node;
use PhpParser\Node\Stmt\{
    Class_,
};
use PHPStan\PhpDocParser\Ast\PhpDoc\{
    PhpDocNode,
    PhpDocTextNode,
    PhpDocTagNode,
};

use PHPStan\PhpDocParser\Parser\{
    PhpDocParser,
    TypeParser,
    ConstExprParser,
    TokenIterator,
};
use PHPStan\PhpDocParser\Lexer\Lexer;

class Segment
{
    public string $name = '';
    public string $type = 'generic';
    /** @var array<Segment> $subSegments */
    protected array $subSegments = [];

    public function addSubSegment(self $segment)
    {
        $this->subSegments[] = $segment;
    }

    protected static function parseDocument(Node $node): PhpDocNode
    {

        $comments = $node->getComments();
        $comment = ($comments[count($comments) - 1] ?? null)?->getText() ?? '';
        if (!str_starts_with($comment, '/**')) {
            // not a phpdoc
            $comment = '/** */';
        }

        $constExprParser = new ConstExprParser();
        $typeParser = new TypeParser($constExprParser);
        $parser = new PhpDocParser($typeParser, $constExprParser);

        $lexer = new Lexer();

        $tokens = new TokenIterator($lexer->tokenize($comment));

        $docNode = $parser->parse($tokens);

        return $docNode;
    }

    /**
     * @param array<string> $ignoreTags
     * @return array<string>
     */
    protected static function parseCommonTags(PhpDocNode $docNode, array $ignoreTags): array
    {
        $ret = [];
        foreach ($docNode->children as $node) {
            switch (true) {
                case $node instanceof PhpDocTextNode:
                    $ret[] = (string) $node;
                    break;
                case $node instanceof PhpDocTagNode:
                    $lcName = strtolower(substr($node->name, 1));
                    foreach ($ignoreTags as $ignoreTag) {
                        if (
                            str_starts_with($lcName, $ignoreTag) ||
                            str_starts_with($lcName, "phpstan-$ignoreTag") ||
                            str_starts_with($lcName, "psalm-$ignoreTag") ||
                            str_starts_with($lcName, "phan-$ignoreTag")
                        ) {
                            break 2;
                        }
                    }
                    switch ($lcName) {
                        case 'note':
                            $ret[] = "{: .note }";
                            $ret[] = "{$node->value}";
                            break;
                        default:
                            $ret[] = '**' . ucfirst($lcName) . "** {$node->value}";
                            break;
                    }
                    break;
                default:
                    throw new \Exception("unknown node type $node");
            }
        }
        return $ret;
    }

    /**
     * @return array<PhpDocTagNode>
     */
    protected static function getTags(PhpDocNode $node, string $name): array
    {
        $tags = $node->getTags();

        $tags = array_filter($tags, function ($n) use ($name) {
            if (
                $n->name == "@phpstan-$name" ||
                $n->name == "@$name"
            ) {
                return true;
            }
            return false;
        });
        // if there's phpstan version, use it
        $usePhpstan = array_reduce($tags, function ($a, $c) {
            if (str_starts_with($c->name, '@phpstan')) {
                $a = true;
            }
            return $a;
        }, false);

        if ($usePhpstan) {
            $tags = array_filter($tags, fn ($n) => str_starts_with($n->name, '@phpstan'));
        }
        return $tags;
    }

    protected function __construct()
    {
        throw new \Exception('not implemented');
    }

    /**
     * to string
     *
     * @param integer $level markdown title level
     * @return string
     */
    public function str($level = 1): string
    {
        $ret = '';
        $this->sort();
        foreach ($this->subSegments as $segment) {
            $ret .= $segment->str($level + 1);
        }
        return $ret;
    }

    /**
     * sort segments
     */
    public function sort()
    {
        // do nothing
    }

    /**
     * from PhpParser\PrettyPrinterAbstract->pModifiers
     */
    // protected static function modifier(Node $node): string
    // {
    //     $modifiers = $node->flags;
    //     return ($modifiers & Class_::MODIFIER_PUBLIC    ? 'public '    : '')
    //         . ($modifiers & Class_::MODIFIER_PROTECTED ? 'protected ' : '')
    //         . ($modifiers & Class_::MODIFIER_PRIVATE   ? 'private '   : '')
    //         . ($modifiers & Class_::MODIFIER_STATIC    ? 'static '    : '')
    //         . ($modifiers & Class_::MODIFIER_ABSTRACT  ? 'abstract '  : '')
    //         . ($modifiers & Class_::MODIFIER_FINAL     ? 'final '     : '')
    //         . ($modifiers & Class_::MODIFIER_READONLY  ? 'readonly '  : '');
    // }

    /**
     * from PhpParser\PrettyPrinterAbstract->pModifiers
     */
    protected static function modifiers(Node $node): array
    {
        $ret = [];
        if (!isset($node->flags)) {
            return $ret;
        }
        $flags = $node->flags;
        if ($flags & Class_::MODIFIER_PUBLIC) {
            $ret[] = 'public';
        }
        if ($flags & Class_::MODIFIER_PROTECTED) {
            $ret[] = 'protected';
        }
        if ($flags & Class_::MODIFIER_PRIVATE) {
            $ret[] = 'private';
        }
        if ($flags & Class_::MODIFIER_STATIC) {
            $ret[] = 'static';
        }
        if ($flags & Class_::MODIFIER_ABSTRACT) {
            $ret[] = 'abstract';
        }
        if ($flags & Class_::MODIFIER_FINAL) {
            $ret[] = 'final';
        }
        if ($flags & Class_::MODIFIER_READONLY) {
            $ret[] = 'readonly';
        };
        return $ret;
    }
}
