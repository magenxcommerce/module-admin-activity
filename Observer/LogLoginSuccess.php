<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Observer;

use Magenx\AdminActivity\Model\Activity\ActionType;
use Magenx\AdminActivity\Model\Activity\ContextProvider;
use Magenx\AdminActivity\Model\Activity\Writer;
use Magenx\AdminActivity\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\User\Model\User;

/**
 * Records a successful admin login.
 *
 * Listens to backend_auth_user_login_success rather than
 * admin_user_authenticate_after: the latter also fires from
 * User::performIdentityCheck(), the re-authentication prompt in front of
 * sensitive screens, which would log a second "login" for what is one session.
 *
 * Written immediately instead of buffered - the login request's postdispatch is
 * a redirect, and a session that dies before it would lose the record.
 */
class LogLoginSuccess implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Writer $writer,
        private readonly ContextProvider $contextProvider
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isLoginLogged()) {
            return;
        }

        $user = $observer->getEvent()->getUser();
        if (!$user instanceof User) {
            return;
        }

        $this->writer->write(
            $this->contextProvider->get((int) $user->getId(), (string) $user->getUserName()),
            [[
                'action_type' => ActionType::LOGIN,
                'status' => ActionType::STATUS_SUCCESS,
            ]]
        );
    }
}
