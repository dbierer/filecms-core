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
use FileCMS\Common\Data\FormatStrategyInterface;
class Native implements FormatStrategyInterface
{
    /**
     * Stores info into storage using PHP serialize
     *
     * @param string $fn   : filename
     * @param mixed $data  : data to be stored
     * @param bool $append : if TRUE, append to existing storage, otherwise overwrite
     * @return bool
     */
    public static function save(string $fn, $data, bool $append = TRUE) : bool
    {
        try {
            $serial = serialize($data);
            $serial .= PHP_EOL;
            $result = ($append)
                    ? file_put_contents($fn, $serial, FILE_APPEND)
                    : file_put_contents($fn, $serial);
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . $t->getMessage());
            $result = FALSE;
        }
        return (bool) $result;
    }
    /**
     * Retrieves info from storage using PHP unserialize
     *
     * @param string $fn   : filename
     * @param bool $array  : if TRUE, returns data as array
     * @param bool $erase  : if TRUE, erase existing storage after retrieval
     * @return array $data : array of mixed stored data
     */
    public static function fetch(string $fn, bool $array = TRUE, bool $erase = FALSE) : array
    {
        $data = [];
        if (!file_exists($fn)) return $data;
        try {
            $lines = file($fn);
            foreach ($lines as $contents)
                $data[] = unserialize(trim($contents));
            if ($erase) unlink($fn);
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . $t->getMessage());
        }
        return $data;
    }
}
