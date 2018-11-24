<?php

namespace Phpactor\Extension\CompletionContainer\Tests\Unit\Complete;

use Microsoft\PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\ChainTolerantCompletor;
use Phpactor\Extension\CompletionContainer\Complete\ContainerCompletor;
use Phpactor\Filesystem\Adapter\Git\GitFilesystem;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\WorseReflection\ReflectorBuilder;

class ContainerCompletorTest extends TestCase
{
    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        [$source, $offset] = ExtractOffset::fromSource($source);
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        $filesystem = new GitFilesystem(__DIR__ . '/../../..');
        $containerCompletor = new ContainerCompletor($reflector, $filesystem);
        $completor = new ChainTolerantCompletor([
            $containerCompletor
        ]);
        $suggestions = iterator_to_array($completor->complete($source, $offset));
    }

    public function provideComplete()
    {
        yield [
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
