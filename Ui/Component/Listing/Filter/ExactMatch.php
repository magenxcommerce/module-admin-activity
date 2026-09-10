<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Ui\Component\Listing\Filter;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;

/**
 * Turns a grid text filter into an equality match.
 *
 * The default strategy for a text column is LIKE '%value%', which no index can
 * serve. On ip_address that matters: the column carries a btree index whose
 * only purpose is answering "everything that came from this address", and a
 * leading-wildcard LIKE makes it a full scan of the whole log instead.
 *
 * Wired per column through the DataProvider's addFilterStrategies argument, so
 * every other column keeps its substring behaviour.
 */
class ExactMatch implements AddFilterToCollectionInterface
{
    /**
     * @param Collection $collection
     * @param string $field
     * @param array<string, mixed>|null $condition as built by the UI DataProvider: [conditionType => value]
     * @return void
     */
    public function addFilter(Collection $collection, $field, $condition = null): void
    {
        if (!is_array($condition) || $condition === []) {
            return;
        }

        $value = reset($condition);
        if (!is_scalar($value)) {
            return;
        }

        $value = $this->unwrap((string) $value);
        if ($value === '') {
            return;
        }

        $collection->addFieldToFilter($field, ['eq' => $value]);
    }

    /**
     * Magento\Ui\Component\Filters\Type\Input wraps the typed value in % before
     * the filter ever reaches a strategy, so the wrapping has to come back off
     * here. One character from each end, not trim('%'), so a value that
     * legitimately ends in a percent sign survives.
     */
    private function unwrap(string $value): string
    {
        if (str_starts_with($value, '%')) {
            $value = substr($value, 1);
        }

        if (str_ends_with($value, '%')) {
            $value = substr($value, 0, -1);
        }

        return trim($value);
    }
}
