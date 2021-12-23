<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Mykhailo Shtanko <fractalzombie@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Transport;

use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 *
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
class KafkaLogger
{
    private const ACK_MESSAGE_TEMPLATE = '[KAFKA] [ACKNOWLEDGED] [{payload}] [{headers}] [topic={topic}, partition={partition}, offset={offset}] [Successfully committed {type}]';
    private const RECEIVE_MESSAGE_TEMPLATE = '[KAFKA] [RECEIVED] [{payload}] [{headers}] [topic={topic}, partition={partition}, offset={offset}] [Received successfully]';
    private const PUBLISH_MESSAGE_TEMPLATE = '[KAFKA] [PUBLISHED] [{payload}] [{headers}] [Successfully]';
    private const ERROR_MESSAGE_TEMPLATE = '[KAFKA] [EXCEPTION] [{exception}] [{message}]';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $kafkaLogger)
    {
        $this->logger = $kafkaLogger;
    }

    /** @throws \JsonException */
    public function logAcknowledge(KafkaStamp $message, KafkaReceiverConfiguration $configuration): void
    {
        $context = [
            'topic' => $message->getTopicName(),
            'partition' => $message->getPartition(),
            'offset' => $message->getOffset(),
            'type' => $configuration->isCommitAsync() ? 'asynchronously' : 'synchronously',
            'headers' => json_encode($message->getHeaders(), \JSON_THROW_ON_ERROR),
            'payload' => $message->getBody(),
        ];

        $this->logger->info(self::ACK_MESSAGE_TEMPLATE, $context);
    }

    /** @throws \JsonException */
    public function logReceive(KafkaStamp $message): void
    {
        $context = [
            'topic' => $message->getTopicName(),
            'partition' => $message->getPartition(),
            'offset' => $message->getOffset(),
            'headers' => json_encode($message->getHeaders(), \JSON_THROW_ON_ERROR),
            'payload' => $message->getBody(),
        ];

        $this->logger->info(self::RECEIVE_MESSAGE_TEMPLATE, $context);
    }

    /** @throws \JsonException */
    public function logProduce(array $payload): void
    {
        $context = [
            'payload' => $payload['body'],
            'headers' => json_encode($payload['headers'] ?? [], \JSON_THROW_ON_ERROR),
        ];

        $this->logger->info(self::PUBLISH_MESSAGE_TEMPLATE, $context);
    }

    public function logError(\Throwable $exception): void
    {
        $context = [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTrace(),
            'exception' => $exception::class,
        ];

        $this->logger->error(self::ERROR_MESSAGE_TEMPLATE, $context);
    }
}
