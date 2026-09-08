<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Test\Unit\Stub;

/**
 * Stands in for the generated ...\Interceptor subclass Magento actually hands an
 * observer. A real subclass, not a mock: what the registry tests exercise is the
 * class_parents() walk, which a test double cannot stand in for.
 */
class TrackedEntityChild extends TrackedEntity
{
}
