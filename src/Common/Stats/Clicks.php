<?php
namespace FileCMS\Common\Stats;
/*
 * Records page clicks
 *
 * @todo: aggregate reports: (1) by IP, (2) by referrer, (3) by path (e.g. /practice/demo_silver)
 * @todo: add provision for user to alter args to f(get|put)csv() calls re: separator, enclosure, and escape
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

use SplFileObject;
use Throwable;
use FileCMS\Common\Data\CsvBase;
class Clicks
{
    public const HOME = '/home';
    public const MONTH_FMT = 'M Y';
    public const IGNORE_URLS = [];
    public const CLICK_HEADERS = ['url','date','time','ip','referrer','get','hits'];
    public static $discrepancies = [];
    /**
     * Records page counts by url, year, month day
     * Doesn't record clicks that match self::IGNORE_URLS
     *
     * @param string $url      : URL to record
     * @param string $click_fn : file name of CSV file containing clicks
     * @param array  $ignore   : list of URLs to ignore
     * @return boolean TRUE if OK; FALSE otherwise
     */
    public static function add(string $url, string $click_fn, array $ignore = []) : bool
    {
        $ok = FALSE;
        if (!empty($ignore) && in_array($url, $ignore)) return $ok;
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $refer = $_SERVER['HTTP_REFERER'] ?? 'Unknown';
            $get = (!empty($_GET)) ? json_encode($_GET) : '{}';
            $obj = new SplFileObject($click_fn, 'a');
            $ok = (bool) $obj->fputcsv([$url, date('Y-m-d'), date('H:i:s'), $ip, $refer,$get,1],
                                       separator: CsvBase::DEFAULT_DELIM,
                                       enclosure: CsvBase::DEFAULT_ENCLOSURE,
                                       escape:    CsvBase::DEFAULT_ESCAPE);
            unset($obj);
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . $t->getMessage() . ':' . $t->getTraceAsString());
        }
        return $ok;
    }
    /**
     * Raw get logic
     *
     * @param string $click_fn : file name of CSV file containing clicks
     * @param callable $callback : builds the key
     * @param mixed $options : options to send to callback
     * @return array $clicks : sorted by URL + key w/ totals
     */
    public static function raw_get(string $click_fn, callable $callback, $options = NULL) : array
    {
        $clicks = [];
        self::$discrepancies = [];
        if (!file_exists($click_fn)) return $clicks;
        try {
            $obj = new SplFileObject($click_fn, 'r');
            $num = count(self::CLICK_HEADERS);
            while (!$obj->eof()) {
                $row = $obj->fgetcsv(separator: CsvBase::DEFAULT_DELIM,
                                     enclosure: CsvBase::DEFAULT_ENCLOSURE,
                                     escape:    CsvBase::DEFAULT_ESCAPE);
                if (empty($row[0])) continue;
                if (count($row) !== $num) {
                    self::$discrepancies[] = $row;
                    continue;
                }
                $key = $callback($row, $options);
                if (!empty($key)) {
                    if (empty($clicks[$key])) {
                        $clicks[$key] = array_combine(self::CLICK_HEADERS, $row);
                    } elseif (empty($clicks[$key]['hits'])) {
                        $clicks[$key]['hits'] = 1;
                    } else {
                        $clicks[$key]['hits']++;
                    }
                }
            }
            asort($clicks);
            unset($obj);
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . $t->getMessage() . ':' . $t->getTraceAsString());
        }
        return $clicks;
    }
    /**
     * Returns array sorted by URL w/ totals
     *
     * @param string $click_fn : file name of CSV file containing clicks
     * @return array $clicks : sorted by URL + date w/ totals
     */
    public static function get(string $click_fn) : array
    {
        $callback = function ($row) { return $row[0] ?? FALSE; };
        return static::raw_get($click_fn, $callback);
    }
    /**
     * Returns array sorted by URL by day w/ totals
     *
     * @param string $click_fn : file name of CSV file containing clicks
     * @return array $clicks : sorted by URL + date w/ totals
     */
    public static function get_by_page_by_day(string $click_fn) : array
    {
        $callback = function ($row) {
            $val = FALSE;
            if (!empty($row[0]) && !empty($row[1])) {
                $val = $row[0] . '-' . $row[1];
            }
            return $val;
        };
        return static::raw_get($click_fn, $callback);
    }
    /**
     * Returns array sorted by URL by month w/ totals
     *
     * @param string $click_fn : file name of CSV file containing clicks
     * @return array $clicks : sorted by URL + date w/ totals
     */
    public static function get_by_page_by_month(string $click_fn) : array
    {
        $callback = function ($row) {
            $val = FALSE;
            if (!empty($row[0]) && !empty($row[1])) {
                $val = $row[0] . '-' . substr($row[1], 0, 7);
            }
            return $val;
        };
        return static::raw_get($click_fn, $callback);
    }
    /**
     * Returns array sorted by URL by day w/ totals
     *
     * @param string $click_fn : file name of CSV file containing clicks
     * @param string $path     : URL path
     * @return array $clicks   : sorted by URL + date w/ totals
     */
    public static function get_by_path(string $click_fn, string $path) : array
    {
        $callback = function ($row, $path) {
            if (empty($row[0])) return FALSE;
            if (stripos($row[0], $path) === FALSE) return FALSE;
            return $row[0] . '-' . substr($row[1], 0, 7);
        };
        return static::raw_get($click_fn, $callback, $path);
    }
}
