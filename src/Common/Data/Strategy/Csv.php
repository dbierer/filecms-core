<?php
namespace FileCMS\Common\Data\Strategy;
/*
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

/**
 * Stores/fetches information in designated file using PHP serialization
 */
use Throwable;
use SplFileObject;
use FileCMS\Common\Data\CsvBase;
use FileCMS\Common\Data\FormatStrategyInterface;
class Csv implements FormatStrategyInterface
{
    /**
     * Stores info into storage using fputcsv()
     *
     * @param string $fn   : filename
     * @param array $data  : data to be stored; forces $data to type "array"
     * @param bool $append : if TRUE, append to existing storage, otherwise overwrite
     * @return bool
     */
    public static function save(string $fn, $data, bool $append = TRUE) : bool
    {
        try {
            $obj = ($append)
                 ? new SplFileObject($fn, 'a')
                 : new SplFileObject($fn, 'w');
            $obj->setCsvControl(separator: CsvBase::DEFAULT_DELIM, 
                                enclosure: CsvBase::DEFAULT_ENCLOSURE, 
                                escape: CsvBase::DEFAULT_ESCAPE);
            if (!is_array($data)) $data = (array) $data;
            $result = $obj->fputcsv($data);
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . $t->getMessage());
            $result = FALSE;
        }
        return (bool) $result;
    }
    /**
     * Retrieves info from storage using fgetcsv()
     *
     * @param string $fn   : filename
     * @param bool $array  : parameter ignored: maintained for compatibility with other strategy classes
     * @param bool $erase  : if TRUE, erase existing storage after retrieval
     * @return array $data : array of mixed stored data
     */
    public static function fetch(string $fn, bool $array = TRUE, bool $erase = FALSE) : array
    {
        $data = [];
        if (!file_exists($fn)) return $data;
        try {
            $obj = new SplFileObject($fn, 'r');
            $obj->setCsvControl(separator: CsvBase::DEFAULT_DELIM, 
                                enclosure: CsvBase::DEFAULT_ENCLOSURE, 
                                escape: CsvBase::DEFAULT_ESCAPE);
            while (!$obj->eof()) {
                $row = $obj->fgetcsv();
                if (!empty($row) && $row[0] !== NULL) $data[] = $row;
            }
            if ($erase) {
                unset($obj);
                unlink($fn);
            }
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . $t->getMessage());
        }
        return $data;
    }
}
