<?php

namespace Phpdoc2md\Markdown;

use PhpParser\Node;
use PhpParser\PrettyPrinter;
use PhpParser\Node\Stmt\{
    Property,
};
use PHPStan\PhpDocParser\Ast\PhpDoc\{
    VarTagValueNode,
};

class PropertySegment extends Segment
{
    public string $type = 'property';

    protected function __construct(
        public string $name,
        public array $modifiers,
        public ?string $varType,
        public array $descriptions,
    ) {
    }

    /**
     * @return array<static>
     */
    public static function allFromPhpNode(Property $node): array
    {
        $prettyPrinter = new PrettyPrinter\Standard();

        $ret = [];

        // parse comment node
        $docNode = static::parseDocument($node);

        $props = [];

        // get var tags
        $varNodes = static::getTags($docNode, 'var');
        foreach ($varNodes as $varNode) {
            /** @var VarTagValueNode $varTagValueNode */
            $varTagValueNode = $varNode->value;
            $name = $varTagValueNode->variableName ? trim((string) $varTagValueNode->variableName, '$') : null;
            $props[$name] = (string) ($varTagValueNode->type ?? '') ?: null;
            //$varType = (string) ($varNode->type ?? '') ?: null;
        }

        // get desc
        $descriptions = static::parseCommonTags($docNode, ['var']);

        $phpType = $node->type ? $prettyPrinter->prettyPrint([$node->type]) : null;

        foreach ($node->props as $prop) {
            $name = ltrim($prettyPrinter->prettyPrint([$prop->name]), '$');
            $type = $props[$name] ?? $props[null] ?? $phpType;
            unset($props[$name]);
            $ret[] = new static(
                name: $name,
                modifiers: static::modifiers($node),
                varType: $type,
                descriptions: $descriptions,
            );
        }

        unset($props[null]);

        foreach ($props as $name => $_) {
            print("unknown @var $name property\n");
        }

        return $ret;
    }

    public function addSubSegment(Segment $segment)
    {
        throw new \Exception('function/method segment should not have sub segments');
    }

    /**
     * to string
     *
     * @param integer $level markdown title level (not used in const segment)
     * @return string
     */
    public function str($level = 1): string
    {
        $ret = "";

        //$varType = $this->varType ? "{$this->varType} " : '';
        // signature: `public int $foo`
        //$ret .= "`{$this->modifier}{$varType}{$this->name}`\n\n";
        $ret .= "{% include varSign.html name='{$this->name}' %}\n\n";

        // descriptions: FOO is a property
        $br = '';
        foreach ($this->descriptions as $description) {
            $br = "\n";
            $ret .= "$description\n";
        }
        $ret .= $br;

        return $ret;
    }
}
