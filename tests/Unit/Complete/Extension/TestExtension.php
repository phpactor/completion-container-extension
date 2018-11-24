<?php

namespace Phpactor\Extension\CompletionContainer\Tests\Unit\Complete\Extension;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\MapResolver\Resolver;

class TestExtension implements Extension
{
    const SERVICE_FOO = 'constant_service';

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register('nothing.here', function (Container $container) {
        });
        $container->register('nothing.there', function (Container $container) {
        });
        $container->register(self::SERVICE_FOO, function (Container $container) {
        });
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }
}
