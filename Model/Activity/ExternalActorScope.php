<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

use Magenx\AdminActivity\Model\Config;

/**
 * Records the changes made by a block of code on behalf of an external actor.
 *
 * Usage, from another module:
 *
 *     $result = $scope->run($actor, fn () => $this->doTheWrite());
 *
 * While the callable runs, the frontend-area observer
 * ({@see \Magenx\AdminActivity\Observer\LogExternalChange}) buffers every
 * tracked entity that commits, exactly as the admin observers do. When it
 * returns - or throws, because a change committed before the failure still
 * happened - those entries are written at once with the actor as the user.
 * There is no controller postdispatch to wait for: the caller may not be a
 * controller at all, and a request may run several scopes.
 *
 * Outside a scope nothing is recorded, which is what lets the observer live in
 * the frontend area without logging a single storefront save.
 */
class ExternalActorScope
{
    private ?ExternalActor $actor = null;

    public function __construct(
        private readonly Config $config,
        private readonly Buffer $buffer,
        private readonly Writer $writer,
        private readonly ContextProvider $contextProvider
    ) {
    }

    /**
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function run(ExternalActor $actor, callable $work): mixed
    {
        if (!$this->config->isEnabled()) {
            return $work();
        }

        $previous = $this->actor;
        $offset = $this->buffer->count();
        $this->actor = $actor;

        try {
            return $work();
        } finally {
            $this->actor = $previous;
            $entries = $this->buffer->drainFrom($offset);
            if ($entries !== []) {
                $this->writer->write($this->context($actor), $entries);
            }
        }
    }

    public function isActive(): bool
    {
        return $this->actor !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(ExternalActor $actor): array
    {
        // A username is always passed, so the provider never falls back to
        // the admin session - which, outside the admin area, it must not start.
        return [
            'full_action_name' => $actor->getAction(),
            'source' => $actor->getSource(),
            'connector' => $actor->getConnector(),
            'role_name' => $actor->getRoleName(),
        ] + $this->contextProvider->get($actor->getUserId(), $actor->getUsername());
    }
}
