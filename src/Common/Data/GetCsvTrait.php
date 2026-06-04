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
trait GetCsvTrait
{
    /**
     * Sets the CSV controls to maintain PHP 8.4+ compliance
     * 
     * @param string $csv_fn : filename of CSV file
     * @param string $mode   : 
     * @param string $delim  : delimiter 
     * @param string $enc    : enclosure (")
     * @param string $esc    : escape (\)
     * @param string $eol    : PHP_EOL
     * @return SplFileObject
     */
    public static function getCsvObject(string $csv_fn,
                                 string $mode = 'r',
                                 string $delim = '',
                                 string $enc   = '',   // enclosure
                                 string $esc   = '',
                                 string $eol   = '') : SplFileObject
    {
        $delim = (!empty($delim)) ? $delim : static::DEFAULT_DELIM;
        $enc   = (!empty($enc))   ? $enc   : static::DEFAULT_ENCLOSURE;
        $esc   = (!empty($esc))   ? $esc   : static::DEFAULT_ESCAPE;
        $eol   = (!empty($eol))   ? $eol   : static::DEFAULT_EOL;   // not used as of yet
        $obj = new SplFileObject($csv_fn, $mode);
        $obj->setCsvControl(separator: $delim, enclosure: $enc, escape: $esc);
        return $obj;
    }
}
