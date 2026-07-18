<?php
// change settings as needed and then copy to /src/config/config.php on your main website
// use this skeleton project to set up your website (https://github.com/dbierer/filecms-website)
// unlikelysource/filecms-core also contains a copy of this file
$config = [
    'CARDS'  => 'cards',
    'LAYOUT' => BASE_DIR . '/templates/layout/layout.html',
    'HOME'   => 'home.phtml',   // default home page
    'HOST'   => '',
    'DELIM'  => '%%',
    'CONTENTS' => '%%CONTENTS%%',
    'HTML_DIR' => HTML_DIR,
    'CLICK_CSV' => BASE_DIR . '/logs/clicks.csv',
    'MSG_MARKER'  => '<!-- %%MESSAGES%% -->',
    'CONTACT_LOG' => BASE_DIR . '/logs/contact.log',
    // use '' for CACHE if you want to disable it
    'CACHE'  => BASE_DIR . '/data/cache.txt',
    'CAPTCHA' => [
        'input_tag_name' => 'phrase',
        'sess_hash_key'  => 'hash',
        'font_file'      => SRC_DIR . '/fonts/FreeSansBold.ttf',
        'img_dir'        => BASE_DIR . '/public/img/captcha',
        'num_bytes'      => 3,  // each byte == 2 characters
        'rotate_min'     => -50,
        'rotate_max'     => 50,
        'line_min'       => 5,
        'line_max'       => 50,
        'dot_min'        => 20,
        'dot_max'        => 60,
    ],
    'META' => [
        'default' => [
            'title' => 'FileCMS',
            'keywords' => 'php, html, simple',
            'description'  => 'Once installed all you need to do is to upload HTML snippets into the site templates folder',
        ],
    ],
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
        'tinymce'      => [
            'width'  => '100%',
            'height' => 400,
        ],
        'super_url'  => '/super',                // IMPORTANT: needs to be a subdir off the "super_dir" setting
        'super_dir'  => BASE_DIR . '/templates', // IMPORTANT: needs to have a subdir === "super_url" setting
        'super_menu' => BASE_DIR . '/templates/layout/super_menu.html',
        'backup_dir' => BASE_DIR . '/backups',
        'backup_cmd' => BASE_DIR . 'zip -r %%BACKUP_FN%% %%BACKUP_SRC%%',
    ],
    /*
     * File based storage
     * Can be used if you need persistent storage
     *
     * If "storage_fmt" param === "php" uses native PHP serialization
     * If "storage_fmt" param === "json" uses JSON encoding
     * If "storage_fmt" param === "csv" uses CSV encoding
     */
    'STORAGE' => [
        'storage_fmt' => 'csv',  // can be php|json|csv
        'storage_dir' => BASE_DIR . '/data',
        'storage_fn'  => 'contacts.txt',
    ],
    /*
     * These settings are primarily used for the email contact form
     * Sample form can be found at /templates/site/contact.phtml
     */
    'COMPANY_EMAIL' => [
        'to'   => 'me@company.com',
        'cc'   => '',
        'from' => 'office@company.com',
        'mx_check' => TRUE, // if set TRUE, runs "checkdnsrr()" for "MX" records
        'default_subject' => 'Need a Quote',
        'SUCCESS' => '<span style="color:green;font-weight:700;">Thanks!  Your request has been sent.</span>',
        'ERROR'   => '<span style="color:red;font-weight:700;">Sorry!  Your question, comment or request info was not received.</span>',
        'phpmailer' => [
            'html'          => TRUE,                // set FALSE if you want plain text
            'smtp'          => FALSE,               // Use SMTP (true) or PHP Mail() function (false)
            'smtp_host'     => 'REPL_SMTP_HOST',    // SMTP server address - URL or IP
            'smtp_port'     => 587,                 // 25 (standard), 465 (SSL), or 587 (TLS)
            'smtp_auth'     => TRUE,                // SMTP Authentication - PLAIN
            'smtp_username' => 'REPL_SMTP_USERNAME',// Username if smtp_auth is true
            'smtp_password' => 'REPL_SMTP_PASSWORD',// Password if smtp_auth is true
            'smtp_secure'   => 'tls',               // Supported SMTP secure connection - 'none, 'ssl', or 'tls'
        ],
        // sprintf() pattern (mainly for non-HTML emails)
        'pattern' => '%-20s : %s' . "\n",
        // as of 2022-06-22 the only aspect of "fields" are the keys themselves (e.g. name, email, phone, etc.)
        // the rest is for future use
        'fields' => [
            // Key is used for the "name" and "id" attributes of the HTML input tags
            'name'    => [
                // Optional: appears next to the HTML input tag
                'label' => 'Your Name',
                // "attributes" are placed inside the HTML input tag
                'attributes' => [
                    'type' => 'text',
                    'class' => 'form-control',
                    'required' => '""',
                    'placeholder' => 'John Doe',
                ],
                // "validator" and "filter" are used to sanitize received form data
                // "validator => KEY" : KEY must be a method inside Common\Security\Validation
                'validator' => [
                    'alpha' => ['allowed' => [' ']],
                ],
                // "filter => KEY" : KEY must be a method inside Common\Security\Filter
                'filter' => [
                    'trim'      => [],
                    'stripTags' => [],
                    'truncate'  => ['length' => 64],
                ],
            ],
            'email'   => [
                'label' => 'Your Email Address',
                'attributes' => [
                    'type' => 'email',
                    'class' => 'form-control',
                    'required' => '""',
                    'placeholder' => 'john@gmail.com',
                ],
                'validator' => [
                    'email' => [],
                ],
                'filter' => [
                    'trim'      => [],
                    'stripTags' => [],
                    'truncate'  => ['length' => 255],
                ],
            ],
            'phone'   => [
                'label' => 'Your Phone Number (optional)',
                'attributes' => [
                    'type' => 'text',
                    'class' => 'form-control',
                    'required' => '""',
                    'placeholder' => '+1-800-643-4500',
                ],
                'validator' => [
                    'phone' => ['allowed' => ['+','-',' ']],
                ],
                'filter' => [
                    'trim'      => [],
                    'stripTags' => [],
                    'truncate'  => ['length' => 32],
                ],
            ],
            'subject' => [
                'label' => 'What\'s This About?',
                'attributes' => [
                    'type' => 'text',
                    'class' => 'form-control',
                    'required' => '""',
                    'placeholder' => 'Subject line for the email you wish to send',
                ],
                'validator' => [
                    'alpha' => ['allowed' => [',','-',' ',':']],
                ],
                'filter' => [
                    'trim'      => [],
                    'stripTags' => [],
                    'truncate'  => ['length' => 64],
                ],
            ],
            'source'  => [
                'label' => 'URL of Where You Heard About Us',
                'attributes' => [
                    'type' => 'text',
                    'class' => 'form-control',
                    'placeholder' => 'https://unlikelysource.com',
                ],
                'validator' => [
                    'url' => [],
                ],
                'filter' => [
                    'trim'      => [],
                    'stripTags' => [],
                    'truncate'  => ['length' => 64],
                ],
            ],
            'message' => [
                'label' => 'What Would You Like to Tell Us?',
                'attributes' => [
                    'type' => 'textarea',
                    'rows' => '4',
                    'placeholder' => 'Hello, I would like to ...',
                ],
                'filter' => [
                    'trim'      => [],
                    'stripTags' => [],
                    'truncate'  => ['length' => 4096],
                ],
            ],
            'created' => [
                'attributes' => [
                    'type' => 'hidden',
                ],
                'filter' => [
                    'date' => ['format' => 'Y-m-d H:i:s'],
                ],
            ],
        ],
    ],
    'UPLOADS' => [
        'restrict_size' => TRUE,    // set to FALSE to ignore size restrictions
        'create_thumbs' => FALSE,   // set TRUE to enable automatic thumbnail creation
        'path_exclude'  => [],      // directory path fragment to exclude from list of images
        'img_width'   => 1000,
        'img_height'  => 1000,
        'img_size'    => 3000000,
        'allowed_ext' => ['jpg','jpeg','png','gif','bmp','svg'],
        'img_dir'  => BASE_DIR . '/public/images',
        'img_url'     => '/images',
        'thumb_dir'   => BASE_DIR . '/public/thumb',
        'thumb_url'   => '/thumb',
        'allowed_types' => ['image/'],  // allowed MIME type prefix
    ],
    'TRANSFORM' => [
        'enable' => TRUE,                      // change this to FALSE to disable this feature
        'transform_dir' => SRC_DIR . '/Transform',
        'transform_file_field' => 'transform_file',  // IMPORTANT: the form must use this name
    ],
    'IMPORT' => [
        'enable' => TRUE,                      // change this to FALSE to disable this feature
        'delim_start'  => '<body>',            // marks beginning of contents to extract
        'delim_stop'   => '</body>',           // marks end of contents to extract
        'import_file_field' => 'import_file',  // IMPORTANT: the form must use this name
        // array of trusted URLs
        'trusted_src' => ['https://test.unlikelysource.com'],
        // add as many transforms as desired
        // you can also add your own anonymous functions as transforms as long as the signature
        // matches the one specified by FileCMS\Transform\TransformInterface
        'transform' => [
            /*
            'clean' => [
                'callback' => 'FileCMS\Transform\Clean',
                'params' => ['bodyOnly' => TRUE],
                'description' => 'Use Tidy extension to clean HTML',
            ],
            'remove_block' => [
                'callback' => 'FileCMS\Transform\RemoveBlock',
                'params' => ['start' => '<tr height="20">','stop' => '</tr>','items' => ['bkgnd_tandk.gif','trans_spacer50.gif','bkgnd_tanlt.gif']],
                'description' => 'Remove block starting with &lt;tr height="20"&gt;',
            ],
            'attribs_remove' => [
                'callback' => 'FileCMS\Transform\RemoveAttributes',
                'params' => ['attributes' => ['width','height','align','valign']],
                'description' => 'Remove these attributes: width,height,style,class',
            ],
            'replace_regex' => [
                'callback' => 'FileCMS\Transform\ReplaceRegex',
                'params'   => ['regex' => ['!https://test.com(.*?).html!','!https://www.test.com(.*?).html!'], 'replace' => '$1'],
                'description' => 'replace "https://test.com/xxx" with "/xxx"',
            ],
            */
            'table_to_row_col_div' => [
                'callback' => 'FileCMS\Transform\TableToDiv',
                'params' => ['tr' => 'row', 'td' => 'col', 'th' => 'col bold'],
                'description' => 'Convert HTML table tags to div row/col classes',
            ],
        ],
    ],
];
return $config;
