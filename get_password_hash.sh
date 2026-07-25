#!/bin/bash
#
# Produces a hashed version of the user-supplied password
#
if [[ "$1" == "" ]]; then
    echo "Usage: get_password_hash.sh PLAIN_TEXT"
    exit 1
fi
php -r "echo password_hash('$1', PASSWORD_BCRYPT) . PHP_EOL;"
echo "Edit the src/config/config.php file, substituting next to the "
echo "    'password' login option under the 'SUPER' or 'alt_logins' keys."
