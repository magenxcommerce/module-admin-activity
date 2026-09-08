<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model;

use Magenx\AdminActivity\Model\ResourceModel\ActivityDetail as ActivityDetailResource;
use Magento\Framework\Model\AbstractModel;

class ActivityDetail extends AbstractModel
{
    protected $_eventPrefix = 'magenx_admin_activity_detail';

    protected function _construct(): void
    {
        $this->_init(ActivityDetailResource::class);
        $this->setIdFieldName('detail_id');
    }
}
