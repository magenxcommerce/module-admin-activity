<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

/**
 * Assembles the who/where half of a log row: admin user, IP, user agent, URL.
 */
class ContextProvider
{
    public function __construct(
        private readonly Http $request,
        private readonly RemoteAddress $remoteAddress,
        private readonly Session $authSession
    ) {
    }

    /**
     * The login observers pass the user explicitly: at the moment
     * backend_auth_user_login_success fires the session is mid-handshake, and on
     * a failed login there is no session user at all.
     *
     * @return array<string, mixed>
     */
    public function get(?int $userId = null, ?string $username = null): array
    {
        if ($userId === null && $username === null) {
            $user = $this->authSession->getUser();
            if ($user !== null) {
                $userId = (int) $user->getId();
                $username = (string) $user->getUserName();
            }
        }

        return [
            'user_id' => $userId,
            'username' => $username,
            'full_action_name' => $this->request->getFullActionName(),
            'request_url' => $this->sanitizeUrl((string) $this->request->getUriString()),
            // RemoteAddress, not $_SERVER['REMOTE_ADDR']: it honours the store's
            // trusted-proxy configuration, so an install behind a load balancer
            // logs the real client address instead of the balancer's.
            'ip_address' => $this->remoteAddress->getRemoteAddress() ?: null,
            'user_agent' => $this->request->getHeader('User-Agent') ?: null,
        ];
    }

    /**
     * Strips the admin secret key out of the stored URL.
     *
     * Every admin URL carries /key/<hash>/, which is a per-session CSRF token.
     * Persisting it would put a live token in a table that a reporting user or
     * a database export can read, for no gain - the route is already recorded
     * in full_action_name.
     */
    private function sanitizeUrl(string $url): string
    {
        return (string) preg_replace('#/key/[^/]+/?#i', '/', $url);
    }
}
