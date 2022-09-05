<?php

namespace Phpdoc2md\Codes;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

use Phpdoc2md\Markdown\{
    Page,
    ClassPage,
    ClassReferenceSegment,
    NamespacePage,
};
use Phpdoc2md\Markdown\{
    Segment,
    FunctionSegment,
    PropertySegment,
    ClassLikeSegment,
    ConstSegment,
};

class ThingsFinder extends NodeVisitorAbstract
{
    private ?Node\Stmt\Namespace_ $namespaceNode = null;
    private ?Node\Stmt\ClassLike $classNode = null;

    /** @var array<string> $namespace */
    private array $namespace = [];
    private ?string $class = null;

    private NamespacePage $namespacePage;
    private ?ClassPage $classPage;

    /** @var array<string, Page> $pages */
    private array $namespacePages = [];
    /** @var array<string, Page> $pages */
    private array $classPages = [];

    private ?Segment $classSegment = null;
    /** @var array<string, Segment> $classSegments */
    private array $classSegments = [];

    public function __construct(
        public bool $skipPrivate,
        public string $parentPage,
    ) {
        $this->namespacePage = new NamespacePage(
            name: '\\',
            parentPage: $parentPage,
        );
        $this->namespacePages[''] = $this->namespacePage;
    }

    public function enterNode(Node $node)
    {
        return $this->enterOrLeaveNode($node, true);
    }
    public function leaveNode(Node $node)
    {
        return $this->enterOrLeaveNode($node, false);
    }

    /**
     * merged node processor
     */
    private function enterOrLeaveNode(Node $node, bool $entering): null|Node|int
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            if ($entering) {
                $this->namespaceNode = $node;
                if ($node->name && $node->name->parts) {
                    $this->namespace = $node->name->parts;
                }
            } else {
                $this->namespaceNode = null;
                $this->namespace = [];
            }

            $page = $this->namespacePages[$this->getNamespaceString()] ?? null;
            if ($page) {
                $this->namespacePage = $page;
            } else {
                $this->namespacePage = new NamespacePage(
                    name: $this->getNamespaceString(),
                    parentPage: $this->parentPage,
                );
                $this->namespacePages[$this->getNamespaceString()] = $this->namespacePage;
            }
        }

        $namespaceString = $this->getNamespaceString();

        if ($node instanceof Node\Stmt\ClassLike) {
            if ($entering) {
                $this->classNode = $node;
                if ($node->name && $node->name->name) {
                    $name = "{$namespaceString}\\{$node->name->name}";
                }
                if (!($this->classPages[$name] ?? null)) {
                    $kind  =ClassLikeSegment::determindKind($node->getType());
                    $this->namespacePage->addSegment(new ClassReferenceSegment(
                        name: $node->name->name,
                        kind: $kind,
                    ));
                    $this->classPages[$name] = new ClassPage(
                        name: $name,
                        parent: $this->namespacePage,
                        kind: $kind,
                    );
                    $this->classSegments[$name] = ClassLikeSegment::fromPhpNode($node);
                    $this->classPages[$name]->addSegment($this->classSegments[$name]);
                }
                $this->classPage = $this->classPages[$name];
                $this->classSegment = $this->classSegments[$name];

            } else {
                $this->classNode = null;
                $this->classPage = null;
                $this->classSegment = null;
            }
        }

        if ($this->skipPrivate) {
            if (is_callable([$node, 'isPrivate']) && [$node, 'isPrivate']()) {
                // skip it
                return null;
            }

            if (
                is_callable([$node, 'isProtected']) &&
                [$node, 'isProtected']() &&
                $this->classNode &&
                is_callable([$this->classNode, 'isFinal']) &&
                [$this->classNode, 'isFinal']()
            ) {
                // skip it
                return null;
            }
        }


        // TODO: should we support global varibles ?

        if ($entering && $node instanceof Node\Stmt\Const_) {
            foreach (ConstSegment::allFromPhpNode($node) as $segment) {
                $this->namespacePage->addSegment($segment);
                $this->namespacePage->addSegmentMeta($segment);
            }
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($entering && $node instanceof Node\Stmt\Function_) {
            $segment = FunctionSegment::fromPhpNode($node);
            $this->namespacePage->addSegment($segment);
            $this->namespacePage->addSegmentMeta($segment);

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($entering && $node instanceof Node\Stmt\Property) {
            foreach (PropertySegment::allFromPhpNode($node) as $segment) {
                $this->classSegment->addSubSegment($segment);
                $this->classPage->addSegmentMeta($segment);
            }

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($entering && $node instanceof Node\Stmt\ClassConst) {
            foreach (ConstSegment::allFromPhpNode($node) as $segment) {
                $this->classSegment->addSubSegment($segment);
                $this->classPage->addSegmentMeta($segment);
            }

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($entering && $node instanceof Node\Stmt\ClassMethod) {
            $segment = FunctionSegment::fromPhpNode($node);
            $this->classSegment->addSubSegment($segment);
            $this->classPage->addSegmentMeta($segment);

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
        return null;
    }

    /**
     * get namespace string
     *
     * @return string
     */
    private function getNamespaceString(): string
    {
        return implode('\\', $this->namespace);
    }

    public function dumpAll(): array
    {
        $ret = [];

        foreach ($this->namespacePages as $name => $page) {
            $path = ltrim(str_replace('\\','/',$name) .'/index.md', '/');

            $content = $page->str();
            $ret[$path] = $content;
        }

        foreach ($this->classPages as $name => $page) {
            $path = ltrim(str_replace('\\','/',$name) .'.md', '/');

            $content = $page->str();
            $ret[$path] = $content;
        }

        return $ret;
    }

    
    /**
     * enumrate things
     */
    public function findThings(string $input)
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($input) ?? [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor($this);
        $traverser->traverse($ast);
    }

}
