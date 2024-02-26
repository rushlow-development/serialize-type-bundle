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

namespace RD\SerializeTypeBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use RD\SerializeTypeBundle\RDSerializeTypeBundle;
use RD\SerializeTypeBundle\Tests\Fixture\EntityRepositoryFixture;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class SerializeBundleTestKernel extends Kernel
{
    public function __construct(private ?ContainerBuilder $builder = null)
    {
        parent::__construct('test', true);
    }

    #[\Override]
    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new RDSerializeTypeBundle(), new DoctrineBundle()];
    }

    #[\Override]
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        if (null === $this->builder) {
            $this->builder = new ContainerBuilder();
        }

        $builder = $this->builder;

        $loader->load(function (ContainerBuilder $containerBuilder) use ($builder) {
            $containerBuilder->merge($builder);

            $containerBuilder->loadFromExtension('doctrine', [
                'dbal' => [
                    'driver' => 'pdo_sqlite',
                    'url' => 'sqlite:///'.$this->getCacheDir().'/app.db',
                ],
                'orm' => [
                    'auto_mapping' => true,
                    'mappings' => [
                        'App' => [
                            'is_bundle' => false,
                            'dir' => 'tests/Fixture/Entity/',
                            'prefix' => 'RD\SerializeTypeBundle\Tests\Fixture\Entity',
                            'alias' => 'App',
                        ],
                    ],
                ],
            ]);

            $containerBuilder->autowire(EntityRepositoryFixture::class, EntityRepositoryFixture::class)
                ->addTag('doctrine.repository_service')
            ;
        });
    }

    #[\Override]
    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/cache'.spl_object_hash($this);
    }

    #[\Override]
    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/logs'.spl_object_hash($this);
    }
}
