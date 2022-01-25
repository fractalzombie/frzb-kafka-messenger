<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Integration;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;

abstract class MessageSerializer extends Serializer
{
    public function decode(array $encodedEnvelope): Envelope
    {
        return parent::decode(['body' => self::getBody($encodedEnvelope), 'headers' => self::getHeaders($encodedEnvelope)]);
    }

    abstract protected static function getMessageType(): string;

    private static function getBody(array $decodedEnvelope): ?string
    {
        return $decodedEnvelope['body'] ?? '{}';
    }

    private static function getHeaders(array $decodedEnvelope): array
    {
        return array_merge($decodedEnvelope['headers'] ?? [], ['type' => static::getMessageType()]);
    }
}
