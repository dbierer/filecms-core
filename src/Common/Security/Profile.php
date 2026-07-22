<?php
namespace FileCMS\Common\Security;
/*
 * Author: doug@unlikelysource.com
 * License: BSD
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 * * Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above
 *   copyright notice, this list of conditions and the following disclaimer
 *   in the documentation and/or other materials provided with the
 *   distribution.
 * * Neither the name of the  nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

class Profile
{

    const PROFILE_KEY         = __CLASS__;
    const PROFILE_DEF_SRC     = 'HTTP_USER_AGENT';
    const PROFILE_AUTH_UNABLE = 'ERROR: unable to authenticate';

    public static $debug   = FALSE;
    public static $config  = [];

    /**
     * Builds initial login profile + sets up auth file
     *
     * @param array $config
     * @return void
     */
    public static function init(array $config) : void
    {
        $now  = date('Y-m-d');
        $info = [];
        $keys = $config['SUPER']['profile'] ?? [];
        if (!empty($keys)) {
            foreach ($keys as $idx)
                $info[$idx] = $_SERVER[$idx] ?? $now;
        }
        $info[self::PROFILE_DEF_SRC] = $_SERVER[self::PROFILE_DEF_SRC] ?? $now;
        $_SESSION[self::PROFILE_KEY] = $info;
    }
    /**
     * Removes auth file
     *
     * @return void
     */
    public static function logout() : void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            // Yes, I know that "@" usage is discouraged!
            // Only added this to prevent a "headers already sent" error
            // from making my tests fail!!!
            @setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain']);
        }
        session_destroy();
    }
    /**
     * Removes auth file
     *
     * @return void
     */
    public static function clear_auth() : void
    {
        unset($_SESSION[self::PROFILE_KEY]);
    }
    /**
     * Verifies profile against stored
     *
     * @param bool $log : set TRUE if you want to log verifications
     * @param array $config
     * @return bool TRUE if match | FALSE otherwise
     */
    public static function verify($log = FALSE, array $config = []) : bool
    {
        $ok  = FALSE;
        $now = date('Y-m-d');
        if (!empty($_SESSION[self::PROFILE_KEY])) {
            $info[self::PROFILE_DEF_SRC] = $_SERVER[self::PROFILE_DEF_SRC] ?? $now;
            $keys = $config['SUPER']['profile'] ?? [];
            if (!empty($keys)) {
                foreach ($keys as $idx)
                    $info[$idx] = $_SERVER[$idx] ?? $now;
            }
            $expected = 0;
            $actual   = 0;
            foreach ($_SESSION[self::PROFILE_KEY] as $key => $value) {
                if (!empty($info[$key])) {
                    $expected++;
                    $actual += (int) ($info[$key] === $value);
                }

            }
            $ok = ($expected === $actual);
        }
        if ($log)
            error_log(__METHOD__
                      . ':ACTUAL INFO:' . var_export($info, TRUE)
                      . ':STORED INFO:' . var_export(($_SESSION[self::PROFILE_DEF_SRC] ?? $now), TRUE));
        return $ok;
    }
}
