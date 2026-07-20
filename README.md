# FileCMS
Simple PHP framework that builds HTML files from HTML widgets.
* Includes a class that can generate and validate CAPTCHAs (uses the GD extension).
* Includes the CKEditor for full-featured editing.
* Includes an email contact form that uses PHPMailer.
* Is able to import single files from a legacy website, or can do bulk import
* Includes a complete set of transformation filters that can be applied during import, or afterwards
* Entirely file-based: does not require a database!
* Very fast and flexible.
* Once you've got it up and running, just upload HTML snippets and/or modify the configuration file.
* IMPORTANT: minimum version is PHP 8!

License: Apache v2

## Initial Installation
### Quick Install (Composer Script)
If you already have a Composer project that requires `unlikelysource/filecms-core`, you can scaffold a new website in one step:
```
composer create-project-website /path/to/new/website
```
This downloads the `filecms-website` skeleton archive, unzips it into `/path/to/new/website`, then runs `composer require unlikelysource/filecms-core` inside that directory to install the latest version of this package.

### Manual Install
1. Clone the `filecms-website` repository to the project root of your new website.
  * If you have `git` installed run this command from a command prompt / terminal window:
```
git clone https://github.com/dbierer/filecms-website.git /path/to/website
```
  * If you don't have `git` installed, just download the ZIP file from:
```
https://github.com/dbierer/filecms-website/archive/refs/tags/0.1.zip
```
  * And unzip into `/path/to/website`
2. Use composer to install `unlikelysource/filecms-core` and 3rd party source code (e.g. PHPMailer)
```
cd /path/to/website
wget https://getcomposer.org/download/latest-stable/composer.phar
php composer.phar self-update
php composer.phar install
```

## Upgrading
### 2026-07: Switch the Super editor from CKEditor to TinyMCE
Run this from the root of your filecms-website-based project (the directory that contains `composer.json`, `src`, `templates` and, after `composer install`, `vendor`):
```
src/upgrade_2026_07.sh
```
This script does the following:
1. Backs up `templates/super/edit.phtml`, `src/upload.php` and `src/config/config.php` (adds a `.bak` suffix)
2. Adds `tinymce/tinymce` to `composer.json` and installs it
3. Copies the TinyMCE assets into `public/tinymce`
4. Downloads the updated `templates/super/edit.phtml` and `src/upload.php`

Two manual steps remain afterward:
1. In `src/config/config.php`, rename the `'SUPER' => 'ckeditor'` key to `'tinymce'` (keep the existing `width` and `height` values):
```
'tinymce' => [ 'width' => '100%', 'height' => 400 ],
```
2. If you had customized `templates/super/edit.phtml` or `src/upload.php`, re-apply those customizations by comparing against the `.bak` files just created, then remove the `.bak` files once you're satisfied.

## Basic website config
All references are from `/path/to/website`

* Primary config file: `/src/config/config.php`
* Bootstrap file: `/bootstrap.php`
* Pre-processing code: `/src/processing.php`

Additional documentation on these three follows.

## To Run Locally Using PHP
From this directory, run the following command:
```
cd /path/to/website
php -S localhost:8888 -t public
```

## To Run Locally Using Docker and docker-compose

