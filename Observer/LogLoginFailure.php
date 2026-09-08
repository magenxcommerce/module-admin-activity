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

/**
 * Records a failed admin login attempt.
 *
 * The username is stored but user_id stays null: the attempt may name an
 * account that does not exist, which is itself worth seeing in the log. Only
 * the exception MESSAGE is kept - Magento's authentication messages are
 * deliberately generic, and storing anything the attempt submitted would put
 * guessed passwords in the database.
 */
class LogLoginFailure implements ObserverInterface
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

        $event = $observer->getEvent();
        $username = (string) $event->getUserName();
        $exception = $event->getException();

        $this->writer->write(
            $this->contextProvider->get(null, $username !== '' ? $username : null),
            [[
                'action_type' => ActionType::LOGIN_FAILED,
                'status' => ActionType::STATUS_FAILURE,
                'message' => $exception instanceof \Throwable ? $exception->getMessage() : null,
            ]]
        );
    }
}
