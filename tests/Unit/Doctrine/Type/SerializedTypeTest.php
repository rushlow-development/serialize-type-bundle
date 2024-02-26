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

namespace RD\SerializeTypeBundle\Tests\Unit\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;
use RD\SerializeTypeBundle\Doctrine\Type\SerializedType;
use RD\SerializeTypeBundle\Tests\Fixture\SimpleObjectFixture;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class SerializedTypeTest extends TestCase
{
    public function testConvertsToJson(): void
    {
        $result = (new SerializedType())->convertToDatabaseValue(
            value: new SimpleObjectFixture('Jesse', 'Developer'),
            platform: $this->createMock(AbstractPlatform::class)
        );

        self::assertSame(
            expected: '{"className":"RD\\\SerializeTypeBundle\\\Tests\\\Fixture\\\SimpleObjectFixture","data":{"name":"Jesse","description":"Developer"}}',
            actual: $result
        );
    }

    public function testConvertsJsonToObject(): void
    {
        $result = (new SerializedType())->convertToPHPValue(
            value: '{"className":"RD\\\SerializeTypeBundle\\\Tests\\\Fixture\\\SimpleObjectFixture","data":{"name":"Jesse","description":"Developer"}}',
            platform: $this->createMock(AbstractPlatform::class),
        );

        $expected = new SimpleObjectFixture('Jesse', 'Developer');

        self::assertEquals($expected, $result);
    }
}
