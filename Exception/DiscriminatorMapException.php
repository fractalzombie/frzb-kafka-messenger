<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Exception;

use Fp\Collections\Entry;
use Fp\Collections\HashMap;

final class DiscriminatorMapException extends \LogicException
{
    private const MISSING_PARAMETER_ERROR_MESSAGE = 'Missing property "%s" with class "%s"';
    private const MISSING_TYPE_PROPERTY_ERROR_MESSAGE = 'Missing mapping for type "%s" in class "%s" with mapping "%s"';

    public static function fromThrowable(\Throwable $previous): self
    {
        return new self($previous->getMessage(), $previous->getCode(), $previous);
    }

    public static function missingProperty(string $className, string $propertyName, ?\Throwable $previous = null): self
    {
        $message = sprintf(self::MISSING_PARAMETER_ERROR_MESSAGE, $propertyName, $className);

        return new self($message, previous: $previous);
    }

    public static function missingTypeProperty(string $className, string $typeProperty, array $mapping, ?\Throwable $previous = null): self
    {
        $map = HashMap::collect($mapping)
            ->map(static fn (Entry $entry) => "{$entry->key}: {$entry->value}")
            ->toArrayList()
            ->mkString()
        ;

        $message = sprintf(self::MISSING_TYPE_PROPERTY_ERROR_MESSAGE, $typeProperty, $className, $map);

        return new self($message, previous: $previous);
    }
}
