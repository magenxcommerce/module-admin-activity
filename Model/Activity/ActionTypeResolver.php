<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

/**
 * Maps a controller's full action name to an ActionType.
 *
 * Magento's getFullActionName() is lowercased "route_controller_action", so the
 * dispatch below works off the last underscore-separated segment. Matching on
 * the action name rather than on the HTTP verb is what separates a delete from
 * an edit: both arrive as POST.
 */
class ActionTypeResolver
{
    /**
     * @param bool $isNewEntity whether the entity being written had no id before the save
     */
    public function resolve(string $fullActionName, string $httpMethod = 'GET', bool $isNewEntity = false): string
    {
        $action = $this->actionSegment($fullActionName);

        // massDelete before the generic mass* rule, or a bulk delete would be
        // filed as an update and disappear from a "what was deleted" query.
        if (str_contains($action, 'massdelete')) {
            return ActionType::DELETE;
        }

        if (str_starts_with($action, 'mass')) {
            return ActionType::MASS_UPDATE;
        }

        if (str_contains($action, 'delete') || str_contains($action, 'remove')) {
            return ActionType::DELETE;
        }

        if (str_contains($action, 'print')) {
            return ActionType::PRINT_ACTION;
        }

        if ($this->isWrite($action)) {
            return $isNewEntity ? ActionType::ADD : ActionType::EDIT;
        }

        if ($this->isRead($action)) {
            return ActionType::VIEW;
        }

        return strtoupper($httpMethod) === 'GET' ? ActionType::VIEW : ActionType::EDIT;
    }

    private function actionSegment(string $fullActionName): string
    {
        $fullActionName = strtolower(trim($fullActionName));
        if ($fullActionName === '') {
            return '';
        }

        $segments = explode('_', $fullActionName);

        return (string) end($segments);
    }

    private function isWrite(string $action): bool
    {
        foreach (['save', 'new', 'create', 'inlineedit', 'update'] as $needle) {
            if (str_contains($action, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isRead(string $action): bool
    {
        foreach (['view', 'edit', 'index', 'grid', 'show'] as $needle) {
            if (str_contains($action, $needle)) {
                return true;
            }
        }

        return false;
    }
}