### Windows
Install [Docker Desktop for Windows](https://hub.docker.com/editions/community/docker-ce-desktop-windows)

Open the Power Shell (some commands don't work in the regular command prompt)

To bring the docker container online, run this command:
```
cd \path\to\website
admin up
```
To stop the container do this:
```
admin down
```
To open a command shell into the container:
```
admin shell
```

### Linux / Mac
Install Docker + docker-compose:
* Mac
  * Install [Docker Desktop for Mac](https://hub.docker.com/editions/community/docker-ce-desktop-mac)
* Linux
  * Install [Docker](https://docs.docker.com/engine/install/)
  * Install [docker-compose](https://docs.docker.com/compose/install/#install-compose-on-linux-systems)

Open a terminal window (Terminal Application)

To bring the docker container online, run this command:
```
cd /path/to/website
./admin.sh up
```
To stop the container do this:
```
./admin.sh down
```
To open a command shell into the container:
```
./admin.sh shell
```

### Browser Access
To access from your browser:
```
http://localhost:8888/
```
* Or, if your IP addressing is working:
```
http://10.10.10.10/
```

## Bootstrap and Document Root
Set the website document root to `/public`
* The central point of entry is `/public/index.php`
* There is a file `.htaccess` that controls URL rewriting
* If you are using *nginx* you will need to incorporate the same logic into your primary config file
* `/public/index.php` first loads the *bootstrap* file `/bootstrap.php`
  * This file defines three key constants used throughout the program (summarized in the table shown next)
  * The bootstrap file also loads the Composer autoloader
  * If you add your own classes under `/src` be sure to update `composer.json` and refresh Composer autoloading:
```
composer dump-autoload
```
Here is a summary of the three key constants defined by `/bootstrap.php`.  Change as needed.

| Constant | Default | Description |
| :------  | :------ | :----------
| BASE_DIR | Same directory as `bootstrap.php` | Project root |
| HTML_DIR | `/templates/site` | Location of HTML snippets |
| SRC_DIR  | `/src` | Location of source code |

## Pre-Processing
Before the final HTML view is rendered, `/public/index.php` includes `/src/processsing.php`.
In this file you can include any pre-processing you need done.
* The request URL is available as the variable `$uri`
* This is where the admin URL (e.g. `/super`) is captured and sent to processing

## Templates
By default templates are stored in `/templates/site`.  You can alter this in the config file.
### Config File
Default: `/src/config/config.php`
* Delimiter: `DELIM` defaults to `%%`
* "Cards" `CARDS` defaults to `cards`
  * Represents the subdirectory under which view renderer expects to file HTML "cards"
### Layout
The overall website look-and-feel is in a single HTML file, by default in `/templates/layout/layout.phtml`.
* The view rendered by requests is injected into the layout by replacing `%%CONTENTS%%`.

### HTML
You can create HTML snippets designed to fit into `layout.phtml` any place in the designated HTML directory.
* Be sure to set the constant `HTML_DIR` in the file `/bootstrap.php`.

### Cards
Important: each `%%CARD%%` directive you add **must** be on its own line!

#### Auto-Populate All Cards
To get an HTML file to auto-populate with cards use this syntax:
```
DELIM+DIR+DELIM
```
Example: you have a subdirectory off `HTML_DIR` named `projects` and you want to load all HTML card files under the `cards` folder:
```
%%PROJECTS%%
```
#### Auto-Populate Specific Number of Cards
To only load a certain (random) number of cards, use `=`.
Example: you have a subdirectory off `HTML_DIR` named `features` and you want to load 3 random HTML card files under the `cards` folder:
```
%%FEATURES=3%%
```
#### Auto-Populate Specified Cards in a Certain Order
For each card, only use the base filename, no extension (i.e. do not add `.html`).
Example: you have a directory `HTML_DIR/blog/cards` with files `one.html`, `two.html`, `three.html`, etc.
You want the cards to be loaded in the order `one.html`, `two.html`, `three.html`, etc.:
```
%%BUNDLES=one,two,three,etc.%%
```

## Editing Pages
By default, if you enter the URL `/super/login` you're prompted to login as a super user.
Configure the username, password and secondary authentication factors in: `/src/config/config.php` under the `SUPER` config key.

### SUPER config key
Example configuration for super user:
```
// other config not shown
'SUPER' => [
    'username'  => 'REPL_SUPER_NAME',  // fill in your username here
    'password'  => 'REPL_SUPER_PWD',   // fill in your password here
    /*
     * extra login validation fields
     * change key/value pairs as desired
     * add as many as you want
     * they're selected at random when asked to login
     */
    'validation'   => [
        // if value is array, authentication needs to use "in_array()"
        'City'        => ['London','Tokyo'],
        'Postal Code' => 'NW1 6XE',
        'Last Name'   => ['Holmes','Lincoln'],
    ],
    'alt_logins' => [
        'REPL_OTHER_NAME' => [
            'username'  => 'REPL_OTHER_NAME',  // fill in alt username here
            'password'  => 'REPL_OTHER_PWD',   // fill in alt password here
        ],
        // add others as needed
    ],
    'attempts'  => 3,
    'message'   => 'Sorry! Unable to login.  Please contact your administrator',
    // reserved for future use:
    'allowed_ip' => ['10.0.0.0/24','192.168.0.0/24'],
    // array of $_SERVER keys to store in session if authenticated
    'profile'  => ['REMOTE_ADDR','HTTP_ACCEPT_LANGUAGE'],
    // change the values to reflect the names of fields in your login.phtml form
    'login_fields' => [
        'name'     => 'name',
        'password' => 'password',
        'other'    => 'other',
        'phrase'   => 'phrase',     // CAPTCHA phrase
    ],
    // only files with these extensions can be edited
    'allowed_ext'  => ['html','htm'],
    'ckeditor'     => [
        'width'  => '100%',
        'height' => 400,
    ],
    'super_url'  => '/super',                // IMPORTANT: needs to be a subdir off the "super_dir" setting
    'super_dir'  => BASE_DIR . '/templates', // IMPORTANT: needs to have a subdir === "super_url" setting
    'super_menu' => BASE_DIR . '/templates/layout/super_menu.html',
    'backup_dir' => BASE_DIR . '/backups',
    'backup_cmd' => BASE_DIR . 'zip -r %%BACKUP_FN%% %%BACKUP_SRC%%',
],
// other config not shown
```
Here's a breakdown of the `SUPER` config keys

| Key | Explanation |
| :-- | :---------- |
| username | Super user login name |
| password | Super user login password |
| attempts | Maximum number of failed login attempts.  If this number is exceeded, a random third authentication field is required for login. |
| validation | Set of key:value pairs randomly selected each time you login. Values can be in the form of an array. |
| alt_logins | Additional usernames and passwords |
| message  | Message that displayed if login fails |
| profile  | Array of `$_SERVER` keys that form the super user's profile once logged in |
| login_fields | Field names drawn from your `login.phtml` login form |
| validation   | You can specify as many of these as you want.  If the login attemp exceeds `attempts`, the SimpleHtml framework will automatically add a random field drawn from this list. |
| allowed_ext  | Only files with an extension on this list can be edited. |
| ckeditor     | Default width and height of the CKeditor screen |
| super_* | Settings pertaining to the location of the super admin user URL, templates and menu |

## Contact Form
The skeleton app includes under `/templates` a file `contact.phtml` that implements an email contact form with a CAPTCHA
* Uses the PHPMailer package
* Configuration can be done in `/src/config/config.php` using the `COMPANY_EMAIL` key
* CAPTCHA configuration can be done in `/src/config/config.php` using the `CAPTCHA` key

## Import Feature
You can enable the import feature by setting the `IMPORT::enable` config key to `TRUE`.
The importer itself is at `/templates/site/super/import.phtml`.
Selected transformation filters can be applied to one or more pages during the import process.

Here are some notes on config file settings under the `IMPORT` config key:
* `IMPORT::enable`
  * Set this value to `FALSE` if you do not wish this feature to be available.
* `IMPORT::delim_start`
  * tells the importer where to start cutting out content from the HTML source
  * default: &lt;body&gt;
* `IMPORT::delim_stop`
  * tells the importer where to stop cutting out content from the HTML source
  * default: &lt;/body&gt;
* `IMPORT::trusted_src`
  * list of one or more prefixes from "trusted" sources for import
  * allows you to limit where imports can be taken from
  * in case you get hacked, this prevents attackers from importing malicious from their own sites
* `IMPORT::import_file_field`
  * this file must be in JSON format
  * name of the file upload field used in the form
  * you can upload a list of URLs to import followed by a list of transforms to apply
    * the URLs key is 'URLS'
    * the 'IMPORT' key lets you override any Import configuration including the transforms to apply during import
* `IMPORT::transform`
  * sub-array of transforms to make available to the importer
  * `callback` : anything that's callable
    * if your own PHP function or anonymous function, signature must match `SimpleHtml\Transform\TransformInterface`
  * `params` : array of parameters the callback expects
  * `description` : shows up when you run `/super/import`
After logging in as the admin user, go to `/super/import`.

## Transform Feature
You can apply transformation filters on existing pages.
The importer itself is at `/templates/site/super/import.phtml`.
Included transformation classes are located in `/src/Transform`.
You can add your own by simply extending `FileCMS\Common\Transform\Base`.
After logging in as the admin user, go to `/super/transform`.

Here are some notes on config file settings under the `TRANSFORM` config key:
* `TRANSFORM::enable`
  * Set this value to `FALSE` if you do not wish this feature to be available.
* `TRANSFORM::backup_dir`
  * Directory where backups will be placed prior to transformation
* `TRANSFORM::transform_dir`
  * Directory where transform classes are found
* `TRANSFORM::transform_file_field`
  * Name of the form field that is used if you want to upload a set of transforms

## Clicks
A class `FileCMS\Common\Stats\Clicks` was added as of version 0.2.1.
Records the following information into a CSV file:
* URL
* Date
* Time
* IP address
* Referrer
* 1
The "1" can be used in a spreadsheet to create totals by any of the other fields.
After logging in as the admin user, go to `/super/clicks`.
### Statistical Methods
The following methods are available for your use:
#### Clicks::get(string $click_fn) : array
Returns an array keyed and sorted by URL, with hit grand totals.
#### Clicks::get_by_page_by_day(string $click_fn) : array
Returns an array keyed and sorted by URL + Y-m-d, with hit totals for each day
#### Clicks::get_by_path(string $click_fn, string $path) : array
Returns the same as `get_by_page_by_day()` except that it filters results based on `$path`.
Use this to return stats on URLs such as `/practice/dr_tom/`.

## CSV
You can use a CSV file just like a database using the new `FileCMS\Common\Data\Csv` class
### public function getItemsFromCsv($key_field = NULL) : array
* Gets list of items from CSV
* @param string|array $key_field : header(s) to use as key; leave blank for numeric array
* @return array $select : `[key => value]`; key === practice_key; value = $row
### public function writeRowToCsv(array $post, array $csv_fields = `[]`) : bool
* Writes row to CSV
* @param array $post       : normally sanitized $_POST
* @param array $csv_fields : array of CSV headers; leave blank if headers not used
* @return bool             : TRUE if entry made OK
### public function findItemInCSV(string $search, bool $case = FALSE, bool $first = TRUE) : array
* Finds key in CSV file
* Assumes first row is headers unless $first === FALSE
* Stores contents of CSV file in $this->lines
* If found, sets $this->pos to the line number of the row found in $this->lines
* @param string $search  : any value that might be in the CSV file
* @param bool $case      : TRUE: case sensitive; FALSE: `[default]` case insensitive search
* @param bool $first_row : TRUE `[default]`: first row is headers; FALSE: first row is data
* @return array
### public function updateRowInCsv(string $search, array $data, array $csv_fields = [], bool $case = FALSE) : bool
* Updates row in CSV file
* If you don't supply $csv_fields, assumes no headers
* If no headers, update does delete and then insert
* @param string $search  : any value that might be in the CSV file
* @param array $data     : array of items to update
* @param array $csv_fields : array of fields names; leave blank if you don't use headers
* @param bool $case      : TRUE: case sensitive; FALSE: `[default]` case insensitive search
* @return bool             : TRUE if entry made OK
### public static function array2csv(array $data) : string
* This writes an array to CSV
* Credits: https://stackoverflow.com/questions/13108157/php-array-to-csv
* @param array $data : data to be written
* @return string $csv_string

## Change Log
### tag: v0.2.2 / v0.2.3
* 2022-04-22 DB: Finished testing modifications to Profile
* 2022-04-18 DB: Updated tests
* 2022-02-17 DB: Added option to prevent %%CARDS%% tags from being overwritten + implemented messages marker replacement for static HTML pages + expanded tests
### tag: v0.2.1
* 2022-02-16 DB: Updated tests + removed user key from Common\Security\Profile
### tag: v0.2.2
* 2022-02-13 DB: Fixed bug whereby you can never login
### tag: v0.2.4
2022-05-12 DB:
* Created `Email::trustedSend()` that allows you to directly call the core email send function
* Refactored `Email::confirmAndSend()` to call `trustedSend()`
* Added `$debug` option to facilitate testing and debugging
  * If set `TRUE` the email is not actually sent, and a `PHPMailer` instance is set to `Email::$phpMailer`
* Added `FileCMSTest\Common\Contact\EmailTest` test class
### tag: v0.2.5
* `FileCMS\Common\Contact\Email::trustedSend()`
  * Fixed bug whereby you were only allowed to send a string to `$cc` and `$bcc`
  * These inputs now allowed `mixed` types (expected string|array)
* Updated `FileCMSTest\Common\Contact\EmailTest` and `FileCMSTest\Common\Security\ProfileTest`
* `FileCMS\Common\Import\Import`
  * Added message if URL not found
  * Wrapped `file_get_contents($url)` call in `try` / `catch` to prevent expected errors from messing up test results
### tag: v0.2.6
* `FileCMS\Common\Contact\Email::trustedSend()`
  * Fixed bug whereby PHPMailer was always set to SMTP regardless of config settings
* Updated `FileCMSTest\Common\Contact\EmailTest`
  * Added tests to see if PHPMailer instance is set to "smtp" or "mail"
### tag: v0.2.8
* `FileCMS\Common\Contact\Email::confirmAndSend()`
  * Removed CAPTCHA verification logic and put into new `AntiSpam` class
* `FileCMS\Common\Contact\AntiSpam`
  * Added static function `verifyCaptcha($config)`
### tag: v0.2.9
* `FileCMS\Common\Security\Profile::verify()`
  * Removed type-hint from method signature for backward compatibility
### tag: v0.2.10
Date:   Sat Jun 25 16:42:03 2022 +0700
* 2022-06-25 DB: Enhancing security in Common\Contact\Email
* 2022-06-21 DB: Minor fix to Common\Security\Profile::verify()
### tag: v0.2.11
Date:   Sun Jul 10 12:50:24 2022 +0700
FileCMS\Common\Stats\Clicks:
Added new column
* `add()` includes `json_encode($_GET)`
* `raw_get()` does `json_decode()` on new column
* Updated `CLICK_HEADERS`
### tag: v0.2.12
Date: Thu Aug 18 10:33:15 2022 +0700
Modified FileCMS\Common\Stats\Clicks to track all URLs but allow users to add list of URLs to be ignored
### tag: v0.2.13
Date:   Thu Sep 8 09:54:26 2022 +0700
FileCMS\Common\Stats\Clicks:
* Added `get_by_page_by_month()`
* Fixed `get_by_path()`
FileCMS\Common\View\Table:
* New class
* Renders multi-dimensional array data
* `render_table()` produces &lt;table> structure with optional CSS classes for table, tr, th and td
* `render_as_div()` produces table structure using &lt;div class="row"> and &lt;div class="col">
### tag: v0.3.0
Date:   Thu Nov 3 11:09:53 2022 +0700
Added FileCMS\Common\Data\Csv
* See documentation above for method information
### tag: v0.3.1
FileCMS\Common\Security\Profile
* Removed the following methods:
  * `getAuthFileName()`
  * `build()`
* Removed the following constants:
  * `DEFAULT_AUTH_DIR`
  * `DEFAULT_AUTH_PREFIX`
  * `AUTH_FILE_TTL`
* Added these constants:
  * `PROFILE_KEY = __CLASS__;`
  * `PROFILE_DEF_SRC = 'HTTP_USER_AGENT';`
* `Profile::init()`
  * Revised to make backwards compatible
  * Always adds `$_SERVER[Profile::PROFILE_DEF_SRC]` to profile
  * If profile config keys are present, also adds these to profile
  * All keys must be valid `$_SERVER` keys
* `Profile::verify()`
  * Revised to make backwards compatible
  * Added config file as 2nd argument
  * Always checks value of `$_SESSION[Profile::PROFILE_KEY][Profile::PROFILE_DEF_SRC]`
  * If profile config keys are present, also confirms these values match
### tag: v0.3.2
`FileCMS\Common\Data\Csv`
* If CSV file doesn't exist, first Csv instance creates it
* If array of headers are supplied, first instance writes headers
* Added new method `deleteRowInCsv()`
* Slightly refactored `updateRowInCsv()` but functionality is the same
### tag: v0.3.3
`FileCMS\Common\Data\Csv`
* `writeRowToCsv()`
  * If you already have headers in the CSV file, this method will now allow you to write a row without using headers as the 2nd argument
* `getItemsFromCsv()`
  * Now allows you to read rows even if header count doesn't match
  * If your headers are > the count of the CSV headers, just appends empty strings
  * If your headers are < the count of the CSV headers, adds fake headers `Header_1`, `Header_2`, etc.
* Also updated tests:
  * `Common\Data\CsvTest`
  * `Common\Security\ProfileTest`
### tag: v0.3.4
Fixed bad `sprintf()` call in `FileCMS\Common\Data\Csv::getItemsFromCsv()`
### tag: v0.3.5
Arghhhh ... struggling with git
### tag: v0.3.6
#### `FileCMS\Common\Data\BigCsv`
* New class
* Handles files of any size
* Doesn't use `file()`
* Low memory consumption
* Not as fast as `Csv`
#### `FileCMS\Common\Data\CsvTrait`
* Hold common constants and methods
* Used by `Csv` and `BigCsv`
* Added new method `array_combine_whatever()`
  * If header count === data count runs `array_combine()`
  * If header count < data count starts creating headers `header_01`, `header_02` etc.
  * If header count > data count just assigns the headers to the data items and drops remaining headers
#### `FileCMS\Common\Data\Csv`
* Refactored slightly to use `CsvTrait`
* Added flag `$all` to `findItemInCSV()`
  * If set `FALSE` (default) only returns 1st match
  * If set `TRUE` returns all matching rows
### tag: v0.3.7
#### `FileCMS\Common\Data\*`
* Moved `CsvTrait::array2csv()` and `array_combine_whatever()` to `FileCMS\Common\Generic\Functions`
* Moved remaining `CsvTrait` functionality to `CsvBase`
* Removed `CsvTrait`
* Refactored `Csv` and `BigCsv` to extend `CsvBase`
#### `FileCMS\Common\Generic\Functions`
```
public static function array2csv(array $data) : string
```
* Writes an array to CSV
* Credits: https://stackoverflow.com/questions/13108157/php-array-to-csv

```
public static function array_combine_whatever(array $headers, array $data, string $prefix = '') : array
```
* Does the equivalent of `array_combine()` even if `count($headers)` doesn't match `count($data)`
### tag: v0.3.8
#### `FileCMS\Common\View\Html`
* Modified to accept a layout file with a `phtml` extension
  * Invokes `ob_start()` and does a PHP `require` on the layout file
  * Runs layout as a PHP script
  * Allows you to automate things like the copyright date (e.g. `<?= date('Y); ?>`
### tag: v0.3.9
#### Updated PHP Minimum Version
* Updated the minimum PHP version to PHP 8
### tag: v0.4.0
#### `FileCMS\Common\Install\UpgradeTinymce`
* New Composer script `composer upgrade-2026-07`, PHP equivalent of `upgrade_2026_07.sh` from `filecms-website`
* Switches the Super editor from CKEditor to TinyMCE: backs up affected files, requires `tinymce/tinymce`, copies its assets into `public/tinymce`, and downloads the updated `templates/super/edit.phtml` and `src/upload.php`
