<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ActivityDetail extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('magenx_admin_activity_detail', 'detail_id');
    }
}
