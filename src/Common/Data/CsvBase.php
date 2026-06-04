<?php
namespace FileCMS\Common\Data;
/*
 * Contains array methods that expand upon array_combine() and go from array to CSV
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
use ArrayIterator;
use FileCMS\Common\Generic\Functions;
class CsvBase
{
    const ERR_CSV   = 'ERROR: CSV file error';
    const HDR_PREFIX = 'header_%02d';
    const DEFAULT_EOL = PHP_EOL;
    const DEFAULT_DELIM = ',';  // $separator
    const DEFAULT_ESCAPE = '';  // as per recommendation in https://www.php.net/manual/en/splfileobject.fputcsv.php
    const DEFAULT_ENCLOSURE = '"';
    public $headers = [];
    public $csv_fn  = '';
    public $size    = 0;
    public function getItemsFromCsv($key_field = NULL, bool $first_row = TRUE) : array
    {
        $obj     = new SplFileObject($this->csv_fn, 'r');
        $obj->setCsvControl(separator: static::DEFAULT_DELIM, enclosure: static::DEFAULT_ENCLOSURE, escape: static::DEFAULT_ESCAPE);
        $select  = [];
        $headers = [];
        $count   = 0;
        $def_key = date('Ymd');
        $idx     = 0;
        while ($row = $obj->fgetcsv()) {
            if (empty($row) || count($row) <= 1) continue;
            // if $key_fields is NULL, just append $row
            if (empty($key_field)) {
                $select[] = $row;
                continue;
            }
            // draw headers from first line
            if (empty($headers) && $first_row) {
                $headers = $row;
                $this->headers = $headers;
            } else {
                $data = Functions::array_combine_whatever($headers, $row);
                // build key
                $key  = '';
                if (is_array($key_field)) {
                    foreach ($key_field as $name) {
                        if (!empty($data[$name])) {
                            $key .= trim($data[$name]) . '_';
                        } else {
                            $key .= $def_key . sprintf('%4d_',$idx++);
                        }
                    }
                    $key = substr($key, 0, -1);
                } else {
                    $key = trim($data[$key_field]);
                }
                $select[$key] = $data;
            }
        }
        ksort($select);
        return $select;
    }
}
