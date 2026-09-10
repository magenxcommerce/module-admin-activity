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
 *
 * The exact-name lists are held as hash maps rather than lists: isSkipped() and
 * isProtected() run once per field per entity, which on a capped mass action is
 * hundreds of fields times two hundred entities, and a linear in_array() over
 * every configured name is the wrong shape for that.
 */
class FieldFilter
{
    public const MASK = '***';

    /** @var array<string, true> */
    private array $protectedFields;

    /** @var array<int, string> */
    private array $protectedPatterns;

    /** @var array<string, true> */
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
        $this->protectedFields = array_fill_keys($this->normalize($protectedFields), true);
        $this->protectedPatterns = $this->normalize($protectedPatterns);
        $this->skipFields = array_fill_keys($this->normalize($skipFields), true);
    }

    public function isSkipped(string $field): bool
    {
        return isset($this->skipFields[strtolower($field)]);
    }

    /**
     * Also takes a store-config path (payment/foo/api_key): the pattern list is
     * substring-matched, so a path names its own sensitivity even though the
     * column holding the secret is called `value`. EntryBuilder relies on that.
     */
    public function isProtected(string $field): bool
    {
        $field = strtolower($field);

        if (isset($this->protectedFields[$field])) {
            return true;
        }

        foreach ($this->protectedPatterns as $pattern) {
            if (str_contains($field, $pattern)) {
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
