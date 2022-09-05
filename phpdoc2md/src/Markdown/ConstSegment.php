<?php

namespace Phpdoc2md\Markdown;

use Exception;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use PhpParser\Node\Stmt\{
    Const_,
    ClassConst,
};

class ConstSegment extends Segment
{
    public string $type = 'const';

    protected function __construct(
        public string $name,
        public string $kind,
        public array $modifiers,
        public string $value,
        public array $descriptions,
    ) {
    }


    /**
     * this is not safe, use it on trusted code only !!!!!!!
     *
     * @param Node\Expr $node
     * @return mixed
     */
    private static function evaluateExpression(Node\Expr $node): string
    {
        $prettyPrinter = new PrettyPrinter\Standard();
        $expr = $prettyPrinter->prettyPrintExpr($node);
        //print("expr is $expr\n");
        /** @var mixed $newVal */
        $newVal = null;
        eval(<<<PHP
                try {
                    \$newVal = $expr;
                } catch (Throwable) {
                    // pass
                }
            PHP);

        switch (true) {
            case is_float($newVal):
                if (is_nan($newVal))
                    return 'NaN';
                if (is_infinite($newVal))
                    return 'INF';
            case is_string($newVal):
            case is_numeric($newVal):
            case is_null($newVal):
            case is_bool($newVal):
                return json_encode($newVal);
            case is_array($newVal):
                // TODO: better output
                return json_encode($newVal);
        }

        return $newVal;
    }

    /**
     * @return array<static>
     */
    public static function allFromPhpNode(Const_|ClassConst $node): array
    {
        $ret = [];

        // parse comment node
        $docNode = static::parseDocument($node);

        // TODO: @var

        $descriptions = static::parseCommonTags($docNode, ['var']);

        foreach ($node->consts as $const) {
            $ret[] = new static(
                name: $const->name->name,
                kind: match ($node->getType()) {
                    'Stmt_Const' => 'constant',
                    'Stmt_ClassConst' => 'class constant',
                },
                value: static::evaluateExpression($const->value),
                modifiers: static::modifiers($node),
                descriptions: $descriptions,
            );
        }
        return $ret;
    }

    public function addSubSegment(Segment $segment)
    {
        throw new Exception('function/method segment should not have sub segments');
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

        // $modifier = '';
        // if ($this->modifiers) {
        //     $modifier = implode(' ', $this->modifiers) . ' ';
        // }
        // // signature: `public const FOO = 1`
        // $ret .= "`{$modifier}const {$this->name}";
        // if ($this->value) {
        //     $ret .= " = {$this->value}";
        // }
        // $ret .= "`\n\n";
        $ret .= "{% include constSign.html name='{$this->name}' %}\n\n";

        // descriptions: FOO is a constant
        $br = '';
        foreach ($this->descriptions as $description) {
            $br = "\n";
            $ret .= "$description\n";
        }
        $ret .= $br;

        return $ret;
    }
}
