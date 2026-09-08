<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * The only class here that touches the database.
 *
 * Every write is wrapped: an audit log that can abort the action it is auditing
 * is worse than no audit log, so a failure is logged to
 * var/log/magenx_admin_activity.log and swallowed.
 */
class Writer
{
    public const TABLE = 'magenx_admin_activity';
    public const DETAIL_TABLE = 'magenx_admin_activity_detail';

    /**
     * Column budgets. Values are clipped rather than left to MySQL, which in
     * strict mode rejects the row outright and in non-strict mode truncates
     * silently - neither of which an observer can do anything useful about.
     */
    private const LENGTHS = [
        'username' => 64,
        'action_type' => 32,
        'status' => 16,
        'entity_type' => 255,
        'entity_label' => 255,
        'entity_id' => 64,
        'entity_name' => 255,
        'full_action_name' => 255,
        'request_url' => 2048,
        'ip_address' => 45,
        'user_agent' => 512,
    ];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger,
        private readonly int $maxEntitiesPerRequest = 200
    ) {
    }

    /**
     * @param array<string, mixed> $context
     * @param array<int, array<string, mixed>> $entries
     */
    public function write(array $context, array $entries): void
    {
        if ($entries === []) {
            return;
        }

        // A "select all" mass action can carry tens of thousands of entities.
        // Past the cap the log records one summary row instead, so a single
        // click cannot multiply into a five-figure insert.
        if (count($entries) > $this->maxEntitiesPerRequest) {
            $entries = [$this->summarize($entries)];
        }

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName(self::TABLE);
            $detailTable = $this->resource->getTableName(self::DETAIL_TABLE);

            foreach ($entries as $entry) {
                $connection->insert($table, $this->buildRow($context, $entry));

                /** @var array<int, array{field_name: string, old_value: ?string, new_value: ?string}> $changes */
                $changes = $entry['changes'] ?? [];
                if ($changes === []) {
                    continue;
                }

                $activityId = (int) $connection->lastInsertId($table);
                $detailRows = [];
                foreach ($changes as $change) {
                    $detailRows[] = [
                        'activity_id' => $activityId,
                        'field_name' => $this->clip((string) $change['field_name'], 255),
                        'old_value' => $change['old_value'] ?? null,
                        'new_value' => $change['new_value'] ?? null,
                    ];
                }

                // One statement for the whole entity, however many fields moved.
                $connection->insertMultiple($detailTable, $detailRows);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Could not record admin activity: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function buildRow(array $context, array $entry): array
    {
        $row = [
            'user_id' => $this->nullableInt($context['user_id'] ?? null),
            'username' => $context['username'] ?? null,
            'action_type' => (string) ($entry['action_type'] ?? ActionType::VIEW),
            'status' => (string) ($entry['status'] ?? ActionType::STATUS_SUCCESS),
            'entity_type' => $entry['entity_type'] ?? null,
            'entity_label' => $entry['entity_label'] ?? null,
            'entity_id' => isset($entry['entity_id']) ? (string) $entry['entity_id'] : null,
            'entity_name' => $entry['entity_name'] ?? null,
            'full_action_name' => $context['full_action_name'] ?? null,
            'request_url' => $context['request_url'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'message' => $entry['message'] ?? null,
        ];

        foreach (self::LENGTHS as $column => $length) {
            if ($row[$column] !== null) {
                $row[$column] = $this->clip((string) $row[$column], $length);
            }
        }

        return $row;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array<string, mixed>
     */
    private function summarize(array $entries): array
    {
        $first = $entries[0];

        return [
            'action_type' => ActionType::MASS_UPDATE,
            'status' => ActionType::STATUS_SUCCESS,
            'entity_type' => $first['entity_type'] ?? null,
            'entity_label' => $first['entity_label'] ?? null,
            'message' => sprintf(
                '%d records affected. Per-field detail was not recorded: the action exceeded the %d record limit.',
                count($entries),
                $this->maxEntitiesPerRequest
            ),
            'changes' => [],
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }

    private function clip(string $value, int $length): string
    {
        return strlen($value) <= $length ? $value : mb_strcut($value, 0, $length);
    }
}
