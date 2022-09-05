<?php

namespace Phpdoc2md\Markdown;

class ClassPage extends Page
{
    public function __construct(
        private string $name,
        private string $kind,
        private NamespacePage $parent,
    ) {
    }

    public function str(): string
    {
        $parentTitle = $this->parent->getPageTitle();

        $ucKind = ucfirst($this->kind);

        $metaVars = [
            "layout: default",
            "title: '{$ucKind} {$this->name}'",
            "parent: '$parentTitle'",
            "grand_parent: '{$this->parent->parentPage}'",
            'has_toc: true',
            "name: '{$this->name}'",
        ];

        foreach ($this->meta as $key => $value) {
            if (!$value) {
                // filter out empty things
                continue;
            }
            // yaml is superset of json
            $metaVars[] = "$key: " . json_encode($value);
        }

        $meta = new TextSegment(contents: ['---', ...$metaVars, '---'], name: '00_jekyllmeta');

        $name = str_replace('\\', '\\\\', $this->name);
        $introduce = new TextSegment(contents: ["This is automatic generated document for things in {$name} class", ''], name: '02_introduce');

        $this->addSegment($meta);
        $this->addSegment($introduce);

        $ret = parent::str();

        // remove tags
        $this->segments = array_filter($this->segments, fn ($x) => $x instanceof ClassLikeSegment);

        return $ret;
    }

    /**
     * sort segments
     */
    public function sort()
    {
        uasort($this->segments, function ($seg1, $seg2) {
            $typeKey = fn ($s) => match ($s->type) {
                'text' => 0,
                'classLike' => 1,
            };
            if ($typeKey($seg1) !== $typeKey($seg2)) {
                return $typeKey($seg1) - $typeKey($seg2);
            }
            return strcmp($seg1->name, $seg2->name);
        });
    }
}
