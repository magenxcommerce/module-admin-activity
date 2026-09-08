<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * The grid's single action: View. There is no Edit and no Delete - an audit log
 * an admin can rewrite is not an audit log.
 *
 * The route is an explicit path from the column's own config, never the
 * wildcard form: prepareDataSource runs again on every grid reload, and that
 * request is mui/index/render, so wildcards would resolve against THAT route
 * and produce mui/index/view - every link 404s as soon as someone sorts or
 * pages the grid.
 */
class ActivityActions extends Column
{
    /**
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $indexField = (string) ($this->getData('config/indexField') ?: 'activity_id');
        $viewUrlPath = (string) ($this->getData('config/viewUrlPath') ?: 'magenx_admin_activity/activity/view');
        $name = (string) $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item[$indexField])) {
                continue;
            }

            $item[$name] = [
                'view' => [
                    'href' => $this->urlBuilder->getUrl($viewUrlPath, ['id' => $item[$indexField]]),
                    'label' => __('View'),
                ],
            ];
        }

        return $dataSource;
    }
}
