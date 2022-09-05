<?php

namespace Phpdoc2md\Markdown;

use PhpParser\Node\Stmt\{
    ClassLike,
};
use PHPStan\PhpDocParser\Ast\PhpDoc\{
    TemplateTagValueNode,
};

    class ClassLikeSegment extends Segment
    {
        public string $type = 'classLike';

        protected function __construct(
            public string $name,
            public string $kind,
            public array $templateNotes,
            public array $descriptions,
        ) {
        }

        public static function determindKind(string $type):string{
            return match($type) {
                'Stmt_Class' => 'class',
                'Stmt_Trait' => 'trait',
                'Stmt_Interface' => 'interface',
                'Stmt_Enum' => 'enum',
            };
        }
        
        public static function fromPhpNode(ClassLike $node): static
        {
            
            $name = $node->name->name;

            $kind = static::determindKind($node->getType());

            // parse comment node
            $docNode = static::parseDocument($node);

            // parse template tags
            $templateNotes = [];
            $templateNodes = static::getTags($docNode, 'template');
            foreach ($templateNodes as $templateNode) {
                /** @var TemplateTagValueNode $templateTagValueNode */
                $templateTagValueNode = $templateNode->value;
                //var_dump($templateTagValueNode);
                $templateNotes[] = [
                    'name' => (string) $templateTagValueNode->name,
                    'bound' => (string) $templateTagValueNode->bound,
                    'description' => (string) $templateTagValueNode->description,
                ];
            }

            // process remaining things
            $descriptions = static::parseCommonTags($docNode, ['template']);

            return new static(
                name: $name,
                kind: $kind,
                templateNotes: $templateNotes,
                descriptions: $descriptions,
            );
        }

        /**
         * to string
         *
         * @param integer $level markdown title level
         * @return string
         */
        public function str($level = 1): string
        {
            $fixTitles = function (string $title, string $type, string $matchType) {
                $prove = false;
                foreach ($this->subSegments as $seg) {
                    if ($seg->type === $matchType) {
                        $prove = true;
                    }
                }
                $this->subSegments = array_filter($this->subSegments, fn($s)=>$s->type !== $type);
                if (!$prove) {
                    return;
                }
                
                $segment = new TitleSegment(
                    title: $title,
                    name: $type,
                );
                $segment->type = $type;
                $this->addSubSegment($segment);
            };
            $fixTitles('Constants','constTitle','const');
            $fixTitles('Properties','propertiesTitle','property');
            $fixTitles('Methods','methodsTitle','function');

            // start build
            $ret = '';

            // title: ## Class Foo
            $ret .= str_repeat('#', $level) . ' ' . ucfirst($this->kind). " {$this->name}\n\n";

            // descriptions: Foo is a class
            $br = '';
            foreach ($this->descriptions as $description) {
                $br = "\n";
                $ret .= "$description\n";
            }
            $ret .= $br;

            // template notes: This class is a generic class.\n**Template** `T` of `A|B` is something
            $br = '';
            foreach ($this->templateNotes as $note) {
                $br = "\n";
                $ret .= "This class is a generic class.\n";
                $ret .= "**Template** `{$note['name']}`";
                if ($note['bound']) {
                    $ret .= " of `{$note['bound']}`";
                }
                if ($note['description']) {
                    $ret .= " {$note['description']}";
                }
                $ret .= "\n";
            }
            $ret .= $br;
            
            $ret .= "\n";

            // sub segments
            $ret .= parent::str($level);

            return $ret;
        }

        /**
         * sort segments
         */
        public function sort()
        {
            uasort($this->subSegments, function ($seg1, $seg2) {
                $typeKey = fn ($s) => match ($s->type) {
                    'constTitle' => 0,
                    'const' => 1,
                    'propertiesTitle' => 2,
                    'property' => 3,
                    'methodsTitle' => 4,
                    'function' => 5,
                };
                if ($typeKey($seg1) !== $typeKey($seg2)) {
                    return $typeKey($seg1) - $typeKey($seg2);
                }
                if ($seg1->type === 'function') {
                    $methodTypeKey = fn ($s) => match ($s->kind) {
                        'static method' => 0,
                        'method' => 1,
                    };
                    if ($methodTypeKey($seg1) !== $methodTypeKey($seg2)) {
                        return $methodTypeKey($seg1) - $methodTypeKey($seg2);
                    }
                }
                return strcmp($seg1->name, $seg2->name);
            });
        }

    }
