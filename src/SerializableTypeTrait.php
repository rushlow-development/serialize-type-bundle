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

namespace RD\SerializeTypeBundle;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @experimental
 */
trait SerializableTypeTrait
{
    public function __serialize(): array
    {
        $data = [];

        foreach ((new \ReflectionClass($this))->getProperties() as $property) {
            $data[$property->getName()] = $property->getValue($this);
        }

        return $data;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $propertyName => $value) {
            $this->$propertyName = $value;
        }
    }
}
