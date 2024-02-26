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

namespace RD\SerializeTypeBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use RD\SerializeTypeBundle\Doctrine\Type\SerializedType;
use RD\SerializeTypeBundle\Tests\Fixture\Entity\EntityFixture;
use RD\SerializeTypeBundle\Tests\Fixture\SimpleObjectFixture;
use RD\SerializeTypeBundle\Tests\SerializeBundleTestKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class BundleTest extends TestCase
{
    public function testSerializedTypeIsRegistered(): void
    {
        $kernel = new SerializeBundleTestKernel();
        $kernel->boot();

        $container = $kernel->getContainer();
        $types = $container->getParameter('doctrine.dbal.connection_factory.types');

        self::assertSame(['serialized' => ['class' => SerializedType::class]], $types);
    }

    public function testSerialization(): void
    {
        $builder = new ContainerBuilder();
        $definition = $builder->autowire('app.service', ServiceFixture::class);
        $definition->setPublic(true);
        $definition->setAutoconfigured(true);

        $kernel = new SerializeBundleTestKernel($builder);
        $kernel->boot();

        /** @var EntityManagerInterface $manager */
        $manager = $kernel->getContainer()->get('app.service')->manager;

        $tools = new SchemaTool($manager);
        $tools->dropDatabase();
        $tools->createSchema($manager->getMetadataFactory()->getAllMetadata());

        $simpleObject = new SimpleObjectFixture(name: 'Jesse', description: 'Developer');
        $entity = new EntityFixture($simpleObject);

        $manager->persist($entity);
        $manager->flush();

        $result = $manager->getConnection()->executeQuery('SELECT * FROM EntityFixture;');
        $result = $result->fetchAllAssociative();

        self::assertEquals([
            'id' => 1,
            'simpleObjectFixture' => '{"className":"RD\\\\SerializeTypeBundle\\\\Tests\\\\Fixture\\\\SimpleObjectFixture","data":{"name":"Jesse","description":"Developer"}}',
        ], $result[0]);

        $repository = $manager->getRepository(EntityFixture::class);

        $results = $repository->findAll();
        self::assertCount(1, $results);

        self::assertSame($entity, $results[0]);
    }
}

class ServiceFixture
{
    public function __construct(public EntityManagerInterface $manager)
    {
    }
}
