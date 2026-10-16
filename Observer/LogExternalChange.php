<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Observer;

use Magenx\AdminActivity\Model\Activity\Buffer;
use Magenx\AdminActivity\Model\Activity\EntryBuilder;
use Magenx\AdminActivity\Model\Activity\ExternalActorScope;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Buffers a tracked entity's save or delete, but only inside an {@see ExternalActorScope}.
 *
 * Registered in etc/frontend/events.xml, where the MCP endpoint and other
 * token-authenticated controllers run. The scope check comes first and costs
 * one property read, so a storefront request - which is never inside a scope -
 * pays nothing and is never logged. It deliberately touches no admin session:
 * constructing one outside the admin area would start it.
 */
class LogExternalChange implements ObserverInterface
{
    public function __construct(
        private readonly ExternalActorScope $scope,
        private readonly EntryBuilder $entryBuilder,
        private readonly Buffer $buffer
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->scope->isActive()) {
            return;
        }

        $object = $observer->getEvent()->getObject();
        if (!$object instanceof AbstractModel) {
            return;
        }

        $entry = $observer->getEvent()->getName() === 'model_delete_commit_after'
            ? $this->entryBuilder->forDelete($object)
            : $this->entryBuilder->forSave($object);

        if ($entry !== null) {
            $this->buffer->add($entry);
        }
    }
}
