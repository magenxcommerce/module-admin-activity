<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Test\Unit;

use Magenx\AdminActivity\Model\Activity\Buffer;
use Magenx\AdminActivity\Model\Activity\ContextProvider;
use Magenx\AdminActivity\Model\Activity\ExternalActor;
use Magenx\AdminActivity\Model\Activity\ExternalActorScope;
use Magenx\AdminActivity\Model\Activity\Writer;
use Magenx\AdminActivity\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Changes made outside the admin panel are recorded under the caller that
 * made them, and only while that caller holds a scope open.
 */
class ExternalActorScopeTest extends TestCase
{
    private Buffer $buffer;
    private Writer&MockObject $writer;
    private ContextProvider&MockObject $contextProvider;

    protected function setUp(): void
    {
        $this->buffer = new Buffer();
        $this->writer = $this->createMock(Writer::class);
        $this->contextProvider = $this->createMock(ContextProvider::class);
        $this->contextProvider->method('get')->willReturnCallback(
            static fn (?int $userId, ?string $username): array => [
                'user_id' => $userId,
                'username' => $username,
                'role_name' => null,
                'full_action_name' => 'magenx_aimcp_server_index',
                'ip_address' => '203.0.113.7',
            ]
        );
    }

    public function testChangesInsideTheScopeAreWrittenUnderTheActor(): void
    {
        $this->writer->expects(self::once())->method('write')->with(
            self::callback(static function (array $context): bool {
                return $context['user_id'] === 7
                    && $context['username'] === 'alice'
                    && $context['source'] === 'mcp'
                    && $context['connector'] === 'Claude'
                    && $context['role_name'] === 'Catalog Managers'
                    && $context['full_action_name'] === 'mcp/update_product'
                    && $context['ip_address'] === '203.0.113.7';
            }),
            [['action_type' => 'edit', 'entity_id' => '42']]
        );

        $result = $this->scope()->run($this->actor(), function (): string {
            $this->buffer->add(['action_type' => 'edit', 'entity_id' => '42']);

            return 'done';
        });

        self::assertSame('done', $result);
        self::assertSame([], $this->buffer->getEntries());
    }

    /**
     * A tool that fails after committing something still changed the store.
     */
    public function testChangesCommittedBeforeAFailureAreStillWritten(): void
    {
        $this->writer->expects(self::once())->method('write');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('second step failed');

        $this->scope()->run($this->actor(), function (): void {
            $this->buffer->add(['action_type' => 'delete', 'entity_id' => '9']);

            throw new \RuntimeException('second step failed');
        });
    }

    public function testNothingIsWrittenWhenNothingChanged(): void
    {
        $this->writer->expects(self::never())->method('write');

        $this->scope()->run($this->actor(), static fn (): null => null);
    }

    public function testTheScopeIsActiveOnlyWhileRunning(): void
    {
        $scope = $this->scope();
        $seen = $scope->run($this->actor(), static fn (): bool => $scope->isActive());

        self::assertTrue($seen);
        self::assertFalse($scope->isActive());
    }

    /**
     * Entries the request buffered before the scope belong to the request, not to the actor.
     */
    public function testEarlierEntriesAreLeftForTheRequest(): void
    {
        $this->buffer->add(['entity_id' => 'before']);
        $this->writer->expects(self::once())->method('write')->with(self::anything(), [['entity_id' => 'inside']]);

        $this->scope()->run($this->actor(), fn () => $this->buffer->add(['entity_id' => 'inside']));

        self::assertSame([['entity_id' => 'before']], $this->buffer->getEntries());
    }

    public function testDisabledLoggingRunsTheWorkAndRecordsNothing(): void
    {
        $this->writer->expects(self::never())->method('write');
        $scope = $this->scope(enabled: false);

        $active = $scope->run($this->actor(), static fn (): bool => $scope->isActive());

        self::assertFalse($active);
    }

    private function scope(bool $enabled = true): ExternalActorScope
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn($enabled);

        return new ExternalActorScope($config, $this->buffer, $this->writer, $this->contextProvider);
    }

    private function actor(): ExternalActor
    {
        return new ExternalActor(7, 'alice', 'mcp', 'Claude', 'Catalog Managers', 'mcp/update_product');
    }
}
