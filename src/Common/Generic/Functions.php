<?php
namespace FileCMS\Common\Generic;
/*
 * Common functions used throughout the framework
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
use ArrayIterator;
use FileCMS\Common\Data\CsvBase;
class Functions
{
    const HDR_PREFIX = 'header_%02d';
    /**
     * This writes an array to CSV
     * Credits: https://stackoverflow.com/questions/13108157/php-array-to-csv
     *
     * @param array $data : data to be written
     * @return string $csv_string
     */
    public static function array2csv(array $data) : string
    {
        $f = fopen('php://memory', 'w+');
        fputcsv($f, 
                $data, 
                separator: CsvBase::DEFAULT_DELIM, 
                enclosure: CsvBase::DEFAULT_ENCLOSURE,
                escape: CsvBase::DEFAULT_ESCAPE);
        rewind($f);
        $str = trim(stream_get_contents($f));
        fclose($f);
        return $str;
    }
    /**
     * Does array_combine() for unequal header count
     * NOTE: if 2nd arg is an associative array, headers will get stripped
     *
	 * @todo Move `array_combine_whatever()` to a generic class w/ static usage
	 * @todo Set up `CsvTrait::array_combine_whatever()` to make a static call to this generic class
     * @param array $headers : desired headers
     * @param array $data    : numberic array of data to be combined with headers
     * @param string $prefix : prefix used for substitute headers (if count($headers) < count($data)
     *                       : IMPORTANT: $prefix must be an sprintf() format string!
     * @return array $combined : associative array
     */
    public static function array_combine_whatever(array $headers, array $data, string $prefix = '') : array
    {
        $combined = [];
		$prefix   = $prefix ?: static::HDR_PREFIX;
		// add sprintf() format code if "%" missing
		if (strpos($prefix, '%') === FALSE) $prefix .= '_%02d';
		// if header count matches data count, just use array_combine()
        if (count($headers) === count($data)) {
            $combined = array_combine($headers, $data);
        } else {
            $iter = new ArrayIterator(array_values($data));
            // otherwise, if header count is short, combine values until you run out of headers
            if (count($headers) < $iter->count()) {
                foreach ($headers as $key) {
                    $combined[$key] = $iter->current();
                    $iter->next();
                }
                $pos = 1;
                // now start creating substitute headers
                while ($iter->valid()) {
                    $key = sprintf($prefix, $pos++);
                    $combined[$key] = $iter->current();
                    $iter->next();
                }
            } else {
				// if header count is too long, assign data to headers until you run out of data
                foreach ($iter as $value) {
                    $combined[current($headers)] = $value;
                    next($headers);
                }
            }
        }
        return $combined;
    }
}
