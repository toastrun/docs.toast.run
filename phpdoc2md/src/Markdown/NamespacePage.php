<?php

namespace Phpdoc2md\Markdown;

class NamespacePage extends Page
{

    public function __construct(
        private string $name,
        public string $parentPage = 'API Reference',
    ) {
    }

    public function getPageTitle(): string
    {
        return "Namespace {$this->name}";
    }

    public function str(): string
    {
        $title = $this->getPageTitle();

        $metaVars = [
            "layout: default",
            "title: '{$title}'",
            'has_children: true',
            "parent: '{$this->parentPage}'",
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

        $meta = new TextSegment(contents: [
            "---",
            ...$metaVars,
            '---',
        ], name: '00_jekyllmeta');

        $name = str_replace('\\', '\\\\', $this->name);
        $introduce = new TextSegment(contents: ["This is automatic generated document for things in {$name} namespace", ''], name: '02_introduce');
        $classesTitle = new TextSegment(contents: ["## Classes", '']);
        $classesTitle->type = 'classesTitle';
        $this->addSegment($meta);
        $this->addSegment($introduce);
        $this->addSegment($classesTitle);

        $ret = parent::str();

        // remove tags
        $this->segments = array_filter($this->segments, fn ($x) => !($x instanceof TextSegment));

        return $ret;
    }

    /**
     * sort segments
     */
    public function sort()
    {
        uasort($this->segments, function ($seg1, $seg2) {
            $typeKey = fn ($s) => match ($s->type) {
                'title' => 0,
                'text' => 0,
                // 'variable' => 1,
                'const' => 2,
                'function' => 3,
                'classesTitle' => 4,
                'classReference' => 5,
            };
            if ($typeKey($seg1) !== $typeKey($seg2)) {
                return $typeKey($seg1) - $typeKey($seg2);
            }
            return strcmp($seg1->name, $seg2->name);
        });
    }
}
