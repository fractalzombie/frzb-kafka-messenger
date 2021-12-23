<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Exception;

use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceivedStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException as BaseTransportException;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Uid\Uuid;

final class TransportException extends BaseTransportException
{
    private const NO_RECEIVED_STAMP_MESSAGE = 'Message "%s" with id "%s" has no "%s" stamp';

    public static function fromThrowable(\Throwable $e): self
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }

    public static function noReceivedStamp(Envelope $envelope): self
    {
        $messageClass = $envelope->getMessage()::class;
        $messageId = $envelope->last(TransportMessageIdStamp::class)?->getId() ?? (string) Uuid::v4();
        $message = sprintf(self::NO_RECEIVED_STAMP_MESSAGE, $messageClass, $messageId, KafkaReceivedStamp::class);

        return new self($message);
    }
}
