<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Config\Source;

use Magenx\AdminActivity\Model\Activity\EntityRegistry;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Grid filter options for the entity_type column, straight from the tracked
 * entity map - so a project that adds an entity in di.xml gets the filter
 * option for free.
 */
class EntityType implements OptionSourceInterface
{
    public function __construct(private readonly EntityRegistry $registry)
    {
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->registry->getLabels() as $class => $label) {
            $options[] = ['value' => $class, 'label' => $label];
        }

        return $options;
    }
}
