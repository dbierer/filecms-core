<?php
namespace FileCMS\Common\Install;

use RuntimeException;

/**
 * One-time setup: adds the "upgrade-2026-07" script entry to a
 * filecms-website-based project's own composer.json, so it can
 * subsequently be run at any time via:
 *   composer upgrade-2026-07
 *
 * Composer only executes "scripts" defined in the ROOT composer.json, never
 * those of a dependency, so this entry has to live in the consumer project's
 * own composer.json rather than filecms-core's.
 *
 * Run this once from the root of your filecms-website-based project (after
 * "composer update" or "composer install"):
 *   vendor/bin/filecms-install-hooks
 */
class InstallHooks
{
    public const SCRIPT_NAME    = 'upgrade-2026-07';
    public const SCRIPT_HANDLER = UpgradeTinymce::class . '::run';

    public static function run(string $projectRoot) : void
    {
        $composerJsonFile = $projectRoot . '/composer.json';
        if (!is_file($composerJsonFile)) {
            throw new RuntimeException(sprintf('Unable to find composer.json in: %s', $projectRoot));
        }

        $contents = file_get_contents($composerJsonFile);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read: %s', $composerJsonFile));
        }

        $data = json_decode($contents, true);
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Unable to parse JSON: %s', $composerJsonFile));
        }

        if (($data['scripts'][self::SCRIPT_NAME] ?? null) === self::SCRIPT_HANDLER) {
            printf("composer.json already has the \"%s\" script -- nothing to do.\n", self::SCRIPT_NAME);
            return;
        }

        $data['scripts'][self::SCRIPT_NAME] = self::SCRIPT_HANDLER;

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false || file_put_contents($composerJsonFile, $encoded . "\n") === false) {
            throw new RuntimeException(sprintf('Unable to write: %s', $composerJsonFile));
        }

        printf("Added \"%s\" script to %s\n", self::SCRIPT_NAME, $composerJsonFile);
        printf("You can now run it at any time with: composer %s\n", self::SCRIPT_NAME);
    }
}
