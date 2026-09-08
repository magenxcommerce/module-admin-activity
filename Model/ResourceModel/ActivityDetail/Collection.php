<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\ResourceModel\ActivityDetail;

use Magenx\AdminActivity\Model\ActivityDetail as Model;
use Magenx\AdminActivity\Model\ResourceModel\ActivityDetail as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'detail_id';

    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }

    /**
     * @return $this
     */
    public function addActivityFilter(int $activityId): self
    {
        $this->addFieldToFilter('activity_id', $activityId);

        return $this;
    }
}
