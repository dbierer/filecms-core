<?php
namespace FileCMSTest\Common\Stats;

use DateTime;
use SplFileObject;
use FileCMS\Common\Stats\Clicks;
use FileCMS\Common\Data\CsvBase;
use PHPUnit\Framework\TestCase;
class ClicksTest extends TestCase
{
    public $config = [];
    public $click_fn = '';
    public function setUp() : void
    {
        $_GET = [];
        $this->config = include BASE_DIR . '/tests/config/test.config.php';
        $this->click_fn = BASE_DIR . '/tests/logs/click_test.csv';
        if (file_exists($this->click_fn)) unlink($this->click_fn);
    }
    public function testAddReturnsTrueIfUrlIsSlash()
    {
        $url = '/';
        $expected = TRUE;
        $actual   = Clicks::add($url, $this->click_fn);
        $this->assertEquals($expected, $actual);
    }
    public function testAddCreatesCSVFile()
    {
        $url = '/test';
        Clicks::add($url, $this->click_fn);
        $expected = TRUE;
        $actual   = file_exists($this->click_fn);
        $this->assertEquals($expected, $actual);
    }
    public function testAddStoresGetParams()
    {
        $_GET = [
            'AAA' => 1,
            'BBB' => 2,
        ];
        $url = '/test';
        Clicks::add($url, $this->click_fn);
        $contents = file_get_contents($this->click_fn);
        $expected = TRUE;
        $actual   = str_contains($contents, '{""AAA"":1,""BBB"":2}');
        $this->assertEquals($expected, $actual);
    }
    public function testAddIgnoresUrlsCorrectly()
    {
        $_GET = [
            'AAA' => 1,
            'BBB' => 2,
        ];
        $url = '/test';
        $ignore = [$url];
        Clicks::add($url, $this->click_fn,$ignore);
        $expected = FALSE;
        $actual   = file_exists($this->click_fn);
        $this->assertEquals($expected, $actual);
    }
    public function testAddReturnsFalseIfUrlIsIgnored()
    {
        $_GET = [
            'AAA' => 1,
            'BBB' => 2,
        ];
        $url = '/test';
        $ignore = [$url];
        $expected = FALSE;
        $actual   = Clicks::add($url, $this->click_fn,$ignore);
        $this->assertEquals($expected, $actual);
    }
    public function testGet()
    {
        $url = '/test';
        Clicks::add($url, $this->click_fn);
        Clicks::add($url, $this->click_fn);
        Clicks::add($url, $this->click_fn);
        $get  = Clicks::get($this->click_fn);
        $expected = 3;
        $actual   = (int) current($get)['hits'];
        $this->assertEquals($expected, $actual);
    }
    public function testRawGetReturnsEmptyArrayIfClickFileDoesNotExist()
    {
        $callback = function ($val) { return $val[0]; };
        $expected = [];
        $actual   = Clicks::raw_get('/tmp/does_not_exist', $callback);
        $this->assertEquals($expected, $actual);
    }
    public function testGetByPageByDayReturnsCorrectArrayKey()
    {
        $date = date('Y-m-d');
        $url = '/test';
        Clicks::add($url, $this->click_fn);
        $expected = $url . '-' . $date;
        $actual   = array_keys(Clicks::get_by_page_by_day($this->click_fn))[0];
        $this->assertEquals($expected, $actual);
    }
    public function testGetByPageByDayReturnsCorrectUrl()
    {
        $date = date('Y-m-d');
        $url = '/test';
        Clicks::add($url, $this->click_fn);
        $get  = Clicks::get_by_page_by_day($this->click_fn);
        $value = current($get);
        $expected = $url;
        $actual   = $value['url'];
        $this->assertEquals($expected, $actual);
    }
    public function testGetByPageByDayReturnsCorrectHits()
    {
        $date = date('Y-m-d');
        $url = '/test';
        Clicks::add($url, $this->click_fn);
        Clicks::add($url, $this->click_fn);
        Clicks::add($url, $this->click_fn);
        $get  = Clicks::get_by_page_by_day($this->click_fn);
        $value = current($get);
        $expected = 3;
        $actual   = (int) $value['hits'];
        $this->assertEquals($expected, $actual);
    }
    public function testRawGetSortsByKey()
    {
        Clicks::add('/aaa', $this->click_fn);
        Clicks::add('/bbb', $this->click_fn);
        Clicks::add('/ccc', $this->click_fn);
        $expected = ['/aaa','/bbb','/ccc'];
        $callback = function ($val) { return $val[0]; };
        $actual   = array_keys(Clicks::raw_get($this->click_fn, $callback));
        $this->assertEquals($expected, $actual);
    }
    public function testRawGetRecordsHitsProperly()
    {
        $url = '/test';
        Clicks::add($url, $this->click_fn);
        Clicks::add($url, $this->click_fn);
        Clicks::add($url, $this->click_fn);
        $expected = 3;
        $callback = function ($val) { return $val[0]; };
        $actual   = Clicks::raw_get($this->click_fn, $callback)['/test']['hits'];
        $this->assertEquals($expected, $actual);
    }
    public function testRawGetSkipsEmptyRows()
    {
        $url = '/test';
        Clicks::add($url, $this->click_fn);
        $obj = new SplFileObject($this->click_fn, 'a');
        $ok = (bool) $obj->fputcsv(['/test', date('Y-m-d')], CsvBase::DEFAULT_DELIM, CsvBase::DEFAULT_ENCLOSURE, CsvBase::DEFAULT_ESCAPE);
        unset($obj);
        Clicks::add($url, $this->click_fn);
        $expected = 2;
        $callback = function ($val) { return $val[0]; };
        $actual   = Clicks::raw_get($this->click_fn, $callback)['/test']['hits'];
        $this->assertEquals($expected, $actual);
    }
    public function testRawGetCreatesDiscrepanciesArrayIfNumColsDoesNotMatchHeaders()
    {
        $url = '/test';
        Clicks::add($url, $this->click_fn);
        $obj = new SplFileObject($this->click_fn, 'a');
        $ok = (bool) $obj->fputcsv(['/test', date('Y-m-d')], CsvBase::DEFAULT_DELIM, CsvBase::DEFAULT_ENCLOSURE, CsvBase::DEFAULT_ESCAPE);
        unset($obj);
        Clicks::add($url, $this->click_fn);
        $callback = function ($val) { return $val[0]; };
        Clicks::raw_get($this->click_fn, $callback);
        $expected = 1;
        $actual   = count(Clicks::$discrepancies);
        $this->assertEquals($expected, $actual);
    }
    public function testGetByPath()
    {
        Clicks::add('/aaa', $this->click_fn);
        Clicks::add('/bbb/111', $this->click_fn);
        Clicks::add('/bbb/222', $this->click_fn);
        Clicks::add('/ccc/111', $this->click_fn);
        Clicks::add('/ccc/222', $this->click_fn);
        Clicks::add('/ccc/333', $this->click_fn);
        $expected = 3;
        $actual   = count(Clicks::get_by_path($this->click_fn, '/ccc/'));
        $this->assertEquals($expected, $actual);
    }
    public function testGetByPageByDay()
    {
        $url    = '/aaa';
        $day[0] = (new DateTime('now'))->format('Y-m-d');
        $day[1] = (new DateTime('tomorrow'))->format('Y-m-d');
        // add three entries to build CSV
        for ($x = 0; $x < 3; $x++)
            Clicks::add($url, $this->click_fn);
        // every other row === alternate days
        $obj  = new SplFileObject($this->click_fn, 'r');
        $tmp  = 0;
        $test = [];
        while ($row = $obj->fgetcsv(CsvBase::DEFAULT_DELIM, CsvBase::DEFAULT_ENCLOSURE, CsvBase::DEFAULT_ESCAPE)) {
            $row[1] = $day[$tmp++ & 1];
            $test[] = $row;
        }
        // write new dates back
        $obj = new SplFileObject($this->click_fn, 'w');
        foreach ($test as $row)
            $obj->fputcsv($row, CsvBase::DEFAULT_DELIM, CsvBase::DEFAULT_ENCLOSURE, CsvBase::DEFAULT_ESCAPE);
        unset($obj);
        // # clicks by day 0 s/be 2
        $key      = $url . '-' . $day[0];
        $clicks   = Clicks::get_by_page_by_day($this->click_fn);
        $expected = 2;
        $actual   = $clicks[$key]['hits'];
        $this->assertEquals($expected, $actual);
        // # clicks by day 1 s/be 1
        $key      = $url . '-' . $day[1];
        $expected = 1;
        $actual   = $clicks[$key]['hits'];
        $this->assertEquals($expected, $actual);
    }
    public function testGetByPageByMonth()
    {
        $url    = '/aaa';
        $day[0] = (new DateTime('now'))->format('Y-m-d');
        $day[1] = (new DateTime('second monday of next month'))->format('Y-m-d');
        // add six entries to build CSV
        for ($x = 0; $x < 6; $x++)
            Clicks::add($url, $this->click_fn);
        // every other row === alternate days
        $obj  = new SplFileObject($this->click_fn, 'r');
        $tmp  = 0;
        $test = [];
        while ($row = $obj->fgetcsv(CsvBase::DEFAULT_DELIM, CsvBase::DEFAULT_ENCLOSURE, CsvBase::DEFAULT_ESCAPE)) {
            $row[1] = $day[$tmp++ & 1];
            $test[] = $row;
        }
        // write new dates back
        $obj = new SplFileObject($this->click_fn, 'w');
        foreach ($test as $row)
            $obj->fputcsv($row, CsvBase::DEFAULT_DELIM, CsvBase::DEFAULT_ENCLOSURE, CsvBase::DEFAULT_ESCAPE);
        unset($obj);
        // # clicks by this month s/be 3
        $clicks   = Clicks::get_by_page_by_month($this->click_fn);
        $key      = $url . '-' . substr($day[0], 0, 7);
        $expected = 3;
        $actual   = $clicks[$key]['hits'];
        $this->assertEquals($expected, $actual);
        // # clicks by next month s/be 3
        $key      = $url . '-' . substr($day[1], 0, 7);
        $expected = 3;
        $actual   = $clicks[$key]['hits'];
        $this->assertEquals($expected, $actual);
    }
}
