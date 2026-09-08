<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Cron;

use Magenx\AdminActivity\Model\Config;
use Magenx\AdminActivity\Model\ResourceModel\Activity as ActivityResource;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Applies the retention window.
 *
 * Deletes parent rows only; magenx_admin_activity_detail follows through its
 * CASCADE foreign key.
 */
class CleanActivityLogs
{
    public function __construct(
        private readonly Config $config,
        private readonly ActivityResource $activityResource,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $days = $this->config->getCleanupDays();
        if (!$this->config->isEnabled() || $days <= 0) {
            return;
        }

        // gmtDate, because created_at is a MySQL TIMESTAMP and Magento stores
        // and compares those in UTC regardless of the store's locale timezone.
        $cutoff = $this->dateTime->gmtDate('Y-m-d H:i:s', $this->dateTime->gmtTimestamp() - ($days * 86400));

        try {
            $deleted = $this->activityResource->deleteOlderThan($cutoff);
            if ($deleted > 0) {
                $this->logger->info(sprintf('Removed %d admin activity record(s) older than %s.', $deleted, $cutoff));
            }
        } catch (\Throwable $e) {
            $this->logger->error('Admin activity cleanup failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
