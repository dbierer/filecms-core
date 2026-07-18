<?php
namespace FileCMS\Common\Install;

use Composer\Script\Event;
use RuntimeException;

/**
 * Composer script: upgrades a filecms-website-based project's Super editor
 * from CKEditor to TinyMCE.
 *
 * Equivalent of "upgrade_2026_07.sh" from the filecms-website repository.
 *
 * Run this from the root of your filecms-website-based project: the
 * directory that contains "composer.json", "src", "templates" and (after
 * "composer install") "vendor".
 *
 * Usage (from any composer.json that requires unlikelysource/filecms-core):
 *   composer upgrade-2026-07
 */
class UpgradeTinymce
{
    public const REPO_RAW = 'https://raw.githubusercontent.com/dbierer/filecms-website/main';
    public const PACKAGE   = 'tinymce/tinymce:^8.0';

    public const BACKUP_FILES = [
        'templates/super/edit.phtml',
        'src/upload.php',
        'src/config/config.php',
    ];

    public const DOWNLOAD_FILES = [
        'templates/super/edit.phtml',
        'src/upload.php',
    ];

    public static function run(Event $event) : void
    {
        $cwd = getcwd();

        echo "Backing up files that will be replaced ...\n";
        self::backup($cwd);

        echo "Adding tinymce/tinymce to composer.json and installing it ...\n";
        self::requireTinymce($cwd);

        echo "Copying TinyMCE assets into public/tinymce ...\n";
        self::copyAssets($cwd);

        echo "Downloading updated templates/super/edit.phtml and src/upload.php ...\n";
        self::download($cwd);

        self::printManualSteps();
    }

    protected static function backup(string $cwd) : void
    {
        foreach (self::BACKUP_FILES as $file) {
            $source = $cwd . '/' . $file;
            if (!is_file($source)) {
                throw new RuntimeException(sprintf('Unable to back up missing file: %s', $file));
            }
            $target = $source . '.bak';
            if (!copy($source, $target)) {
                throw new RuntimeException(sprintf('Unable to back up file: %s', $file));
            }
            printf("%s -> %s\n", $file, $file . '.bak');
        }
    }

    protected static function requireTinymce(string $cwd) : void
    {
        $cmd = sprintf(
            'composer require %s --working-dir=%s',
            escapeshellarg(self::PACKAGE),
            escapeshellarg($cwd)
        );
        passthru($cmd, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf('Composer command failed: %s', $cmd));
        }
    }

    protected static function copyAssets(string $cwd) : void
    {
        $source = $cwd . '/vendor/tinymce/tinymce';
        $target = $cwd . '/public/tinymce';
        if (!is_dir($source)) {
            throw new RuntimeException(sprintf('TinyMCE assets not found: %s', $source));
        }
        self::copyDir($source, $target);
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
                printf("%s -> %s\n", $sourcePath, $targetPath);
            }
        }
    }

    protected static function download(string $cwd) : void
    {
        foreach (self::DOWNLOAD_FILES as $file) {
            $url = self::REPO_RAW . '/' . $file;
            $contents = file_get_contents($url);
            if ($contents === false) {
                throw new RuntimeException(sprintf('Unable to download: %s', $url));
            }
            $target = $cwd . '/' . $file;
            if (file_put_contents($target, $contents) === false) {
                throw new RuntimeException(sprintf('Unable to write file: %s', $target));
            }
            printf("%s -> %s\n", $url, $file);
        }
    }

    protected static function printManualSteps() : void
    {
        echo "\n";
        echo str_repeat('=', 80) . "\n";
        echo "Automatic steps are done. Two manual steps remain -- see README.md for detail:\n";
        echo "\n";
        echo "1. In src/config/config.php, rename the 'SUPER' => 'ckeditor' key to 'tinymce'\n";
        echo "   (keep the existing 'width' and 'height' values):\n";
        echo "       'tinymce' => [ 'width' => '100%', 'height' => 400 ],\n";
        echo "\n";
        echo "2. If you had customized templates/super/edit.phtml or src/upload.php,\n";
        echo "   re-apply those customizations by comparing against the .bak files just\n";
        echo "   created, then remove the .bak files once you're satisfied.\n";
        echo str_repeat('=', 80) . "\n";
    }
}
