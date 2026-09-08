<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

/**
 * Turns an arbitrary model value into a bounded, storable string.
 *
 * Imports nothing from magento/framework on purpose, so it is unit-testable
 * against a bare PHPUnit.
 */
class ValueFormatter
{
    /**
     * 128 KB. Serialized product data (custom options, tier prices, a full
     * description) routinely runs to tens of kilobytes, and a handful of those
     * per save is what turns an audit log into the biggest table in the
     * database. The ceiling is above text's 64 KB limit, hence the mediumtext
     * columns in db_schema.xml.
     */
    public const MAX_LENGTH = 131072;

    public const TRUNCATION_MARKER = '... [truncated]';

    public function __construct(private readonly int $maxLength = self::MAX_LENGTH)
    {
    }

    /**
     * Null in, null out: a null is a real state ("this field had no value") and
     * must not collapse to an empty string, or every nullable field would read
     * as changed the first time it is written.
     */
    public function format(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->truncate($this->stringify($value));
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if (is_array($value) || $value instanceof \JsonSerializable) {
            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                return $encoded;
            }
        }

        // Recursive structures and resources reach here. Record the shape rather
        // than failing the save that triggered the logging.
        return is_object($value) ? '[' . $value::class . ']' : '[' . gettype($value) . ']';
    }

    /**
     * Cuts on a byte budget but never mid-character: mb_strcut backs up to the
     * previous character boundary, so the stored value stays valid UTF-8 and
     * MySQL does not reject the insert.
     */
    private function truncate(string $value): string
    {
        if (strlen($value) <= $this->maxLength) {
            return $value;
        }

        $budget = $this->maxLength - strlen(self::TRUNCATION_MARKER);
        if ($budget < 1) {
            return substr(self::TRUNCATION_MARKER, 0, $this->maxLength);
        }

        return mb_strcut($value, 0, $budget) . self::TRUNCATION_MARKER;
    }
}
