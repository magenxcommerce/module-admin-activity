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
 * Records deletions, including the entity's final state.
 *
 * Bound to model_delete_commit_after for the same reason as the save observer:
 * only a committed delete is a delete.
 */
class LogModelDelete implements ObserverInterface
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
        if (!$object instanceof AbstractModel || !$this->authSession->isLoggedIn()) {
            return;
        }

        $entry = $this->entryBuilder->forDelete($object);
        if ($entry !== null) {
            $this->buffer->add($entry);
        }
    }
}
