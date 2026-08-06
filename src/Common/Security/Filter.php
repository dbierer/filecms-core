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

class Filter extends Base
{
    const DEFAULT_DATE = 'Y-m-d H:i:s';
    /**
     * Runs a set of filters against a string
     *
     * @param string $text : string to filter
     * @param array $callbacks : array of callbacks to call: all callbacks need to return string
     * @return string $text : the filtered text is returned
     */
    public static function runFilters(string $text, array $callbacks)
    {
        foreach ($callbacks as $method => $params) {
            // static:: (not self::) so a subclass overriding e.g. truncate() is still
            // dispatched to correctly when runFilters() is called on it
            $text = static::$method($text, $params);
        }
        return $text;
    }
    /**
     * Trims white space and "\n"
     *
     * @param string $text
     * @param array $params : not used
     * @return string $text
     */
    public static function trim(string $text, array $params = [])
    {
        return trim($text);
    }
    /**
     * Removes markup
     *
     * @param string $text
     * @param array $params : not used
     * @return string $text
     */
    public static function stripTags(string $text, array $params = [])
    {
        return strip_tags($text);
    }
    /**
     * Truncates string to XXX number of characters
     * Uses mb_substr()/mb_strlen(), not substr()/strlen(), so a multi-byte UTF-8
     * character (e.g. Khmer script) is never split mid-character, which would
     * otherwise corrupt the trailing byte sequence.
     *
     * @param string $text
     * @param array $params : looks for 'length' key
     * @return string $text
     */
    public static function truncate(string $text, array $params = [])
    {
        $length = (isset($params['length']))
                ? (int) $params['length']
                : mb_strlen($text, 'UTF-8');
        return mb_substr($text, 0, $length, 'UTF-8');
    }
    /**
     * Produces current date and time
     *
     * @param string $text
     * @param array $params : $params['format'] : date format string
     * @return string $text
     */
    public static function date(string $text, array $params = [])
    {
        $format = $params['format'] ?? self::DEFAULT_DATE;
        return date($format);
    }
}
