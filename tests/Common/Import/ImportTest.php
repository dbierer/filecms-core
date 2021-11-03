<?php
namespace FileCMSTest\Common\Import;

use FileCMS\Common\Import\Import;
use FileCMS\Common\Page\Edit;
use FileCMS\Common\Generic\Messages;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
class ImportTest extends TestCase
{
    public $testFileDir = '';
    public function setUp() : void
    {
        $this->testFileDir = realpath(__DIR__ . '/../test_files');
    }
    public function testGetDelimitedStartDoesNotExist()
    {
        $text     = '<html><body><p>xxx</p></body></html>';
        $start    = '<div>';
        $expected = $text;
        $actual = Import::get_delimited($text, $start);
        $this->assertEquals($expected, $actual, 'Contents from between delimiters not returned when start does not exist');
    }
    public function testGetDelimitedNoStop()
    {
        $text     = '<html><body><p>xxx</p></body></html>';
        $start    = '<body>';
        $expected = '<p>xxx</p></body></html>';
        $actual = Import::get_delimited($text, $start);
        $this->assertEquals($expected, $actual, 'Contents from between delimiters not returned with no stop');
    }
    public function testGetDelimitedArray()
    {
        $text     = '<html><body><p>xxx</p><p>yyy</p><p>zzz</p><div align="center">footer</div></body></div></html>';
        $start    = '<body>';
        $stop     = ['</body>','<div align="center">'];
        $expected = '<p>xxx</p><p>yyy</p><p>zzz</p>';
        $actual = Import::get_delimited($text, $start, $stop);
        $this->assertEquals($expected, $actual, 'Contents from between delimiters not returned with stop delim as array');
    }
    public function testGetDelimitedExtractsExpectedContent()
    {
        $text     = "<html>\n<body>\n<div class='xxx'><p>xxx</p>\n</div>\n</body>\n</html>\n";
        $start    = "<div class='xxx'>";
        $stop     = '</div>';
        $expected = '<p>xxx</p>';
        $actual = Import::get_delimited($text, $start, $stop);
        $this->assertEquals($expected, $actual, 'Contents from between delimiters not returned.');
    }
    public function testIsTrustedReturnsTrueAsExpected()
    {
        $url = 'https://test.unlikelysource.com/test1.html';
        $trusted = ['https://test.unlikelysource.com'];
        $expected = TRUE;
        $actual = Import::is_trusted($url, $trusted);
        $this->assertEquals($expected, $actual, 'is_trusted is not working');
    }
    public function testIsTrustedReturnsFalseAsExpected()
    {
        $url = 'https://bad.com/test1.html';
        $trusted = ['https://test.unlikelysource.com'];
        $expected = FALSE;
        $actual = Import::is_trusted($url, $trusted);
        $this->assertEquals($expected, $actual, 'is_trusted is not working');
    }
    public function testImportExtractsExpectedContentWithNoCallbacks()
    {
        $url      = 'https://test.unlikelysource.com/test1.html';
        $callbax  = [];
        $start    = '<body>';
        $stop     = '</body>';
        $expected = '<h1>Test 1</h1>';
        echo "\nMaking request to $url\n";
        $actual = Import::import($url, $callbax, $start, $stop);
        $this->assertEquals($expected, $actual, 'Contents from between delimiters not returned.');
    }
    public function testImportExtractsExpectedContentWithAppendTransform()
    {
        $url      = 'https://test.unlikelysource.com/test1.html';
        $callbax  = ['append' => ['callback' => 'FileCMS\Transform\Append', 'params' => ['text' => '<p>TEST</p>']]];
        $start    = '<body>';
        $stop     = '</body>';
        $expected = '<h1>Test 1</h1><p>TEST</p>';
        echo "\nMaking request to $url\n";
        $actual = Import::import($url, $callbax, $start, $stop);
        $this->assertEquals($expected, $actual, 'Callback not invoked properly');
    }
}