<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 *
 * Makes the suite runnable both inside a Magento installation and on its own.
 *
 * Inside an installation, vendor/autoload.php is found a few directories up and
 * already knows this module's PSR-4 mapping. Standalone - which is how CI-less
 * local runs work, since magento/framework cannot be resolved from Packagist -
 * the fallback below maps Magenx\AdminActivity\ onto this repository. The five
 * classes under test import nothing from the framework, so that is enough.
 */
declare(strict_types=1);

$moduleRoot = dirname(__DIR__, 2);

foreach ([$moduleRoot . '/vendor/autoload.php', dirname($moduleRoot, 3) . '/autoload.php'] as $autoloader) {
    if (is_file($autoloader)) {
        require_once $autoloader;
        break;
    }
}

spl_autoload_register(static function (string $class) use ($moduleRoot): void {
    $prefix = 'Magenx\\AdminActivity\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = $moduleRoot . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});
