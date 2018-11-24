<?php

namespace Phpactor\Extension\CompletionContainer\Tests\Unit\Complete;

use Microsoft\PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\ChainTolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Extension\CompletionContainer\Complete\ContainerCompletor;
use Phpactor\Filesystem\Adapter\Git\GitFilesystem;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\WorseReflection\Bridge\Composer\ComposerSourceLocator;
use Phpactor\WorseReflection\Bridge\Phpactor\ClassToFileSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;

class ContainerCompletorTest extends TestCase
{
    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        [$source, $offset] = ExtractOffset::fromSource($source);

        $filesystem = new GitFilesystem(__DIR__ . '/../../..');
        $reflector = ReflectorBuilder::create()
            ->addLocator(new ComposerSourceLocator(require __DIR__ . '/../../../vendor/autoload.php'))
            ->addSource($source)->build();

        $containerCompletor = new ContainerCompletor($reflector, $filesystem);
        $completor = new ChainTolerantCompletor([
            $containerCompletor
        ]);
        $suggestions = iterator_to_array($completor->complete($source, $offset));

        foreach ($expected as $serviceId) {
            $suggestion = array_reduce($suggestions, function ($carry, Suggestion $suggestion) use ($serviceId) {
                if ($serviceId === $suggestion->name()) {
                    $carry = $suggestion;
                }
                return $carry;
            }, null);

            $this->assertNotNull($suggestion);
        }
    }

    public function provideComplete()
    {
        yield 'from plain string registrations' => [
            <<<'EOT'
<?php

use Phpactor\Container\Container;

$fo = function (Container $container) {
    $container->get(<>
}
EOT
            ,
            [
                '\'nothing.here\'',
                '\'nothing.there\'',
            ]
        ];

        yield 'from constant registrations' => [
            <<<'EOT'
<?php

use Phpactor\Container\Container;

$fo = function (Container $container) {
    $container->get(<>
}
EOT
            ,
            [
            ]
        ];
    }
}
