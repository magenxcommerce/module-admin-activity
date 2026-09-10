<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Model\Activity;

/**
 * The tracked-entity map and the model allowlist, in one structure.
 *
 * Two jobs that must never disagree:
 *
 *  1. Which models are worth logging. Every model_save_after in the admin passes
 *     through here; anything not listed (flags, indexer state, session records,
 *     this module's own rows) is dropped before any diffing happens.
 *  2. Which classes may be INSTANTIATED while rendering a log entry. entity_type
 *     is a class name in a database column, and a row reachable by SQL injection
 *     elsewhere, a restored backup, or a careless migration must not become
 *     arbitrary class instantiation. getAllowedClass() returns a class only if
 *     it is listed here verbatim.
 *
 * Configured entirely from etc/di.xml, so adding an entity is one <item>.
 */
class EntityRegistry
{
    /** @var array<string, array{class: string, label: string, name_field: string, id_field: string}> */
    private array $entities = [];

    /** @var array<string, string> lowercase class => configured class */
    private array $byClass = [];

    /**
     * Memoised resolveObject() results, keyed by concrete class name - null
     * included, since the overwhelming majority of models saved in the admin
     * are untracked and resolve to null every time.
     *
     * model_save_commit_after fires for every model in every admin request, and
     * the ancestor walk below calls class_parents() on each one. Class names do
     * not change within a request, so the walk is worth doing exactly once per
     * class.
     *
     * @var array<string, array{class: string, label: string, name_field: string, id_field: string}|null>
     */
    private array $resolvedByObjectClass = [];

    /**
     * @param array<string, array<string, string>> $entities
     */
    public function __construct(array $entities = [])
    {
        foreach ($entities as $code => $entity) {
            $class = trim((string) ($entity['class'] ?? ''));
            if ($class === '') {
                continue;
            }

            $class = ltrim($class, '\\');
            $this->entities[(string) $code] = [
                'class' => $class,
                'label' => (string) ($entity['label'] ?? $class),
                'name_field' => (string) ($entity['name_field'] ?? ''),
                'id_field' => (string) ($entity['id_field'] ?? ''),
            ];
            $this->byClass[strtolower($class)] = (string) $code;
        }
    }

    /**
     * Resolves a class name, optionally falling back to its ancestors.
     *
     * The ancestor walk is why the parameter exists: what reaches an observer is
     * almost never the configured class but a generated
     * Magento\Catalog\Model\Product\Interceptor, and third-party modules
     * routinely subclass core models outright.
     *
     * @param array<int, string> $ancestors ordered nearest-first
     * @return array{class: string, label: string, name_field: string, id_field: string}|null
     */
    public function resolve(string $class, array $ancestors = []): ?array
    {
        foreach (array_merge([$class], $ancestors) as $candidate) {
            $code = $this->byClass[strtolower(ltrim((string) $candidate, '\\'))] ?? null;
            if ($code !== null) {
                return $this->entities[$code];
            }
        }

        return null;
    }

    /**
     * @return array{class: string, label: string, name_field: string, id_field: string}|null
     */
    public function resolveObject(object $object): ?array
    {
        $class = $object::class;

        // array_key_exists, not ??= : a cached null is the common case (most
        // models saved in the admin are untracked) and ??= would treat it as a
        // miss and redo the ancestor walk every single time.
        if (!array_key_exists($class, $this->resolvedByObjectClass)) {
            $this->resolvedByObjectClass[$class] =
                $this->resolve($class, array_values(class_parents($object) ?: []));
        }

        return $this->resolvedByObjectClass[$class];
    }

    public function isTracked(object $object): bool
    {
        return $this->resolveObject($object) !== null;
    }

    /**
     * The strict allowlist: an exact, configured class or nothing. No ancestor
     * fallback here - that would let an unlisted subclass through, which is the
     * whole thing this guards against.
     */
    public function getAllowedClass(string $class): ?string
    {
        $code = $this->byClass[strtolower(ltrim($class, '\\'))] ?? null;

        return $code === null ? null : $this->entities[$code]['class'];
    }

    /**
     * @return array<string, string> class => label, for the grid's filter options
     */
    public function getLabels(): array
    {
        $labels = [];
        foreach ($this->entities as $entity) {
            $labels[$entity['class']] = $entity['label'];
        }

        return $labels;
    }
}
