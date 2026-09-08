<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Observer;

use Magenx\AdminActivity\Model\Activity\Buffer;
use Magenx\AdminActivity\Model\Activity\EntryBuilder;
use Magenx\AdminActivity\Model\Config;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Records what an admin changed, field by field.
 *
 * Bound to model_save_commit_after, not model_save_after: the plain event fires
 * inside the still-open transaction, and a save rolled back afterwards would
 * leave a log entry for a change that never landed.
 *
 * Registered in etc/adminhtml/events.xml, never in a global one: the model events
 * fire in every area, and an area-scoped registration is what keeps storefront
 * checkouts and cron jobs out of the admin activity log without an App\State
 * lookup on the hot path.
 */
class LogModelSave implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Session $authSession,
        private readonly EntryBuilder $entryBuilder,
        private readonly Buffer $buffer
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $object = $observer->getEvent()->getObject();
        if (!$object instanceof AbstractModel) {
            return;
        }

        // No admin, no admin activity. This also excludes the login handshake's
        // own writes to admin_user, which the login observers record properly.
        if (!$this->authSession->isLoggedIn()) {
            return;
        }

        $entry = $this->entryBuilder->forSave($object);
        if ($entry !== null) {
            $this->buffer->add($entry);
        }
    }
}
