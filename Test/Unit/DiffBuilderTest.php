<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Test\Unit;

use Magenx\AdminActivity\Model\Activity\DiffBuilder;
use Magenx\AdminActivity\Model\Activity\FieldFilter;
use Magenx\AdminActivity\Model\Activity\ValueFormatter;
use PHPUnit\Framework\TestCase;

class DiffBuilderTest extends TestCase
{
    private DiffBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new DiffBuilder(
            new FieldFilter(['password' => 'password'], [], ['updated_at' => 'updated_at']),
            new ValueFormatter()
        );
    }

    public function testOnlyChangedFieldsAreReported(): void
    {
        $changes = $this->builder->build(
            ['name' => 'Old', 'sku' => 'ABC'],
            ['name' => 'New', 'sku' => 'ABC']
        );

        self::assertSame(
            [['field_name' => 'name', 'old_value' => 'Old', 'new_value' => 'New']],
            $changes
        );
    }

    public function testLooseValuesAreNotReportedAsChanges(): void
    {
        // Magento hands back '1' where it stored 1 and '10.0000' where the form
        // posted 10. A raw !== flags every one of those, which is how an audit
        // log ends up with a hundred rows per save that nobody reads.
        $changes = $this->builder->build(['status' => 1, 'qty' => 10], ['status' => '1', 'qty' => '10']);

        self::assertSame([], $changes);
    }

    public function testSkippedFieldsNeverAppear(): void
    {
        $changes = $this->builder->build(['updated_at' => '2026-01-01'], ['updated_at' => '2026-01-02']);

        self::assertSame([], $changes);
    }

    public function testProtectedFieldIsListedWithMaskedValues(): void
    {
        $changes = $this->builder->build(['password' => 'old-hash'], ['password' => 'new-hash']);

        self::assertSame(
            [['field_name' => 'password', 'old_value' => FieldFilter::MASK, 'new_value' => FieldFilter::MASK]],
            $changes
        );
    }

    public function testCreateYieldsNewValuesOnly(): void
    {
        $changes = $this->builder->build([], ['name' => 'New Page']);

        self::assertSame(
            [['field_name' => 'name', 'old_value' => null, 'new_value' => 'New Page']],
            $changes
        );
    }

    public function testDeleteYieldsOldValuesOnly(): void
    {
        $changes = $this->builder->build(['name' => 'Doomed'], []);

        self::assertSame(
            [['field_name' => 'name', 'old_value' => 'Doomed', 'new_value' => null]],
            $changes
        );
    }

    public function testFieldAddedOnlyInTheNewDataIsReported(): void
    {
        $changes = $this->builder->build(['name' => 'Same'], ['name' => 'Same', 'meta_title' => 'Added']);

        self::assertSame(
            [['field_name' => 'meta_title', 'old_value' => null, 'new_value' => 'Added']],
            $changes
        );
    }
}
