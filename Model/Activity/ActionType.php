<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

/**
 * The closed set of values stored in magenx_admin_activity.action_type.
 *
 * Deliberately a plain constant holder rather than a PHP enum: the value is
 * written to and read back from a varchar column and is surfaced in a grid
 * filter, so it has to round-trip as a string without a backing-enum cast at
 * every boundary.
 */
final class ActionType
{
    public const ADD = 'add';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const VIEW = 'view';
    public const PRINT_ACTION = 'print';
    public const MASS_UPDATE = 'mass_update';
    public const LOGIN = 'login';
    public const LOGIN_FAILED = 'login_failed';
    public const LOGOUT = 'logout';
    public const PAGE_VISIT = 'page_visit';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';

    /**
     * @return array<string, string> value => untranslated label
     */
    public static function all(): array
    {
        return [
            self::ADD => 'Add',
            self::EDIT => 'Edit',
            self::DELETE => 'Delete',
            self::VIEW => 'View',
            self::PRINT_ACTION => 'Print',
            self::MASS_UPDATE => 'Mass Update',
            self::LOGIN => 'Login',
            self::LOGIN_FAILED => 'Login Failed',
            self::LOGOUT => 'Logout',
            self::PAGE_VISIT => 'Page Visit',
        ];
    }
}
