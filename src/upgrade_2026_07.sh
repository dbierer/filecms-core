#!/bin/bash
#
# Upgrade script: switches the Super editor from CKEditor to TinyMCE.
#
# Run this from the root of your filecms-website-based project: the
# directory that contains "composer.json", "src", "templates" and (after
# "composer install") "vendor".
#
set -e

REPO_RAW='https://raw.githubusercontent.com/dbierer/filecms-website/main'

echo "Backing up files that will be replaced ..."
cp -v templates/super/edit.phtml templates/super/edit.phtml.bak
cp -v src/upload.php src/upload.php.bak
cp -v src/config/config.php src/config/config.php.bak

echo "Adding tinymce/tinymce to composer.json and installing it ..."
composer require tinymce/tinymce:^8.0

echo "Copying TinyMCE assets into public/tinymce ..."
mkdir -p public/tinymce
cp -ruv vendor/tinymce/tinymce/. public/tinymce/

echo "Downloading updated templates/super/edit.phtml and src/upload.php ..."
curl -fL "$REPO_RAW/templates/super/edit.phtml" -o templates/super/edit.phtml
curl -fL "$REPO_RAW/src/upload.php" -o src/upload.php

echo
echo "=============================================================================="
echo "Automatic steps are done. Two manual steps remain -- see README.md for detail:"
echo
echo "1. In src/config/config.php, rename the 'SUPER' => 'ckeditor' key to 'tinymce'"
echo "   (keep the existing 'width' and 'height' values):"
echo "       'tinymce' => [ 'width' => '100%', 'height' => 400 ],"
echo
echo "2. If you had customized templates/super/edit.phtml or src/upload.php, "
echo "   re-apply those customizations by comparing against the .bak files just"
echo "   created, then remove the .bak files once you're satisfied."
echo "=============================================================================="
