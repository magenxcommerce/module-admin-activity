<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Config\Source;

use Magenx\AdminActivity\Model\Activity\ActionType;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Grid filter options for the status column.
 */
class Status implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => ActionType::STATUS_SUCCESS, 'label' => __('Success')],
            ['value' => ActionType::STATUS_FAILURE, 'label' => __('Failure')],
        ];
    }
}
