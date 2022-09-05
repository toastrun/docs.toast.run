<?php

namespace Phpdoc2md\Markdown;

class TitleSegment extends Segment
{
    public string $type = 'title';

    public function __construct(
        public string $title,
        public string $name = '',
    ) {
    }

    public function addSubSegment(Segment $segment)
    {
        throw new \Exception('title segment should not have sub segments');
    }

    /**
     * to string
     *
     * @param integer $level markdown title level
     * @return string
     */
    public function str($level = 1): string
    {
        // this should not have children
        return str_repeat('#', $level - 1) . ' ' . $this->title . "\n\n";
    }
}
