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
        if (!\is_object($value) && !\in_array(SerializableTypeInterface::class, class_implements($value))) {
            return null;
        }

        return $this->serialize($value);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?object
    {
        if (!\is_string($value)) {
            return null;
        }

        try {
            $value = json_decode($value, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new UnserializeException($exception->getMessage(), $exception);
        }

        $reflectedObject = new \ReflectionClass($value['className']);

        if (!$reflectedObject->implementsInterface(SerializableTypeInterface::class)) {
            return null;
        }

        $instance = $reflectedObject->newInstanceWithoutConstructor();
        $instance->__unserialize($value['data']);

        return $instance;
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
