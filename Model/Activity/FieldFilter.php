<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

/**
 * Decides which fields are logged and which values are masked.
 *
 * Three lists, all supplied from etc/di.xml so a project can extend them
 * without touching code:
 *
 *  - skipFields      dropped entirely (noise: timestamps, denormalised counters)
 *  - protectedFields exact names whose VALUES are never stored
 *  - protectedPatterns substrings that mark a field protected (case-insensitive)
 *
 * A protected field keeps its NAME in the log and has both values replaced with
 * ***. Dropping it outright would hide the single most security-relevant fact
 * an audit log can carry - that someone changed a password or rotated an API
 * key - while storing the value would defeat the point of protecting it.
 */
class FieldFilter
{
    public const MASK = '***';

    /** @var array<int, string> */
    private array $protectedFields;

    /** @var array<int, string> */
    private array $protectedPatterns;

    /** @var array<int, string> */
    private array $skipFields;

    /**
     * @param array<string, string> $protectedFields
     * @param array<string, string> $protectedPatterns
     * @param array<string, string> $skipFields
     */
    public function __construct(
        array $protectedFields = [],
        array $protectedPatterns = [],
        array $skipFields = []
    ) {
        $this->protectedFields = $this->normalize($protectedFields);
        $this->protectedPatterns = $this->normalize($protectedPatterns);
        $this->skipFields = $this->normalize($skipFields);
    }

    public function isSkipped(string $field): bool
    {
        return in_array(strtolower($field), $this->skipFields, true);
    }

    public function isProtected(string $field): bool
    {
        $field = strtolower($field);

        if (in_array($field, $this->protectedFields, true)) {
            return true;
        }

        foreach ($this->protectedPatterns as $pattern) {
            if ($pattern !== '' && str_contains($field, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A null stays null even for a protected field: "was empty, now set" is
     * safe to record and is often the only useful thing about the change.
     */
    public function mask(string $field, ?string $value): ?string
    {
        if ($value === null || !$this->isProtected($field)) {
            return $value;
        }

        return self::MASK;
    }

    /**
     * @param array<string, string> $values
     * @return array<int, string>
     */
    private function normalize(array $values): array
    {
        $normalized = [];
        foreach ($values as $value) {
            $value = strtolower(trim((string) $value));
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }
}
