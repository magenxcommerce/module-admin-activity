<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Test\Unit;

use Magenx\AdminActivity\Model\Activity\Buffer;
use PHPUnit\Framework\TestCase;

/**
 * The buffer decides whether a recorded change survives the request, so its
 * flush semantics are worth pinning down: getting them wrong loses audit
 * records silently, which is the one failure this module cannot have.
 */
class BufferTest extends TestCase
{
    private Buffer $buffer;

    protected function setUp(): void
    {
        $this->buffer = new Buffer();
    }

    public function testEntriesAccumulateInOrder(): void
    {
        $this->buffer->add(['action_type' => 'edit']);
        $this->buffer->add(['action_type' => 'delete']);

        self::assertSame(
            [['action_type' => 'edit'], ['action_type' => 'delete']],
            $this->buffer->getEntries()
        );
    }

    public function testANewBufferIsEmptyAndUnflushed(): void
    {
        self::assertSame([], $this->buffer->getEntries());
        self::assertFalse($this->buffer->isFlushed());
    }

    public function testFlushingClearsTheEntriesAndSetsTheFlag(): void
    {
        $this->buffer->add(['action_type' => 'edit']);
        $this->buffer->markFlushed();

        self::assertSame([], $this->buffer->getEntries());
        self::assertTrue($this->buffer->isFlushed());
    }

    /**
     * The regression that matters: a controller calling _forward() dispatches
     * twice, so postdispatch fires twice. Anything saved by the second action
     * is buffered AFTER the first flush and must still be readable - clearing
     * the entries is what prevents the double write, not the flag.
     */
    public function testEntriesBufferedAfterAFlushAreStillVisible(): void
    {
        $this->buffer->add(['action_type' => 'edit']);
        $this->buffer->markFlushed();

        $this->buffer->add(['action_type' => 'delete']);

        self::assertSame([['action_type' => 'delete']], $this->buffer->getEntries());
        self::assertTrue($this->buffer->isFlushed());
    }
}
