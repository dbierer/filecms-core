<?php
namespace FileCMSTest\Common\Generic;

use FileCMS\Common\Data\CsvBase;
use FileCMS\Common\Generic\Functions;
use PHPUnit\Framework\TestCase;
class FunctionsTest extends TestCase
{
    // public static function array2csv(array $data) : string
    public function testArray2csv()
    {
        $arr = ['AAA',111,222.222,'This is a "test"',TRUE];
        $expected = 'AAA,111,222.222,"This is a ""test""",1';
        $actual   = Functions::array2csv($arr);
        $this->assertEquals($expected, $actual);
    }
    // public static function array_combine_whatever(array $headers, array $data, string $prefix = '') : array
    public function testArrayCombineWhateverWorksWhenCountIsSame()
    {
        $headers = ['A','B','C'];
        $data    = [1, 2, 3];
        $expected = ['A' => 1,'B' => 2,'C' => 3];;
        $actual = Functions::array_combine_whatever($headers, $data);
        $this->assertEquals($expected, $actual);
    }
    public function testArrayCombineWhateverStripsHeadersIfDataAssocArray()
    {
        $headers = ['A','B','C'];
        $data    = ['X' => 1, 'Y' => 2, 'Z' => 3];
        $expected = ['A' => 1,'B' => 2,'C' => 3];;
        $actual = Functions::array_combine_whatever($headers, $data);
        $this->assertEquals($expected, $actual);
    }
    public function testArrayCombineWhateverWorksIfCountHeadersMoreThanData()
    {
        $headers = ['A','B','C','D'];
        $data    = [1, 2, 3];
        $expected = ['A' => 1,'B' => 2,'C' => 3];;
        $actual = Functions::array_combine_whatever($headers, $data);
        $this->assertEquals($expected, $actual);
    }
    public function testArrayCombineWhateverWorksIfCountHeadersLessThanData()
    {
        $headers = ['A','B','C'];
        $data    = [1, 2, 3, 4];
        $expected = ['A' => 1,'B' => 2,'C' => 3, 'header_01' => 4];;
        $actual = Functions::array_combine_whatever($headers, $data);
        $this->assertEquals($expected, $actual);
    }
    public function testArrayCombineWhateverUserSuppliedHeader()
    {
        $headers = ['A','B','C'];
        $data    = [1, 2, 3, 4];
        $expected = ['A' => 1,'B' => 2,'C' => 3, 'test_01' => 4];;
        $actual = Functions::array_combine_whatever($headers, $data, 'test_%02d');
        $this->assertEquals($expected, $actual);
    }
    public function testArrayCombineWhateverWorksIfUserSuppliedHeaderNotSprintfFormatString()
    {
        $headers = ['A','B','C'];
        $data    = [1, 2, 3, 4];
        $expected = ['A' => 1,'B' => 2,'C' => 3, 'test_01' => 4];;
        $actual = Functions::array_combine_whatever($headers, $data, 'test');
        $this->assertEquals($expected, $actual);
    }
}
