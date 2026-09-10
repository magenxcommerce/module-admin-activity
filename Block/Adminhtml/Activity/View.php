<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Block\Adminhtml\Activity;

use Magenx\AdminActivity\Model\Activity;
use Magenx\AdminActivity\Model\Activity\EntityRegistry;
use Magenx\AdminActivity\Model\ActivityFactory;
use Magenx\AdminActivity\Model\ResourceModel\Activity as ActivityResource;
use Magenx\AdminActivity\Model\Config\Source\ActionType as ActionTypeLabels;
use Magenx\AdminActivity\Model\ResourceModel\ActivityDetail\CollectionFactory as DetailCollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Phrase;

/**
 * Renders one activity record and its before/after table.
 */
class View extends Template
{
    private ?Activity $activity = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly ActivityFactory $activityFactory,
        private readonly ActivityResource $activityResource,
        private readonly DetailCollectionFactory $detailCollectionFactory,
        private readonly EntityRegistry $entityRegistry,
        private readonly ActionTypeLabels $actionTypeLabels,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getActivity(): Activity
    {
        if ($this->activity === null) {
            $this->activity = $this->activityFactory->create();
            $activityId = (int) $this->getRequest()->getParam('id');
            if ($activityId > 0) {
                $this->activityResource->load($this->activity, $activityId);
            }
        }

        return $this->activity;
    }

    /**
     * @return array<int, array{field_name: string, old_value: ?string, new_value: ?string}>
     */
    public function getChanges(): array
    {
        $activityId = (int) $this->getActivity()->getId();
        if ($activityId === 0) {
            return [];
        }

        $collection = $this->detailCollectionFactory->create()
            ->addActivityFilter($activityId)
            ->setOrder('detail_id', 'ASC');

        $changes = [];
        foreach ($collection as $detail) {
            $changes[] = [
                'field_name' => (string) $detail->getData('field_name'),
                'old_value' => $detail->getData('old_value') === null ? null : (string) $detail->getData('old_value'),
                'new_value' => $detail->getData('new_value') === null ? null : (string) $detail->getData('new_value'),
            ];
        }

        return $changes;
    }

    /**
     * The entity type as a human label - and the reason EntityRegistry exists.
     *
     * entity_type is a class name read back out of a database column. It is
     * passed through the allowlist before it is used for anything, and an
     * unrecognised value is shown verbatim as text rather than resolved, so a
     * tampered row cannot turn a page render into class instantiation.
     */
    public function getEntityLabel(): ?string
    {
        $activity = $this->getActivity();
        $entityType = (string) $activity->getData('entity_type');
        if ($entityType === '') {
            return null;
        }

        if ($this->entityRegistry->getAllowedClass($entityType) === null) {
            return $entityType;
        }

        return (string) ($activity->getData('entity_label') ?: $entityType);
    }

    public function getActionTypeLabel(): Phrase
    {
        return $this->actionTypeLabels->getLabel((string) $this->getActivity()->getData('action_type'));
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('*/*/index');
    }
}
