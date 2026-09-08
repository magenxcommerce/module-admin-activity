<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

/**
 * Builds the field-level before/after rows for one entity change.
 *
 * Takes plain arrays rather than a DataObject so it carries no framework
 * dependency and can be unit-tested directly; the observers do the
 * getOrigData()/getData() extraction.
 */
class DiffBuilder
{
    public function __construct(
        private readonly FieldFilter $fieldFilter,
        private readonly ValueFormatter $valueFormatter
    ) {
    }

    /**
     * Comparison happens on the FORMATTED values, not the raw ones. Magento
     * hands back '1' where it stored 1 and '10.0000' where the form posted
     * '10', and a raw !== would report every one of those as a change - which
     * is how an audit log ends up with a hundred rows per save and nobody reads
     * it any more.
     *
     * A create is build($new, []) reversed - pass an empty $orig - and a delete
     * is build($existing, []), which yields old-value-only rows.
     *
     * @param array<string, mixed> $orig
     * @param array<string, mixed> $new
     * @return array<int, array{field_name: string, old_value: ?string, new_value: ?string}>
     */
    public function build(array $orig, array $new): array
    {
        $fields = array_keys($orig + $new);
        $changes = [];

        foreach ($fields as $field) {
            $field = (string) $field;
            if ($this->fieldFilter->isSkipped($field)) {
                continue;
            }

            $oldValue = $this->valueFormatter->format($orig[$field] ?? null);
            $newValue = $this->valueFormatter->format($new[$field] ?? null);

            if ($oldValue === $newValue) {
                continue;
            }

            $changes[] = [
                'field_name' => $field,
                'old_value' => $this->fieldFilter->mask($field, $oldValue),
                'new_value' => $this->fieldFilter->mask($field, $newValue),
            ];
        }

        return $changes;
    }
}
