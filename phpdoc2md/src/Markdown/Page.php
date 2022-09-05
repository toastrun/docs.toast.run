<?php

namespace Phpdoc2md\Markdown;

class Page
{
    protected array $meta = [
        'functions' => [],
        'variables' => [],
        'consts' => [],
    ];

    //public string $type = 'generic';
    protected array $segments = [];

    public function addSegment(Segment $segment)
    {
        $this->segments[] = $segment;
    }

    /**
     * to string
     */
    public function str(): string
    {
        $ret = '';
        $this->sort();
        foreach ($this->segments as $segment) {
            $ret .= $segment->str(2);
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

    public function addSegmentMeta(FunctionSegment|PropertySegment|ConstSegment $segment)
    {
        $kind = '';
        $meta = [];

        switch (true) {
            case $segment instanceof FunctionSegment:
                $kind = 'functions';

                if ($segment->returnType !== null) {
                    $meta['returnType'] = $segment->returnType;
                }

                $meta['modifiers'] = $segment->modifiers;

                $params = [];
                foreach ($segment->params as $name => $param) {
                    $params[] = [
                        'name' => $name,
                        ...$param,
                    ];
                }
                $meta['params'] = $params;
                break;
            case $segment instanceof PropertySegment:
                $kind = 'variables';
                if ($segment->varType !== null) {
                    $meta['type'] = $segment->varType;
                }
                if ($segment->modifiers) {
                    $meta['modifiers'] = $segment->modifiers;
                }
                break;
            case $segment instanceof ConstSegment:
                $kind = 'constants';
                if ($segment->value !== null) {
                    $meta['value'] = $segment->value;
                }
                if ($segment->modifiers) {
                    $meta['modifiers'] = $segment->modifiers;
                }
                break;
        };

        $this->meta[$kind][$segment->name] = $meta;
    }
}
