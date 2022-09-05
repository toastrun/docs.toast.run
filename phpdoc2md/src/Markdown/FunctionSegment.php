<?php

namespace Phpdoc2md\Markdown;

use Exception;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use PhpParser\Node\{
    Variable,
};
use PhpParser\Node\Stmt\{
    ClassMethod,
    Function_,
};
use PHPStan\PhpDocParser\Ast\PhpDoc\{
    ReturnTagValueNode,
    ParamTagValueNode,
    ThrowsTagValueNode,
};

class FunctionSegment extends Segment
{
    public string $type = 'function';

    protected function __construct(
        public string $name,
        public string $kind,
        public array $params,
        public ?string $returnType,
        public array $returnNotes,
        public array $throwsNotes,
        // public string $signature,
        public array $modifiers,
        public array $descriptions,
    ) {
    }

    public static function fromPhpNode(Function_|ClassMethod $node): static
    {
        // parse php node first
        $prettyPrinter = new PrettyPrinter\Standard();

        $name = $node->name->name;

        $params = [];
        foreach ($node->params as $paramNode) {
            /** @var Variable $varNode */
            $varNode = $paramNode->var;
            $varName = $varNode->name;
            if (!is_string($varName)) {
                throw new Exception("strange things");
            }
            $params[$varName] = [
                'variadic' => $paramNode->variadic,
                'byRef' => $paramNode->byRef,
                'type' => $paramNode->type ? $prettyPrinter->prettyPrint([$paramNode->type]) : null,
            ];
        }

        $returnType = $node->returnType ? $prettyPrinter->prettyPrint([$node->returnType]) : null;

        // parse comment node
        $docNode = static::parseDocument($node);

        // fix param type
        $paramNodes = static::getTags($docNode, 'param');
        foreach ($paramNodes as $paramNode) {
            /** @var ParamTagValueNode $paramValueNode */
            $paramValueNode = $paramNode->value;
            $paramName = ltrim((string) $paramValueNode->parameterName, '$');
            if (!array_key_exists($paramName, $params)) {
                echo "???? not found arg $paramName for function $name\n";
                continue;
            }
            // should this be applied?
            //$params[$paramName]['variadic'] = $paramValueNode->isVariadic;
            //$params[$paramName]['byRef'] = $paramValueNode->isReference;
            $params[$paramName]['type'] = (string)$paramValueNode->type;
            // after override, check if it's necessary to generate notes
            if (
                (string) $paramValueNode->description ||
                $params[$paramName]['variadic'] !== $paramValueNode->isVariadic ||
                $params[$paramName]['byRef'] !== $paramValueNode->isReference ||
                $params[$paramName]['type'] !== (string)$paramValueNode->type
            ) {
                $params[$paramName]['description'] = (string) $paramValueNode->description;
            }
        }

        // fix return type
        $returnNotes = [];
        $docReturnType = [];
        $returnNodes = static::getTags($docNode, 'return');
        foreach ($returnNodes as $returnNode) {
            /** @var ReturnTagValueNode $returnValueNode */
            $returnValueNode = $returnNode->value;
            $returnTypeStr = (string)$returnValueNode->type;
            if (!in_array($returnTypeStr, $docReturnType)) {
                $docReturnType[] = $returnTypeStr;
            }
            $desc = (string) $returnValueNode->description;
            if ($desc) {
                $returnNotes[] = [
                    'type' => (string) $returnValueNode->type,
                    'description' => (string) $returnValueNode->description,
                ];
            }
        }
        sort($docReturnType);
        $docReturnType = implode('|', $docReturnType);
        if ($docReturnType) {
            $returnType = $docReturnType;
        }

        // throws note
        $throwsNotes = [];
        $throwsNodes = static::getTags($docNode, 'throws');
        foreach ($throwsNodes as $throwsNode) {
            /** @var ThrowsTagValueNode $throwsValueNode */
            $throwsValueNode = $throwsNode->value;
            $throwsNotes[] = [
                'type' => (string) $throwsValueNode->type,
                'description' => (string) $throwsValueNode->description,
            ];
        }

        // make signature text
        // $signatureNode = new ($node::class)($name);
        // $signatureNode->params = $node->params;
        // $index = 0;
        // foreach ($params as $paramName => $param) {
        //     $signatureNode->params[$index]->var = new Node\Expr\Variable(
        //         $paramName,
        //     );
        //     $signatureNode->params[$index]->byRef = $param['byRef'];
        //     $signatureNode->params[$index]->variadic = $param['variadic'];
        //     $signatureNode->params[$index]->type = $param['type'] ? new Node\Name($param['type']) :null;
        // };
        // $signatureNode->returnType = $returnType ? new Node\Name($returnType) : null;
        // if (isset($node->flags)) {
        //     $signatureNode->flags = $node->flags;
        // }
        // $signature = rtrim($prettyPrinter->prettyPrint([$signatureNode]), characters: " \t\n\r\0\x0B{}");

        // process remaining things
        $descriptions = static::parseCommonTags($docNode, ['param', 'return', 'throws']);

        $kind = match (true) {
            ($node instanceof Function_) => 'function',
            ($node instanceof ClassMethod && $node->isStatic()) => 'static method',
            ($node instanceof ClassMethod) => 'method',
        };

        $modifiers = static::modifiers($node);

        return new static(
            name: $name,
            kind: $kind,
            params: $params,
            returnType: $returnType,
            returnNotes: $returnNotes,
            throwsNotes: $throwsNotes,
            // signature: $signature,
            modifiers: $modifiers,
            descriptions: $descriptions,
        );
    }

    public function addSubSegment(Segment $segment)
    {
        throw new Exception('function/method segment should not have sub segments');
    }

    /**
     * to string
     *
     * @param integer $level markdown title level (not used in function/method segment)
     * @return string
     */
    public function str($level = 1): string
    {
        $ret = '';

        $kind = ucfirst($this->kind);

        // title: ## Function foo
        $ret .= str_repeat('#', $level) . " {$kind} {$this->name}\n\n";
        // signature: `function foo(array<string> $bar): void`
        // $ret .= "`{$this->signature}`\n\n";
        $ret .= "{% include funcSign.html name='{$this->name}' %}\n\n";

        // descriptions: foo is a function
        $br = '';
        foreach ($this->descriptions as $description) {
            $br = "\n";
            $ret .= "$description\n";
        }
        $ret .= $br;

        // params: **Param** `array<string>` **$bar** bar is an array of string
        $br = '';
        foreach ($this->params as $name => $param) {
            if ($param['description'] ?? null) {
                $br = "\n";
                if ($param['type'] !== null ) {
                    $type = " `{$param['type']}`";
                }
                $ret .= "**Param**$type **\$$name** {$param['description']}\n";
            }
        }
        $ret .= $br;

        // return notes: **Returns** `int` if something
        $br = '';
        foreach ($this->returnNotes as $note) {
            $br = "\n";
            $ret .= "**Returns** `{$note['type']}` {$note['description']}\n";
        }
        $ret .= $br;

        // throws notes: **Throws** `Error` if something
        $br = '';
        foreach ($this->throwsNotes as $note) {
            $br = "\n";
            $ret .= "**Throws** `{$note['type']}` {$note['description']}\n";
        }
        $ret .= $br;

        return $ret;
    }
}