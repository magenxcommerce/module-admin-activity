<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Test\Unit;

use Magenx\AdminActivity\Model\Activity\EntityRegistry;
use PHPUnit\Framework\TestCase;

/** Stand-in for a tracked model. */
class RegistryStubEntity
{
}

/** Stand-in for the generated interceptor Magento actually hands an observer. */
class RegistryStubEntityChild extends RegistryStubEntity
{
}

/** Stand-in for a model nobody configured. */
class RegistryStubStranger
{
}

class EntityRegistryTest extends TestCase
{
    private EntityRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new EntityRegistry([
            'stub' => [
                'class' => RegistryStubEntity::class,
                'label' => 'Stub',
                'name_field' => 'title',
            ],
        ]);
    }

    public function testConfiguredClassResolves(): void
    {
        $entity = $this->registry->resolve(RegistryStubEntity::class);

        self::assertNotNull($entity);
        self::assertSame('Stub', $entity['label']);
        self::assertSame('title', $entity['name_field']);
    }

    public function testSubclassResolvesThroughItsAncestors(): void
    {
        // What reaches an observer is almost never the configured class but a
        // generated ...\Interceptor subclass of it.
        self::assertNotNull($this->registry->resolveObject(new RegistryStubEntityChild()));
        self::assertTrue($this->registry->isTracked(new RegistryStubEntityChild()));
    }

    public function testUnknownClassIsNotTracked(): void
    {
        self::assertNull($this->registry->resolve(RegistryStubStranger::class));
        self::assertFalse($this->registry->isTracked(new RegistryStubStranger()));
    }

    public function testLeadingSlashesAndCaseDoNotDefeatTheLookup(): void
    {
        self::assertNotNull($this->registry->resolve('\\' . RegistryStubEntity::class));
        self::assertNotNull($this->registry->resolve(strtolower(RegistryStubEntity::class)));
    }

    public function testAllowlistAcceptsOnlyTheExactConfiguredClass(): void
    {
        self::assertSame(RegistryStubEntity::class, $this->registry->getAllowedClass(RegistryStubEntity::class));
    }

    public function testAllowlistRejectsASubclass(): void
    {
        // Deliberately stricter than resolve(): entity_type is a class name read
        // back out of a database column, and instantiating whatever it happens
        // to name is exactly what this guards against.
        self::assertNull($this->registry->getAllowedClass(RegistryStubEntityChild::class));
    }

    public function testAllowlistRejectsAnUnlistedClass(): void
    {
        self::assertNull($this->registry->getAllowedClass(RegistryStubStranger::class));
        self::assertNull($this->registry->getAllowedClass('Evil\\Payload'));
    }

    public function testEntryWithoutAClassIsIgnored(): void
    {
        $registry = new EntityRegistry(['broken' => ['label' => 'No class here']]);

        self::assertSame([], $registry->getLabels());
    }

    public function testLabelsFeedTheGridFilter(): void
    {
        self::assertSame([RegistryStubEntity::class => 'Stub'], $this->registry->getLabels());
    }
}
