<?php
namespace FileCMSTest\Common\Data\Strategy;

use FileCMS\Common\Data\Strategy\Native;
use FileCMSTest\Common\Data\StorageBase;
class NativeTest extends StorageBase
{
    public function testSaveNativeString()
    {
        $data = ['A' => 111, 'B' => 222, 'C' => 333];
        Native::save(self::$tmpFn, $data);
        $expected = $data;
        $actual   = unserialize(trim(file_get_contents(self::$tmpFn)));
        $this->assertEquals($expected, $actual);
    }
    public function testFetchReturnsPhpArray()
    {
        $data = ['A' => 111, 'B' => 222, 'C' => 333];
        Native::save(self::$tmpFn, $data);
        $expected = $data;
        $actual   = Native::fetch(self::$tmpFn)[0];
        $this->assertEquals($expected, $actual);
    }
    public function testSavesMultipleNativeStrings()
    {
        $data = ['A' => 111, 'B' => 222, 'C' => 333];
        Native::save(self::$tmpFn, $data);
        Native::save(self::$tmpFn, $data);
        $expected = [$data, $data];
        $actual   = Native::fetch(self::$tmpFn);
        $this->assertEquals($expected, $actual);
    }
    public function testDoesNotSaveMultipleNativeStringsIfNotAppend()
    {
        $data = ['A' => 111, 'B' => 222, 'C' => 333];
        Native::save(self::$tmpFn, $data);
        Native::save(self::$tmpFn, $data, FALSE);
        $expected = $data;
        $actual   = Native::fetch(self::$tmpFn)[0];
        $this->assertEquals($expected, $actual);
    }
    public function testFetchErasesStorage()
    {
        $data = ['A' => 111, 'B' => 222, 'C' => 333];
        Native::save(self::$tmpFn, $data);
        Native::fetch(self::$tmpFn, TRUE, TRUE);
        $expected = FALSE;
        $actual   = file_exists(self::$tmpFn);
        $this->assertEquals($expected, $actual);
    }
}
