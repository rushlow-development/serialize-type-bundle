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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use RD\SerializeTypeBundle\Doctrine\Type\SerializedType;
use RD\SerializeTypeBundle\Tests\Fixture\Entity\EntityFixture;
use RD\SerializeTypeBundle\Tests\Fixture\SimpleObjectFixture;
use RD\SerializeTypeBundle\Tests\SerializeBundleTestKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class BundleTest extends TestCase
{
    private KernelInterface $kernel;
    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $builder = new ContainerBuilder();
        $definition = $builder->autowire('app.service', ServiceFixture::class);
        $definition->setPublic(true);
        $definition->setAutoconfigured(true);

        $this->kernel = new SerializeBundleTestKernel($builder);
        $this->kernel->boot();

        $container = $this->kernel->getContainer();

        /** @var EntityManagerInterface $entityManager */
        $this->entityManager = $container->get('doctrine')->getManager(); // @phpstan-ignore-line

        $tools = new SchemaTool($this->entityManager);
        $tools->dropDatabase();
        $tools->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
    }

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
        $simpleObject = new SimpleObjectFixture(name: 'Jesse', description: 'Developer');
        $entity = new EntityFixture($simpleObject);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $result = $this->entityManager->getConnection()->executeQuery('SELECT * FROM EntityFixture;');
        $result = $result->fetchAllAssociative();

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys([
            'simpleObjectFixture' => '{"className":"RD\\\\SerializeTypeBundle\\\\Tests\\\\Fixture\\\\SimpleObjectFixture","data":{"name":"Jesse","description":"Developer"}}',
            'id' => 1,
        ], $result[0], ['fixtures']);

        $repository = $this->entityManager->getRepository(EntityFixture::class);

        $results = $repository->findAll();
        self::assertCount(1, $results);

        self::assertSame($entity, $results[0]);
    }

    public function testCollectionSerialization(): void
    {
        $collection = new ArrayCollection();
        $collection->add(new SimpleObjectFixture(name: 'Jesse', description: 'Collection'));

        $simpleObject = new SimpleObjectFixture(name: 'Jesse', description: 'Developer');
        $entity = new EntityFixture($simpleObject, fixtures: $collection);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $result = $this->entityManager->getConnection()->executeQuery('SELECT * FROM EntityFixture;');
        $result = $result->fetchAllAssociative();

        self::assertSame([
            'simpleObjectFixture' => '{"className":"RD\\\\SerializeTypeBundle\\\\Tests\\\\Fixture\\\\SimpleObjectFixture","data":{"name":"Jesse","description":"Developer"}}',
            'id' => 1,
            'fixtures' => '{"className":"Doctrine\\\\Common\\\\Collections\\\\ArrayCollection","data":[{"className":"RD\\\\SerializeTypeBundle\\\\Tests\\\\Fixture\\\\SimpleObjectFixture","data":{"name":"Jesse","description":"Collection"}}]}',
        ], $result[0]);

        $repository = $this->entityManager->getRepository(EntityFixture::class);

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
