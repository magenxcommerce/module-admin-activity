<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Config\Source;

use Magenx\AdminActivity\Model\Activity\ActionType as ActionTypeList;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Grid filter options for the action_type column.
 */
class ActionType implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (ActionTypeList::all() as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }

        return $options;
    }
}
