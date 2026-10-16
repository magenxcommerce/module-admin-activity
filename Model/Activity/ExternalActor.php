<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

/**
 * Who is acting when a change does not come from an admin session.
 *
 * An integration token or an OAuth connection calling another module (the MCP
 * server, for one) changes the same entities an admin does, and an audit trail
 * that only knows about the admin panel would miss exactly the changes nobody
 * clicked. The caller describes itself with one of these and runs its work
 * inside {@see ExternalActorScope}.
 */
class ExternalActor
{
    /**
     * @param int|null $userId the admin user, when the caller acts as one; null for an integration
     * @param string $username stored as-is, e.g. "alice" or "integration:Reporting"
     * @param string $source what kind of caller this is, e.g. "mcp"
     * @param string|null $connector the credential behind it: an integration name or an OAuth client
     * @param string|null $roleName the ACL role the change was authorised by
     * @param string $action what was invoked, stored as full_action_name, e.g. "mcp/update_product"
     */
    public function __construct(
        private readonly ?int $userId,
        private readonly string $username,
        private readonly string $source,
        private readonly ?string $connector,
        private readonly ?string $roleName,
        private readonly string $action
    ) {
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getConnector(): ?string
    {
        return $this->connector;
    }

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
