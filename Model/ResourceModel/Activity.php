<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Activity extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('magenx_admin_activity', 'activity_id');
    }

    /**
     * Deletes expired rows in bounded batches.
     *
     * A single DELETE ... WHERE created_at < ? is one statement but an unbounded
     * one: on an install where the cron has not run for months it locks the
     * table for the length of the delete and can outgrow the binlog transaction
     * limits. Batching keeps each statement short; the detail rows follow via
     * the CASCADE foreign key, so this touches only the parent table.
     *
     * @return int number of rows removed
     */
    public function deleteOlderThan(string $cutoff, int $batchSize = 5000): int
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();
        $deleted = 0;

        do {
            $select = $connection->select()
                ->from($table, 'activity_id')
                ->where('created_at < ?', $cutoff)
                ->limit($batchSize);

            $ids = $connection->fetchCol($select);
            if ($ids === []) {
                break;
            }

            $deleted += $connection->delete($table, ['activity_id IN (?)' => $ids]);
        } while (count($ids) === $batchSize);

        return $deleted;
    }
}
