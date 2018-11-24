<?php

namespace Phpactor\Extension\CompletionContainer\Complete;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\StringLiteral;
use Microsoft\PhpParser\Parser;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Container\Container;
use Phpactor\Container\Extension;
use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Filesystem\Domain\Filesystem;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use SplFileInfo;

class ContainerCompletor implements TolerantCompletor
{
    /**
     * @var SourceCodeReflector
     */
    private $reflector;

    /**
     * @var Filesystem
     */
    private $filesystem;


    public function __construct(SourceCodeReflector $reflector, Filesystem $filesystem)
    {
        $this->reflector = $reflector;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(Node $node, string $source, int $offset): Generator
    {
        if (!$node instanceof CallExpression) {
            return;
        }

        $node = $node->callableExpression;

        if (!$node instanceof MemberAccessExpression) {
            return;
        }

        if ($node->memberName->getText($node->getFileContents()) === 'get') {
            yield from $this->serviceSuggestions($node);
        }
    }

    private function serviceSuggestions(MemberAccessExpression $expression): Generator
    {
        $offset = $this->reflector->reflectOffset(
            $expression->getFileContents(),
            $expression->getStart()
        );
        $types = $offset->symbolContext()->types();

        if ($types->best()->__toString() !== Container::class) {
            return;
        }

        yield from $this->buildSuggestions();
    }

    private function buildSuggestions(): Generator
    {
        $candidates = $this->filesystem->fileList()->filter(function (SplFileInfo $file) {
            return false !== strpos($file->getFilename(), 'Extension');
        });

        /** @var FilePath $candidate */
        foreach ($candidates as $candidate) {
            $classes = $this->reflector->reflectClassesIn(file_get_contents($candidate->path()));

            foreach ($classes as $class) {
                if (!$class->isInstanceOf(ClassName::fromString(Extension::class))) {
                    continue;
                }

                foreach ($this->serviceReferences($class) as $reference) {
                    yield Suggestion::createWithOptions($reference, [
                        'type' => Suggestion::TYPE_VALUE
                    ]);
                }

                return;
            }
        }
    }

    private function serviceReferences(ReflectionClass $class): array
    {
        $parser = new Parser();
        $node = $parser->parseSourceFile($class->sourceCode()->__toString());
        $references = [];
        $node->walkDescendantNodesAndTokens(function ($node) use (&$references) {
            if (!$node instanceof Node) {
                return;
            }
            if (!$node instanceof MemberAccessExpression) {
                return;
            }

            if ($node->memberName->getText($node->getFileContents()) !== 'register') {
                return;
            }

            $call = $node->parent;

            if (!$call instanceof CallExpression) {
                return;
            }

            /** @var ArgumentExpression $node */
            foreach ($call->argumentExpressionList->getElements() as $node) {
                if ($node->expression instanceof StringLiteral) {
                    $references[] = $node->expression->getStringContentsText();
                }
                return;
            }
        });

        return $references;
    }
}
