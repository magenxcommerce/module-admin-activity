<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Observer;

use Magenx\AdminActivity\Model\Activity\ActionType;
use Magenx\AdminActivity\Model\Activity\Buffer;
use Magenx\AdminActivity\Model\Activity\ContextProvider;
use Magenx\AdminActivity\Model\Activity\Writer;
use Magenx\AdminActivity\Model\Config;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Writes everything the request buffered, once, at postdispatch.
 *
 * Also the page-visit path: a request that changed nothing is a page view, and
 * gets a single row when that (off-by-default) setting is on.
 */
class FlushActivity implements ObserverInterface
{
    /**
     * Action-name prefixes that never produce a page-visit row. Grid AJAX
     * (mui_index_render) fires on every sort, filter and page of every grid;
     * the login screens are covered by the login observers; and this module's
     * own screens would otherwise log an entry for reading the log.
     *
     * @var array<int, string>
     */
    private array $ignoredActions;

    /**
     * @param array<string, string> $ignoredActions
     */
    public function __construct(
        private readonly Config $config,
        private readonly Session $authSession,
        private readonly Buffer $buffer,
        private readonly Writer $writer,
        private readonly ContextProvider $contextProvider,
        private readonly Http $request,
        array $ignoredActions = []
    ) {
        $this->ignoredActions = array_values(array_map('strtolower', $ignoredActions));
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isEnabled() || $this->buffer->isFlushed()) {
            return;
        }

        $entries = $this->buffer->getEntries();
        if ($entries === []) {
            $entries = $this->pageVisitEntries();
        }

        if ($entries === []) {
            return;
        }

        $this->writer->write($this->contextProvider->get(), $entries);
        $this->buffer->markFlushed();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pageVisitEntries(): array
    {
        if (!$this->config->isPageVisitLogged() || !$this->authSession->isLoggedIn()) {
            return [];
        }

        // GET only, and never an XHR: a POST that buffered nothing is a failed
        // or no-op write, not a page view, and every admin screen fires a
        // handful of background XHRs that nobody navigated to.
        if (strtoupper((string) $this->request->getMethod()) !== 'GET' || $this->request->isXmlHttpRequest()) {
            return [];
        }

        $fullActionName = strtolower((string) $this->request->getFullActionName());
        foreach ($this->ignoredActions as $ignored) {
            if ($ignored !== '' && str_starts_with($fullActionName, $ignored)) {
                return [];
            }
        }

        return [[
            'action_type' => ActionType::PAGE_VISIT,
            'status' => ActionType::STATUS_SUCCESS,
        ]];
    }
}
