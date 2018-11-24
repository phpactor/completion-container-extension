<?php

namespace Phpactor\Extension\CompletionContainer;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\CompletionContainer\Complete\ContainerCompletor;
use Phpactor\Extension\CompletionWorse\CompletionWorseExtension;
use Phpactor\Extension\Completion\CompletionExtension;
use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\MapResolver\Resolver;

class CompletionContainerExtension implements Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register('completion_container.completor.container', function (Container $container) {
            return new ContainerCompletor(
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(SourceCodeFilesystemExtension::SERVICE_FILESYSTEM_GIT)
            );
        }, [ CompletionWorseExtension::TAG_TOLERANT_COMPLETOR => []]);
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }
}
