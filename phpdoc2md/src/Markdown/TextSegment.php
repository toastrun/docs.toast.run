<?php

namespace Phpdoc2md\Markdown;

class TextSegment extends Segment
{
    public string $type = 'text';

    /**
     * @param array<string> $contents
     */
    public function __construct(
        public string $name = '',
        public array $contents = [],
    ) {
    }

    public function addSubSegment(Segment $segment)
    {
        throw new \Exception('text segment should not have sub segments');
    }

    /**
     * to string
     *
     * @param integer $level markdown title level (not used for text segment)
     * @return string
     */
    public function str($level = 1): string
    {
        $ret = '';
        foreach ($this->contents as $line) {
            $ret .= $line . "\n";
        }
        // this should not have children
        return $ret;
    }
}
