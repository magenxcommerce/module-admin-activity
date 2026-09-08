<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Controller\Adminhtml\Activity;

use Magenx\AdminActivity\Model\ActivityFactory;
use Magenx\AdminActivity\Model\ResourceModel\Activity as ActivityResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

/**
 * One activity record, with its field-level changes.
 */
class View extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magenx_AdminActivity::activity';

    public function __construct(
        Context $context,
        private readonly ActivityFactory $activityFactory,
        private readonly ActivityResource $activityResource
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $activityId = (int) $this->getRequest()->getParam('id');
        $activity = $this->activityFactory->create();

        if ($activityId > 0) {
            $this->activityResource->load($activity, $activityId);
        }

        if (!$activity->getId()) {
            $this->messageManager->addErrorMessage(__('This activity record no longer exists.'));

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
        }

        /** @var Page $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $result->setActiveMenu(self::ADMIN_RESOURCE);
        $result->getConfig()->getTitle()->prepend(__('Activity #%1', $activity->getId()));

        return $result;
    }
}
