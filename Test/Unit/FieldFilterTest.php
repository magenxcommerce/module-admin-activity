<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Test\Unit;

use Magenx\AdminActivity\Model\Activity\FieldFilter;
use PHPUnit\Framework\TestCase;

class FieldFilterTest extends TestCase
{
    private FieldFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new FieldFilter(
            ['password' => 'password', 'cc_cid' => 'cc_cid'],
            ['token' => 'token', 'secret' => 'secret'],
            ['updated_at' => 'updated_at']
        );
    }

    public function testSkippedFieldIsRecognised(): void
    {
        self::assertTrue($this->filter->isSkipped('updated_at'));
        self::assertFalse($this->filter->isSkipped('name'));
    }

    public function testExactProtectedFieldIsRecognised(): void
    {
        self::assertTrue($this->filter->isProtected('password'));
        self::assertTrue($this->filter->isProtected('cc_cid'));
    }

    public function testPatternCatchesTheLongTail(): void
    {
        self::assertTrue($this->filter->isProtected('braintree_token'));
        self::assertTrue($this->filter->isProtected('stripe_secret_key'));
        self::assertFalse($this->filter->isProtected('name'));
    }

    public function testMatchingIsCaseInsensitive(): void
    {
        self::assertTrue($this->filter->isProtected('PASSWORD'));
        self::assertTrue($this->filter->isProtected('OAuth_Access_TOKEN'));
        self::assertTrue($this->filter->isSkipped('Updated_At'));
    }

    public function testProtectedValueIsMaskedButTheFieldSurvives(): void
    {
        // The field name staying in the log is the point: an audit trail has to
        // show THAT a password changed without storing what it changed to.
        self::assertSame(FieldFilter::MASK, $this->filter->mask('password', 'hunter2'));
    }

    public function testUnprotectedValuePassesThrough(): void
    {
        self::assertSame('Blue Shirt', $this->filter->mask('name', 'Blue Shirt'));
    }

    public function testNullStaysNullEvenWhenProtected(): void
    {
        // "Was empty, now set" is safe to record and is often the only useful
        // thing about a credential change.
        self::assertNull($this->filter->mask('password', null));
    }

    public function testEmptyConfigurationProtectsNothing(): void
    {
        $filter = new FieldFilter();

        self::assertFalse($filter->isProtected('password'));
        self::assertFalse($filter->isSkipped('updated_at'));
    }
}
