<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

/**
 * Request-scoped accumulator, shared by the observers within one dispatch.
 *
 * Entity changes are collected as they happen (the model commit events fire
 * deep inside the controller) and written once at
 * controller_action_postdispatch.
 * Writing them as they arrive would mean an INSERT in the middle of the admin's
 * own transaction - rolled back with it when the save later fails, which is
 * exactly the event worth keeping - and one INSERT per entity for a mass
 * action instead of one batch.
 */
class Buffer
{
    /** @var array<int, array<string, mixed>> */
    private array $entries = [];

    private bool $flushed = false;

    /**
     * @param array<string, mixed> $entry
     */
    public function add(array $entry): void
    {
        $this->entries[] = $entry;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Clearing the entries is what stops a double write; the flag records only
     * that this request has already produced a row, which is what suppresses
     * the page-visit fallback for the rest of it.
     */
    public function markFlushed(): void
    {
        $this->flushed = true;
        $this->entries = [];
    }

    public function isFlushed(): bool
    {
        return $this->flushed;
    }
}
