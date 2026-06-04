<?php
namespace FileCMSTest\Common\Data;

use FileCMS\Common\Data\Strategy\{Csv,Json,Native};
use FileCMS\Common\Data\{Storage,CsvBase};
class StorageTest extends StorageBase
{
    public function testConstructCreatesConfigProperly()
    {
        $expected = $this->config['STORAGE'];
        $actual   = $this->storage->config;
        $this->assertEquals($expected, $actual);
    }
    public function testStrategyDefaultsToCsv()
    {
        unset($this->storage->config['storage_fmt']);
        $expected = Csv::class;
        $actual   = $this->storage->strategy;
        $this->assertEquals($expected, $actual);
    }
    public function testSetStrategyWorksForJson()
    {
        $this->storage->setStrategy(Storage::FMT_JSON);
        $expected = Json::class;
        $actual   = $this->storage->strategy;
        $this->assertEquals($expected, $actual);
    }
    public function testSaveCsvString()
    {
        $data = [111, 222, 333];
        $this->storage->save($data);
        $tmp  = file_get_contents(self::$tmpFn);
        $expected = $data;
        $actual   = str_getcsv($tmp, separator: CsvBase::DEFAULT_DELIM, escape: CsvBase::DEFAULT_ESCAPE);
        $this->assertEquals($expected, $actual);
    }
    public function testFetchReturnsPhpArray()
    {
        $data = [111, 222, 333];
        $this->storage->save($data);
        $expected = $data;
        $actual   = $this->storage->fetch(self::$tmpFn)[0];
        $this->assertEquals($expected, $actual);
    }
}
