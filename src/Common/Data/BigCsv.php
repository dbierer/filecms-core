<?php
namespace FileCMS\Common\Data;
/*
 * Version of FileCMS\Common\Data\Csv but can be used for large files (or files of any size)
 * Treats CSV file like database
 * Lets you search, update and delete rows
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
use Throwable;
use Exception;
use SplFileObject;
use FileCMS\Common\Contact\Email;
use FileCMS\Common\Generic\Messages;
class BigCsv extends CsvBase
{
    /**
     * If CSV file doesn't exist, creates the file
     * If $headers aren't empty, also writes out headers
     *
     * @param string $csv_fn : filename of CSV file
     * @param array $headers : optional array of headers
     * @return void
     */
    public function __construct(string $csv_fn, array $headers = [])
    {
        $this->csv_fn = $csv_fn;
        $this->headers = $headers;
        if (file_exists($csv_fn)) {
            $this->size = filesize($csv_fn);
        } else {
            if (empty($headers)) {
                touch($csv_fn);
                $this->size = 0;
            } else {
                $obj = new SplFileObject($this->csv_fn, 'w');
                $obj->fputcsv($headers, separator: static::DEFAULT_DELIM, enclosure: static::DEFAULT_ENCLOSURE, escape: static::DEFAULT_ESCAPE);
                unset($obj);
                $this->size = strlen(implode(',', $headers));
            }
        }
    }
    /**
     * Writes row to CSV
     *
     * @param array $post       : normally sanitized $_POST
     * @param array $csv_fields : array of CSV headers; leave blank if headers not used
     * @param string $delim     : default == CsvBase::DEFAULT_DELIM (separator parameter)
     * @return bool             : TRUE if entry made OK
     */
    public function writeRowToCsv(array $post, array $csv_fields = [], ?string $delim = NULL) : bool
    {
        $ok = FALSE;
        $delim ??= static::DEFAULT_DELIM;
        try {
            $obj = new SplFileObject($this->csv_fn, 'a');
            if (empty($csv_fields)) {
                $data = $post;
            } else {
                // if size === 0 write out headers
                if ($this->size === 0) {
                    $obj->fputcsv($csv_fields, separator: $delim, enclosure: static::DEFAULT_ENCLOSURE, escape: static::DEFAULT_ESCAPE);
                    $this->size = strlen(implode(',', $csv_fields));
                }
                // align $_POST data to csv fields
                $data = [];
                foreach ($csv_fields as $name)
                    $data[$name] = $post[$name] ?? '';
            }
            $ok = (bool) $obj->fputcsv(array_values($data), separator: static::DEFAULT_DELIM, enclosure: static::DEFAULT_ENCLOSURE, escape: static::DEFAULT_ESCAPE);
            unset($obj);
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . get_class($t) . ':' . $t->getMessage() . ':' . $t->getTraceAsString());
        }
        return $ok;
    }
    /**
     * Finds key in CSV file
     * Assumes first row is headers unless $first === FALSE
     * Stores contents of CSV file in $this->lines
     * If found, sets $this->pos to the line number of the row found in $this->lines
     *
     * @param string $search  : any value that might be in the CSV file
     * @param bool $case      : TRUE: case sensitive; FALSE: [default] case insensitive search
     * @param bool $first     : TRUE [default]: first row is headers; FALSE: first row is data
     * @param string $delim   : default == CsvBase::DEFAULT_DELIM (separator parameter)
     * @return array
     */
    public function findItemInCSV(string $search,
                                  bool $case = FALSE,
                                  bool $first = TRUE,
                                  bool $all = FALSE,
                                  ?string $delim = NULL) : array
    {
        $delim ??= static::DEFAULT_DELIM;
        $func  = ($case) ? 'strpos' : 'stripos';
        $found = [];
        $hdr_count = 0;
        $this->headers = [];
        $obj = new SplFileObject($this->csv_fn, 'r');
        while (!$obj->eof()) {
            $row = $obj->fgets();
            if ($first && empty($this->headers)) {
                $this->headers = str_getcsv($row, $delim, enclosure: static::DEFAULT_ENCLOSURE, escape: static::DEFAULT_ESCAPE);
                $hdr_count = count($this->headers);
            } else {
                if ($func($row, $search) !== FALSE) {
                    $found = str_getcsv($row, $delim, enclosure: static::DEFAULT_ENCLOSURE, escape: static::DEFAULT_ESCAPE);
                    if ($first && $hdr_count === count($found)) {
                        $found = array_combine($this->headers, $found);
                    }
                    break;
                }
            }
        }
        return $found;
    }
    /**
     * Deletes row in CSV file
     * If you don't supply $csv_fields, assumes no headers
     * If $overwrite is set TRUE (default), CSV is rewritten minus deleted row
     * If $overwrite is set FALSE, CSV::$lines reflects deletion, but CSV file itself remains the same
     *
     * @param string $search    : any value that might be in the CSV file
     * @param array $csv_fields : array of fields names; leave blank if you don't use headers
     * @param bool $case        : TRUE: case sensitive; FALSE: [default] case insensitive search
     * @param bool $overwite    : TRUE: write contents (minus deleted row) back to CSV
     * @param string $tmp_fn    : temporary file
     * @param bool $erase_tmp   : TRUE: erase temp file when everything's done
     * @return array            : returns deleted row; if row not found, returns empty array
     */
    public function deleteRowInCsv(string $search,
                                   array $csv_fields = [],
                                   bool $case = FALSE,
                                   bool $overwrite = TRUE,
                                   string $tmp_fn = '',
                                   bool $erase_tmp = TRUE) : array
    {
        $func  = ($case) ? 'strpos' : 'stripos';
        $first = !empty($csv_fields);   // if $csv_fields is not empty === first row is headers
        $found = [];
        $hdr_count = 0;
        $this->headers = [];
        // get temp file if none supplied
        if (empty($tmp_fn))
            $tmp_fn = tempnam(sys_get_temp_dir(), 'temp_' . date('_Y-m-d_'));
        $tmp = new SplFileObject($tmp_fn, 'w+');
        $obj = new SplFileObject($this->csv_fn, 'r');
        while (!$obj->eof()) {
            $row = $obj->fgets();
            if ($first && empty($this->headers)) {
                $this->headers = str_getcsv($row, separator: static::DEFAULT_DELIM, enclosure: static::DEFAULT_ENCLOSURE, escape: static::DEFAULT_ESCAPE);
                $hdr_count = count($this->headers);
                $tmp->fwrite($row);
            } else {
                // if nothing is found yet, store found row but don't write it to tmp
                if ($func($row, $search) !== FALSE && empty($found)) {
                    $found = str_getcsv($row, separator: static::DEFAULT_DELIM, enclosure: static::DEFAULT_ENCLOSURE, escape: static::DEFAULT_ESCAPE);
                    if ($first && $hdr_count === count($found))
                        $found = array_combine($this->headers, $found);
                } else {
                    $tmp->fwrite($row);
                }
            }
        }
        unset($obj);
        // write CSV back out if $overwrite flag is set
        if ($overwrite) {
            $tmp->rewind();
            $obj = new SplFileObject($this->csv_fn, 'w');
            while (!$tmp->eof()) {
                $row = $tmp->fgets();
                $obj->fwrite($row);
            }
        }
        unset($tmp);
        unset($obj);
        // erase tmp file if flag set
        if ($erase_tmp) unlink($tmp_fn);
        return $found;
    }
    /**
     * Updates row in CSV file
     * If you don't supply $csv_fields, assumes no headers
     * If no headers, update does delete and then insert
     *
     * @param string $search  : any value that might be in the CSV file
     * @param array $data     : array of items to update
     * @param array $csv_fields : array of fields names; leave blank if you want a simple replace
     * @param bool $case      : TRUE: case sensitive; FALSE: [default] case insensitive search
     * @return bool             : TRUE if entry made OK
     */
    public function updateRowInCsv(string $search, array $data, array $csv_fields = [], bool $case = FALSE) : bool
    {
        $row = $this->deleteRowInCsv($search, $csv_fields, $case);
        if (empty($row)) return FALSE;  // means row not found
        // update $row with $data if $csv_fields not empty
        if (empty($csv_fields)) {
			$row = $data;
		} else {
			foreach ($csv_fields as $key)
				if (!empty($data[$key])) $row[$key] = $data[$key];
		}
        // write CSV back out
        return (bool) (new SplFileObject($this->csv_fn, 'a'))->fputcsv($row, separator: static::DEFAULT_DELIM, enclosure: static::DEFAULT_ENCLOSURE, escape: static::DEFAULT_ESCAPE);
    }
}
