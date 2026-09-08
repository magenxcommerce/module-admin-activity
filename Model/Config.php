<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Typed reader over magenx_admin_activity/**.
 *
 * Every getter is global. Unlike a storefront feature, an admin audit trail has
 * no meaningful store scope - the admin panel is not a store view - so the
 * system.xml fields are declared showInDefault only and read at default scope.
 */
class Config
{
    private const XML_ENABLED = 'magenx_admin_activity/general/enabled';
    private const XML_LOG_LOGIN = 'magenx_admin_activity/general/log_login';
    private const XML_LOG_PAGE_VISITS = 'magenx_admin_activity/general/log_page_visits';
    private const XML_CLEANUP_DAYS = 'magenx_admin_activity/general/cleanup_days';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED);
    }

    public function isLoginLogged(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_LOG_LOGIN);
    }

    /**
     * Off by default, and the system.xml comment says why: a row per admin page
     * view is a lot of rows for very little signal.
     */
    public function isPageVisitLogged(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_LOG_PAGE_VISITS);
    }

    /**
     * Days of history to keep. Zero (or a negative value someone typed into the
     * field) means "keep everything" and the cleanup job does nothing - the safe
     * reading, since the destructive interpretation of a misconfigured retention
     * window is deleting the whole log.
     */
    public function getCleanupDays(): int
    {
        return max(0, (int) $this->scopeConfig->getValue(self::XML_CLEANUP_DAYS));
    }
}
