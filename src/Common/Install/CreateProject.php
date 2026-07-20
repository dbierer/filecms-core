<?php
namespace FileCMS\Common\Install;

use Composer\Script\Event;
use RuntimeException;
use ZipArchive;

/**
 * Composer script: downloads the filecms-website skeleton archive, unzips it
 * into a target directory, then installs the latest version of filecms-core.
 *
 * Usage (from any composer.json that requires unlikelysource/filecms-core):
 *   composer create-project-website /path/to/new/website
 */
class CreateProject
{
    public const ARCHIVE_URL = 'https://github.com/dbierer/filecms-website/archive/refs/tags/0.1.1.zip';
    public const PACKAGE     = 'unlikelysource/filecms-core';

    public static function run(Event $event) : void
    {
        $args = $event->getArguments();
        if (empty($args[0])) {
            throw new RuntimeException(
                'Missing target directory. Usage: composer create-project-website <path/to/new/website>'
            );
        }
        $target = rtrim($args[0], '/\\');
        self::download($target);
        self::install($target);
    }

    protected static function download(string $target) : void
    {
        if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
            throw new RuntimeException(sprintf('Unable to create directory: %s', $target));
        }
        $zipFile = tempnam(sys_get_temp_dir(), 'filecms_') . '.zip';
        $contents = file_get_contents(self::ARCHIVE_URL);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to download archive: %s', self::ARCHIVE_URL));
        }
        file_put_contents($zipFile, $contents);

        $extractDir = sys_get_temp_dir() . '/filecms_extract_' . uniqid();
        mkdir($extractDir, 0755, true);
        self::extract($zipFile, $extractDir);
        unlink($zipFile);

        // GitHub archives extract into a single top-level folder, e.g. filecms-website-0.1
        $entries = array_values(array_diff(scandir($extractDir), ['.', '..']));
        $sourceDir = (count($entries) === 1 && is_dir($extractDir . '/' . $entries[0]))
            ? $extractDir . '/' . $entries[0]
            : $extractDir;

        self::copyDir($sourceDir, $target);
        self::removeDir($extractDir);

        printf("Downloaded and unzipped %s into %s\n", self::ARCHIVE_URL, $target);
    }

    protected static function extract(string $zipFile, string $extractDir) : void
    {
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) {
                throw new RuntimeException(sprintf('Unable to open ZIP archive: %s', $zipFile));
            }
            $zip->extractTo($extractDir);
            $zip->close();
            return;
        }
        // Fall back to the system "unzip" utility if ext-zip isn't available.
        exec(sprintf('unzip -qq %s -d %s', escapeshellarg($zipFile), escapeshellarg($extractDir)), $output, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException(
                'Unable to extract archive: the "zip" PHP extension is missing and no "unzip" utility was found.'
            );
        }
    }

    protected static function install(string $target) : void
    {
        $cmd = sprintf(
            'composer require %s --working-dir=%s',
            escapeshellarg(self::PACKAGE),
            escapeshellarg($target)
        );
        passthru($cmd, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf('Composer command failed: %s', $cmd));
        }
    }

    protected static function copyDir(string $source, string $target) : void
    {
        if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
            throw new RuntimeException(sprintf('Unable to create directory: %s', $target));
        }
        foreach (array_diff(scandir($source), ['.', '..']) as $entry) {
            $sourcePath = $source . '/' . $entry;
            $targetPath = $target . '/' . $entry;
            if (is_dir($sourcePath)) {
                self::copyDir($sourcePath, $targetPath);
            } else {
                copy($sourcePath, $targetPath);
            }
        }
    }

    protected static function removeDir(string $dir) : void
    {
        foreach (array_diff(scandir($dir), ['.', '..']) as $entry) {
            $path = $dir . '/' . $entry;
            is_dir($path) ? self::removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
