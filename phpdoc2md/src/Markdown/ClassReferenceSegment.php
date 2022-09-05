<?php

namespace Phpdoc2md\Markdown;

use Exception;

class ClassReferenceSegment extends Segment
{
    public string $type = 'classReference';

    public function __construct(
        public string $name,
        public string $kind,
    ) {
    }

    public function addSubSegment(Segment $segment)
    {
        throw new Exception('class reference segment should not have sub segments');
    }

    /**
     * to string
     *
     * @param integer $level markdown title level (not used in class reference segment)
     * @return string
     */
    public function str($level = 1): string
    {
        $ret = "";

        $ucKind = ucfirst($this->kind);

        // signature: `public const FOO = 1`
        $ret .= "{$ucKind} [{$this->name}]({$this->name}.html)\n\n";

        return $ret;
    }
}
