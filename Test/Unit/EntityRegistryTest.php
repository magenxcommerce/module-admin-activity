<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Test\Unit;

use Magenx\AdminActivity\Model\Activity\EntityRegistry;
use Magenx\AdminActivity\Test\Unit\Stub\TrackedEntity;
use Magenx\AdminActivity\Test\Unit\Stub\TrackedEntityChild;
use Magenx\AdminActivity\Test\Unit\Stub\UntrackedEntity;
use PHPUnit\Framework\TestCase;

class EntityRegistryTest extends TestCase
{
    private EntityRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new EntityRegistry([
            'stub' => [
                'class' => TrackedEntity::class,
                'label' => 'Stub',
                'name_field' => 'title',
            ],
        ]);
    }

    public function testConfiguredClassResolves(): void
    {
        $entity = $this->registry->resolve(TrackedEntity::class);

        self::assertNotNull($entity);
        self::assertSame('Stub', $entity['label']);
        self::assertSame('title', $entity['name_field']);
    }

    public function testSubclassResolvesThroughItsAncestors(): void
    {
        // What reaches an observer is almost never the configured class but a
        // generated ...\Interceptor subclass of it.
        self::assertNotNull($this->registry->resolveObject(new TrackedEntityChild()));
        self::assertTrue($this->registry->isTracked(new TrackedEntityChild()));
    }

    public function testUnknownClassIsNotTracked(): void
    {
        self::assertNull($this->registry->resolve(UntrackedEntity::class));
        self::assertFalse($this->registry->isTracked(new UntrackedEntity()));
    }

    public function testLeadingSlashesAndCaseDoNotDefeatTheLookup(): void
    {
        self::assertNotNull($this->registry->resolve('\\' . TrackedEntity::class));
        self::assertNotNull($this->registry->resolve(strtolower(TrackedEntity::class)));
    }

    public function testAllowlistAcceptsOnlyTheExactConfiguredClass(): void
    {
        self::assertSame(TrackedEntity::class, $this->registry->getAllowedClass(TrackedEntity::class));
    }

    public function testAllowlistRejectsASubclass(): void
    {
        // Deliberately stricter than resolve(): entity_type is a class name read
        // back out of a database column, and instantiating whatever it happens
        // to name is exactly what this guards against.
        self::assertNull($this->registry->getAllowedClass(TrackedEntityChild::class));
    }

    public function testAllowlistRejectsAnUnlistedClass(): void
    {
        self::assertNull($this->registry->getAllowedClass(UntrackedEntity::class));
        self::assertNull($this->registry->getAllowedClass('Evil\\Payload'));
    }

    public function testEntryWithoutAClassIsIgnored(): void
    {
        $registry = new EntityRegistry(['broken' => ['label' => 'No class here']]);

        self::assertSame([], $registry->getLabels());
    }

    public function testLabelsFeedTheGridFilter(): void
    {
        self::assertSame([TrackedEntity::class => 'Stub'], $this->registry->getLabels());
    }
}
