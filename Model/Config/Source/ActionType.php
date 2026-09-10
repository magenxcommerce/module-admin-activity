<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Config\Source;

use Magenx\AdminActivity\Model\Activity\ActionType as ActionTypeValue;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

/**
 * Action type labels: the grid filter's options, and the detail page's label
 * lookup.
 *
 * The map lives here rather than beside the constants because this is an
 * injectable service - a plugin can extend the labels, which a static accessor
 * on the constant holder could not offer.
 */
class ActionType implements OptionSourceInterface
{
    private const LABELS = [
        ActionTypeValue::ADD => 'Add',
        ActionTypeValue::EDIT => 'Edit',
        ActionTypeValue::DELETE => 'Delete',
        ActionTypeValue::VIEW => 'View',
        ActionTypeValue::PRINT_ACTION => 'Print',
        ActionTypeValue::MASS_UPDATE => 'Mass Update',
        ActionTypeValue::LOGIN => 'Login',
        ActionTypeValue::LOGIN_FAILED => 'Login Failed',
        ActionTypeValue::LOGOUT => 'Logout',
        ActionTypeValue::PAGE_VISIT => 'Page Visit',
    ];

    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::LABELS as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }

        return $options;
    }

    /**
     * Falls back to the raw stored value: a row written by an older version of
     * the module, or by a project that added its own action type, is still worth
     * showing rather than rendering as a blank cell.
     *
     * Returns a Phrase rather than a string so the translation happens here,
     * against a literal the i18n collector can actually see. Handing back an
     * untranslated string for the template to wrap in __() is a
     * translate-by-variable that bin/magento i18n:collect-phrases walks past.
     */
    public function getLabel(string $actionType): Phrase
    {
        $label = self::LABELS[$actionType] ?? null;

        return $label === null ? __($actionType) : __($label);
    }
}
