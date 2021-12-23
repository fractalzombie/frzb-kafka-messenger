<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper;

use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Serializer as SerializerComponent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class DependencyHelper
{
    public static function getSerializer(): SerializerInterface
    {
        return new Serializer(
            new SerializerComponent\Serializer(
                [new ObjectNormalizer(), new ArrayDenormalizer()],
                [JsonEncoder::FORMAT => new JsonEncoder()],
            )
        );
    }
}
