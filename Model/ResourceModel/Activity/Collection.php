<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\ResourceModel\Activity;

use Magenx\AdminActivity\Model\Activity as Model;
use Magenx\AdminActivity\Model\ResourceModel\Activity as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'activity_id';

    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
