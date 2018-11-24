<?php

namespace Phpactor\Extension\CompletionContainer\Complete;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Container\Container;
use Phpactor\Filesystem\Domain\Filesystem;
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
        $candidates = $this->filesystem->fileList()->named('.*Extension.php');
        var_dump(iterator_to_array($candidates));

        /** @var SplFileInfo $candidate */
        foreach ($candidates as $candidate) {
            $classes = $this->reflector->reflectClassesIn(file_get_contents($candidate->getPathname()));

            foreach ($classes as $class) {
                var_dump($class->name());
            }
        }
    }
}
