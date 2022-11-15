<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Helper;

use Fp\Collections\ArrayList;
use FRZB\Component\Messenger\Bridge\Kafka\Exception\DiscriminatorMapException;
use JetBrains\PhpStorm\Immutable;
use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

#[Immutable]
final class ClassHelper
{
    public static function getClassName(string $targetClass, array|string $targetPayload): string
    {
        try {
            $targetPayload = \is_array($targetPayload) ? $targetPayload : json_decode($targetPayload, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $targetPayload = [];
        }

        if ($discriminatorMap = self::getAttribute($targetClass, DiscriminatorMap::class)) {
            $property = $discriminatorMap->getTypeProperty();
            $mapping = $discriminatorMap->getMapping();
            $parameter = $targetPayload[$property] ?? throw DiscriminatorMapException::missingProperty($targetClass, $property);
            $targetClass = $mapping[$parameter] ?? throw DiscriminatorMapException::missingTypeProperty($targetClass, $property, $mapping);
        }

        return $targetClass;
    }

    /**
     * @template T
     *
     * @param class-string<T> $attributeClass
     *
     * @return null|T
     */
    public static function getAttribute(string|object $target, string $attributeClass): ?object
    {
        return ArrayList::collect(self::getAttributes($target, $attributeClass))
            ->firstElement()
            ->get()
        ;
    }

    /**
     * @template T
     *
     * @param class-string<T> $attributeClass
     *
     * @return array<T>
     */
    public static function getAttributes(string|object $target, string $attributeClass): array
    {
        try {
            $attributes = (new \ReflectionClass($target))->getAttributes($attributeClass);
        } catch (\ReflectionException) {
            $attributes = [];
        }

        return ArrayList::collect($attributes)
            ->map(static fn (\ReflectionAttribute $a) => $a->newInstance())
            ->toArray()
        ;
    }
}
