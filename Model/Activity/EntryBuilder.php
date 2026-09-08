<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Model\AbstractModel;

/**
 * Turns a model that was just saved or deleted into a buffered log entry.
 *
 * Shared by the save and delete observers so the entity-identification rules
 * live in exactly one place.
 */
class EntryBuilder
{
    /**
     * Tried in order when the tracked entity declares no name_field. Covers
     * essentially every Magento entity without needing a per-entity config.
     */
    private const NAME_FALLBACKS = ['name', 'title', 'sku', 'code', 'label', 'email', 'username', 'increment_id'];

    /** @var array<int, string> */
    private array $maskedClasses;

    /**
     * @param array<string, string> $maskedClasses classes whose every value is masked, from etc/di.xml
     */
    public function __construct(
        private readonly EntityRegistry $registry,
        private readonly DiffBuilder $diffBuilder,
        private readonly ActionTypeResolver $actionTypeResolver,
        private readonly Http $request,
        array $maskedClasses = []
    ) {
        $this->maskedClasses = array_values(array_filter(array_map('strval', $maskedClasses)));
    }

    /**
     * @return array<string, mixed>|null null when the model is not tracked or nothing changed
     */
    public function forSave(AbstractModel $object): ?array
    {
        $entity = $this->registry->resolveObject($object);
        if ($entity === null) {
            return null;
        }

        // getOrigData() is the pre-save snapshot and is still intact here:
        // AbstractDb::save() never calls setOrigData(), which only happens on
        // load. An empty snapshot therefore means the model was never loaded -
        // this is a create.
        $orig = $object->getOrigData();
        $isNew = !is_array($orig) || $orig === [];

        $changes = $this->diffBuilder->build($isNew ? [] : $orig, $object->getData());
        if ($changes === [] && !$isNew) {
            // Magento saves models that nothing touched all the time; logging
            // those buries the real changes.
            return null;
        }

        return $this->entry($entity, $object, $this->resolveType($isNew), $changes);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forDelete(AbstractModel $object): ?array
    {
        $entity = $this->registry->resolveObject($object);
        if ($entity === null) {
            return null;
        }

        $data = $object->getOrigData();
        if (!is_array($data) || $data === []) {
            $data = $object->getData();
        }

        // Everything the row held becomes an old value with no new value: what a
        // delete destroyed is the only interesting thing about it.
        return $this->entry($entity, $object, ActionType::DELETE, $this->diffBuilder->build($data, []));
    }

    /**
     * @param array{class: string, label: string, name_field: string, id_field: string} $entity
     * @param array<int, array<string, ?string>> $changes
     * @return array<string, mixed>
     */
    private function entry(array $entity, AbstractModel $object, string $actionType, array $changes): array
    {
        $idField = $entity['id_field'] !== '' ? $entity['id_field'] : null;

        return [
            'action_type' => $actionType,
            'status' => ActionType::STATUS_SUCCESS,
            'entity_type' => $entity['class'],
            'entity_label' => $entity['label'],
            'entity_id' => $idField !== null ? $object->getDataUsingMethod($idField) : $object->getId(),
            'entity_name' => $this->resolveName($object, $entity['name_field']),
            'changes' => $this->applyClassMask($object, $changes),
        ];
    }

    /**
     * Some models hold a credential in an innocuously named column, which no
     * field-name rule can catch. The encrypted config backend is the standing
     * example: its column is called `value` and it holds the ciphertext of a
     * payment gateway key or an SMTP password. The change is still recorded -
     * knowing that someone rotated a key at 03:00 is the point - but the
     * ciphertext is not copied into a second table.
     *
     * @param array<int, array<string, ?string>> $changes
     * @return array<int, array<string, ?string>>
     */
    private function applyClassMask(AbstractModel $object, array $changes): array
    {
        foreach ($this->maskedClasses as $class) {
            if (!is_a($object, $class)) {
                continue;
            }

            foreach ($changes as &$change) {
                $change['old_value'] = $change['old_value'] === null ? null : FieldFilter::MASK;
                $change['new_value'] = $change['new_value'] === null ? null : FieldFilter::MASK;
            }
            unset($change);

            return $changes;
        }

        return $changes;
    }

    private function resolveType(bool $isNew): string
    {
        return $this->actionTypeResolver->resolve(
            (string) $this->request->getFullActionName(),
            (string) $this->request->getMethod(),
            $isNew
        );
    }

    private function resolveName(AbstractModel $object, string $nameField): ?string
    {
        $candidates = $nameField !== '' ? [$nameField] : self::NAME_FALLBACKS;

        foreach ($candidates as $candidate) {
            $value = $object->getDataUsingMethod($candidate);
            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }
}
