<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Observer;

use Magenx\AdminActivity\Model\Activity\ActionType;
use Magenx\AdminActivity\Model\Activity\Buffer;
use Magenx\AdminActivity\Model\Activity\ContextProvider;
use Magenx\AdminActivity\Model\Activity\Writer;
use Magenx\AdminActivity\Model\Config;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Records a logout.
 *
 * Bound to controller_action_predispatch_adminhtml_auth_logout because
 * Auth\Session::processLogout() dispatches no event of its own and destroys the
 * session outright - by postdispatch there is no user left to name.
 */
class LogLogout implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Session $authSession,
        private readonly Writer $writer,
        private readonly ContextProvider $contextProvider,
        private readonly Buffer $buffer
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isLoginLogged() || !$this->authSession->isLoggedIn()) {
            return;
        }

        $user = $this->authSession->getUser();
        if ($user === null) {
            return;
        }

        $this->writer->write(
            $this->contextProvider->get((int) $user->getId(), (string) $user->getUserName()),
            [[
                'action_type' => ActionType::LOGOUT,
                'status' => ActionType::STATUS_SUCCESS,
            ]]
        );

        // Stops the postdispatch observer from filing the logout screen as a
        // page visit on top of the logout it just recorded.
        $this->buffer->markFlushed();
    }
}
