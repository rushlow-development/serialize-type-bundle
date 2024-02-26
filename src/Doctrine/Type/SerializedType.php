<?php

/*
 * Copyright 2024 Rushlow Development - Jesse Rushlow
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace RD\SerializeTypeBundle\Doctrine\Type;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use RD\SerializeTypeBundle\Exception\SerializeException;
use RD\SerializeTypeBundle\Exception\UnserializeException;
use RD\SerializeTypeBundle\SerializableTypeInterface;

/**
 * @author Jesse Rushlow<jr@rushlow.dev>
 *
 * @experimental
 */
final class SerializedType extends Type
{
    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (!\is_object($value)) {
            return null;
        }

        if (\in_array(SerializableTypeInterface::class, class_implements($value))) {
            /** @var SerializableTypeInterface $value */
            return $this->serialize($value);
        }

        if (!\in_array(Collection::class, class_implements($value))) {
            // If the object doesn't implement our type interface and isn't a collection
            // return null;
            return null;
        }

        $collectionItems = [];

        /** @var Collection<int|string, mixed> $value */
        foreach ($value as $collectionItemValue) {
            if (!\is_object($collectionItemValue)) {
                continue;
            }

            if (!\in_array(SerializableTypeInterface::class, class_implements($collectionItemValue))) {
                continue;
            }

            /** @var SerializableTypeInterface $collectionItemValue */
            $collectionItems[] = ['className' => $collectionItemValue::class, 'data' => $collectionItemValue->__serialize()];
        }

        $collection = ['className' => $value::class, 'data' => $collectionItems];

        return json_encode($collection, \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?object
    {
        if (!\is_string($value)) {
            return null;
        }

        try {
            $value = json_decode($value, associative: true, flags: \JSON_THROW_ON_ERROR);

            if (!\is_array($value)) {
                return null;
            }
        } catch (\JsonException $exception) {
            throw new UnserializeException($exception->getMessage(), $exception);
        }

        if (!\array_key_exists('className', $value) || !\array_key_exists('data', $value)) {
            return null;
        }

        $reflectedObject = new \ReflectionClass($value['className']);

        if ($reflectedObject->implementsInterface(SerializableTypeInterface::class)) {
            /** @var SerializableTypeInterface $instance */
            $instance = $reflectedObject->newInstanceWithoutConstructor();
            $instance->__unserialize($value['data']);

            return $instance;
        }

        if (!$reflectedObject->implementsInterface(Collection::class)) {
            return null;
        }

        $items = [];

        foreach ($value['data'] as $itemArray) {
            if (!\array_key_exists('className', $itemArray) || !\array_key_exists('data', $itemArray)) {
                continue;
            }

            $r = new \ReflectionClass($itemArray['className']);
            if (!$r->implementsInterface(SerializableTypeInterface::class)) {
                continue;
            }

            /** @var SerializableTypeInterface $instance */
            $instance = $r->newInstanceWithoutConstructor();
            $instance->__unserialize($itemArray['data']);

            $items[] = $instance;
        }

        return new $value['className']($items);
    }

    protected function serialize(SerializableTypeInterface $object): string
    {
        try {
            return json_encode(['className' => $object::class, 'data' => $object->__serialize()], \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION);
        } catch (\JsonException $exception) {
            throw new SerializeException($object, $exception->getMessage(), $exception);
        }
    }
}
