<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Mykhailo Shtanko <fractalzombie@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Transport;

use Fp\Collections\ArrayList;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Fixtures\KafkaMessage;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\OptionsHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceivedStamp;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

/**
 * @requires extension rdkafka
 *
 * @group kafka-messenger
 *
 * @internal
 */
class KafkaExtIntegrationTest extends TestCase
{
    public function testItSendsAndReceivesMessages(): void
    {
        if (!getenv('MESSENGER_KAFKA_DSN')) {
            $this->markTestSkipped('The "MESSENGER_KAFKA_DSN" environment variable is required.');
        }

        $options = OptionsHelper::getOptions();
        $transport = (new KafkaTransportFactory())->createTransport(getenv('MESSENGER_KAFKA_DSN'), $options);

        $transport->send($first = new Envelope(new KafkaMessage('Message')));

        $envelopes = ArrayList::collect($transport->get());
        $this->assertCount(1, $envelopes);
        /** @var Envelope $envelope */
        $envelope = $envelopes->firstElement()->get();
        $transport->ack($envelope);
        $this->assertEquals($first->getMessage(), $envelope->getMessage());
        $this->assertInstanceOf(KafkaReceivedStamp::class, $envelope->last(KafkaReceivedStamp::class));
    }
}
