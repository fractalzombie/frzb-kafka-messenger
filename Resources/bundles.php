<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    FRZB\Component\Messenger\Bridge\Kafka\KafkaMessengerExtension::class => ['all' => true],
];
