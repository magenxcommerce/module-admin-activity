<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model;

use Magenx\AdminActivity\Model\ResourceModel\Activity as ActivityResource;
use Magento\Framework\Model\AbstractModel;

class Activity extends AbstractModel
{
    protected $_eventPrefix = 'magenx_admin_activity';

    protected function _construct(): void
    {
        $this->_init(ActivityResource::class);
        $this->setIdFieldName('activity_id');
    }
}
