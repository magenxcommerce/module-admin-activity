<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Test\Unit;

use Magenx\AdminActivity\Model\Activity\ValueFormatter;
use PHPUnit\Framework\TestCase;

class ValueFormatterTest extends TestCase
{
    private ValueFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ValueFormatter();
    }

    public function testNullStaysNull(): void
    {
        // "Had no value" is a real state and must not collapse to an empty
        // string, or every nullable field reads as changed on first write.
        self::assertNull($this->formatter->format(null));
    }

    public function testScalarsBecomeStrings(): void
    {
        self::assertSame('42', $this->formatter->format(42));
        self::assertSame('4.5', $this->formatter->format(4.5));
        self::assertSame('sku-1', $this->formatter->format('sku-1'));
    }

    public function testBooleansBecomeDatabaseShapedFlags(): void
    {
        self::assertSame('1', $this->formatter->format(true));
        self::assertSame('0', $this->formatter->format(false));
    }

    public function testArraysBecomeJson(): void
    {
        self::assertSame('{"a":1,"b":[2,3]}', $this->formatter->format(['a' => 1, 'b' => [2, 3]]));
    }

    public function testUnencodableValueRecordsItsShapeInsteadOfFailing(): void
    {
        $resource = fopen('php://memory', 'rb');
        self::assertSame('[resource]', $this->formatter->format($resource));
        fclose($resource);
    }

    public function testLongValueIsTruncatedWithinTheByteBudget(): void
    {
        $formatter = new ValueFormatter(64);

        $result = $formatter->format(str_repeat('x', 500));

        self::assertNotNull($result);
        self::assertLessThanOrEqual(64, strlen($result));
        self::assertStringEndsWith(ValueFormatter::TRUNCATION_MARKER, $result);
    }

    public function testTruncationDoesNotSplitAMultibyteCharacter(): void
    {
        // A cut mid-character produces invalid UTF-8, which MySQL rejects
        // outright - the insert fails and the audit record is lost.
        $formatter = new ValueFormatter(40);

        $result = (string) $formatter->format(str_repeat('é', 100));

        self::assertLessThanOrEqual(40, strlen($result));
        self::assertSame($result, mb_convert_encoding($result, 'UTF-8', 'UTF-8'));
    }

    public function testShortValueIsUntouched(): void
    {
        self::assertSame('short', $this->formatter->format('short'));
    }
}
