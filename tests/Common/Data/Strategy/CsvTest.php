<?php
namespace FileCMSTest\Common\Data\Strategy;

use FileCMS\Common\Data\CsvBase;
use FileCMS\Common\Data\Strategy\Csv;
use FileCMSTest\Common\Data\StorageBase;
class CsvTest extends StorageBase
{
    public function testSaveCsvString()
    {
        $data = [111, 222, 333];
        Csv::save(self::$tmpFn, $data);
        $tmp  = file_get_contents(self::$tmpFn);
        $expected = $data;
        $actual   = str_getcsv($tmp, separator: CsvBase::DEFAULT_DELIM, escape: CsvBase::DEFAULT_ESCAPE);
        $this->assertEquals($expected, $actual);
    }
    public function testFetchReturnsPhpArray()
    {
        $data = [111, 222, 333];
        Csv::save(self::$tmpFn, $data);
        $expected = $data;
        $actual   = Csv::fetch(self::$tmpFn)[0];
        $this->assertEquals($expected, $actual);
    }
    public function testSavesMultipleCsvStrings()
    {
        $data = [111, 222, 333];
        Csv::save(self::$tmpFn, $data);
        Csv::save(self::$tmpFn, $data);
        $expected = [$data, $data];
        $actual   = Csv::fetch(self::$tmpFn);
        $this->assertEquals($expected, $actual);
    }
    public function testDoesNotSaveMultipleCsvStringsIfNotAppend()
    {
        $data = [111, 222, 333];
        Csv::save(self::$tmpFn, $data);
        Csv::save(self::$tmpFn, $data, FALSE);
        $expected = $data;
        $actual   = Csv::fetch(self::$tmpFn)[0];
        $this->assertEquals($expected, $actual);
    }
    public function testFetchErasesStorage()
    {
        $data = [111, 222, 333];
        Csv::save(self::$tmpFn, $data);
        Csv::fetch(self::$tmpFn, TRUE, TRUE);
        $expected = FALSE;
        $actual   = file_exists(self::$tmpFn);
        $this->assertEquals($expected, $actual);
    }
}
