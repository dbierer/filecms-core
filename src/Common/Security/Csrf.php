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

class Csrf
{

    const SESSION_KEY = __CLASS__;

    /**
     * Returns the current session's CSRF token, generating one on first call.
     * One token per session -- reused across every form/step in a flow rather than
     * rotated per-request, so a multi-step form (e.g. a wizard) doesn't invalidate
     * itself between steps.
     *
     * @return string
     */
    public static function token() : string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Verifies a submitted token against the session's token using hash_equals()
     * (constant-time comparison, avoids a timing side-channel that a byte-by-byte
     * === comparison would leak).
     *
     * @param mixed $submitted : usually $_POST['csrf_token'] ?? null
     * @return bool TRUE if a token exists for this session and matches
     */
    public static function verify($submitted) : bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        return $expected !== '' && is_string($submitted) && hash_equals($expected, $submitted);
    }

    /**
     * Renders a ready-to-use hidden form field carrying the current token, so
     * templates don't each need to know the session key / field-naming convention.
     *
     * @param string $fieldName : defaults to "csrf_token" -- verify() itself doesn't
     *     care what the field is named, only what value is passed to it, so this is
     *     purely for templates that want a single call to emit the input tag
     * @return string : e.g. <input type="hidden" name="csrf_token" value="..." />
     */
    public static function field(string $fieldName = 'csrf_token') : string
    {
        return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="'
             . htmlspecialchars(self::token()) . '" />';
    }
}
