<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper;

use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as MessengerSerializer;
use Symfony\Component\Serializer as SerializerComponent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializer;

final class DependencyHelper
{
    public static function getMessengerSerializer(): MessengerSerializer
    {
        return new Serializer(self::getSymfonySerializer());
    }

    public static function getSymfonySerializer(): SymfonySerializer
    {
        return new SerializerComponent\Serializer(
            [new ObjectNormalizer(), new ArrayDenormalizer()],
            [JsonEncoder::FORMAT => new JsonEncoder()],
        );
    }
}
