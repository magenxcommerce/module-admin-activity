<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/**
 * Validates the cleanup cron expression before it is persisted.
 *
 * The field feeds crontab.xml's <config_path>, so a malformed value is not this
 * module's problem alone: Magento\Cron\Model\Schedule::trySchedule() throws on
 * it while building the schedule for the WHOLE `default` group, and every other
 * module's jobs stop running with it. Refusing the value at save time is the
 * only point where a human is present to read the error.
 */
class CronSchedule extends Value
{
    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $expression = trim((string) $this->getValue());

        if ($expression === '') {
            throw new LocalizedException(
                __('The cleanup schedule cannot be empty. Use a cron expression such as 0 3 * * *.')
            );
        }

        $parts = preg_split('/\s+/', $expression) ?: [];
        // Magento's own parser accepts 5 or 6 fields (the 6th being seconds).
        if (count($parts) < 5 || count($parts) > 6) {
            throw new LocalizedException(
                __(
                    'The cleanup schedule must be a cron expression of 5 or 6 fields, e.g. 0 3 * * *. Got: %1',
                    $expression
                )
            );
        }

        foreach ($parts as $part) {
            if (!preg_match('/^[0-9*,\/\-A-Za-z]+$/', $part)) {
                throw new LocalizedException(
                    __('"%1" is not a valid cron field in the cleanup schedule.', $part)
                );
            }
        }

        $this->setValue($expression);

        return parent::beforeSave();
    }
}
