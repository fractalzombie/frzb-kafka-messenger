<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class KafkaMessengerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerExtension(new KafkaMessengerExtension());
    }
}
