<?php
namespace FileCMSTest\Common\Data;

use FileCMS\Common\Data\{Csv,CsvBase};
use FileCMS\Common\Generic\Functions;
use PHPUnit\Framework\TestCase;
class CsvTest extends TestCase
{
    public $csv = NULL;
    public $csvFn = '';
    public $csvTestFn = '';
    public $csvFileDir = __DIR__ . '/../../logs';
    public $headers = [];
    public function setUp() : void
    {
        $this->csvFn = $this->csvFileDir . '/order.csv';
        $this->csvTestFn = $this->csvFileDir . '/test.csv';
        $this->csv = new Csv($this->csvFn);
        // populate headers
        $lines = file($this->csvFn);
        $this->headers = str_getcsv($lines[0], 
                                    separator: CsvBase::DEFAULT_DELIM, 
                                    enclosure: CsvBase::DEFAULT_ENCLOSURE, 
                                    escape: CsvBase::DEFAULT_ESCAPE);
        // get rid of test.csv
        $csv_fn = $this->csvTestFn;
        if (file_exists($csv_fn)) unlink($csv_fn);
    }
    //     public function getItemsFromCsv($key_field = NULL) : array
    public function testGetItemsFromCsvReturnsNumericArrayIfKeyFieldBlank()
    {
        $rows = $this->csv->getItemsFromCsv();
        next($rows);
        $expected = 1;
        $actual   = key($rows);
        $this->assertEquals($expected, $actual);
    }
    public function testGetItemsFromCsvReturnsExpectedNumberOfRows()
    {
        $expected = count(file($this->csvFn));
        $actual   = count($this->csv->getItemsFromCsv());
        $this->assertEquals($expected, $actual);
    }
    public function testGetItemsFromCsvReturnsExpectedAssocArray()
    {
        $rows = $this->csv->getItemsFromCsv('dentist_email');
        $expected = 'Wilma';
        $actual   = $rows['wilma@flintstone.com']['first_name'];
        $this->assertEquals($expected, $actual);
    }
    public function testGetItemsFromCsvReturnsExpectedAssocArrayIfArrayKeyField()
    {
        $rows = $this->csv->getItemsFromCsv(['first_name','last_name']);
        $expected = 'wilma@flintstone.com';
        $actual   = $rows['Wilma_Flintstone']['dentist_email'];
        $this->assertEquals($expected, $actual);
    }
    //     public function findItemInCSV(string $search, bool $case = FALSE, bool $first = TRUE, bool $all = FALSE) : array
    public function testFindItemInCsvPopulatesLines()
    {
        $search   = 'BETTY@UNLIKELYSOURCE.COM';
        $this->csv->findItemInCSV($search, FALSE, TRUE);
        $expected = file($this->csvFn);
        $actual   = $this->csv->lines;
        $this->assertEquals($expected, $actual);
    }
    public function testFindItemInCsvPopulatesHeaders()
    {
        $search   = 'BETTY@UNLIKELYSOURCE.COM';
        $this->csv->findItemInCSV($search, FALSE, TRUE);
        $expected = $this->headers;
        $actual   = $this->csv->headers;
        $this->assertEquals($expected, $actual);
    }
    public function testFindItemInCsvSetsLinePointer()
    {
        $search   = 'BETTY@UNLIKELYSOURCE.COM';
        $this->csv->findItemInCSV($search, FALSE, TRUE);
        $lines = file($this->csvFn);
        $expected = $lines[3];
        $actual   = $this->csv->lines[$this->csv->pos] ?? [];
        $this->assertEquals($expected, $actual);
    }
    public function testFindItemInCsvCaseInsensitive()
    {
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $search = 'Barney Rubble';
        $expected = $arr;
        $actual   = $csv->findItemInCSV($search);
        $this->assertEquals($expected, $actual);
    }
    public function testFindItemInCsvCaseSensitiveReturnsFalse()
    {
        $search   = 'BETTY@UNLIKELYSOURCE.COM';
        $expected = [];
        $actual   = $this->csv->findItemInCSV($search, TRUE, TRUE);
        $this->assertEquals($expected, $actual);
    }
    public function testFindItemInCsvTreatsFirstRowAsDataIfFirstFlagFalse()
    {
        $search   = 'web_person_email';
        $expected = $this->headers;
        $actual   = $this->csv->findItemInCSV($search, FALSE, FALSE);
        $this->assertEquals($expected, $actual);
    }
    public function testFindItemInCsvReturnsMultipleRowsIfAllFlagSet()
    {
        $search   = 'please_add_me';
        $expected = [];
        $actual   = $this->csv->findItemInCSV($search, FALSE, FALSE,TRUE);
        $this->assertEquals($expected, $actual);
    }
    //     public function writeRowToCsv(array $post, array $csv_fields, bool $first = TRUE) : bool
    public function testWriteRowToCsvDoesNotWriteHeadersIf2ndArgEmpty()
    {
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr);
        $lines = file($csv_fn);
        $expected = array_values($arr);
        $actual   = str_getcsv($lines[0],separator: CsvBase::DEFAULT_DELIM,enclosure: CsvBase::DEFAULT_ENCLOSURE,escape: CsvBase::DEFAULT_ESCAPE);
        $this->assertEquals($expected, $actual);
    }
    public function testWriteRowToCsvWritesHeadersIfFileBlank()
    {
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $lines = file($csv_fn);
        $expected = 2;
        $actual   = count($lines);
        $this->assertEquals($expected, $actual);
        $expected = Functions::array2csv($this->headers);
        $actual   = trim($lines[0]);
        $this->assertEquals($expected, $actual);
    }
    public function testWriteRowToCsvWritesColumnsInOrder()
    {
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $lines = file($csv_fn);
        $expected = $arr;
        $actual   = array_combine(
            str_getcsv($lines[0],separator: CsvBase::DEFAULT_DELIM,enclosure: CsvBase::DEFAULT_ENCLOSURE,escape: CsvBase::DEFAULT_ESCAPE), 
            str_getcsv($lines[1],separator: CsvBase::DEFAULT_DELIM,enclosure: CsvBase::DEFAULT_ENCLOSURE,escape: CsvBase::DEFAULT_ESCAPE));
        $this->assertEquals($expected, $actual);
    }
    public function testGetSizeReportsZeroAfterNewInstance()
    {
        $csv_fn = $this->csvTestFn;
        $csv = new Csv($csv_fn);
        $expected = 0;
        $actual   = $csv->getSize();
        $this->assertEquals($expected, $actual);
    }
    public function testGetSizeReportsNonZeroAfterWriteRowToCsv()
    {
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $expected = count(file($csv_fn));
        $actual   = $csv->getSize();
        $this->assertEquals($expected, $actual);
    }
    public function testWriteRowToCsvTwoTimesDoesNotWriteHeadersTwice()
    {
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $csv->writeRowToCsv($arr, $this->headers);
        $lines = file($csv_fn);
        $expected = 3;
        $actual   = count($lines);
        $this->assertEquals($expected, $actual);
    }
    //     public function updateRowInCsv(string $search, array $data, array $csv_fields = [], bool $case = FALSE) : bool
    public function testUpdateRowInCsvReturnsTrueIfUpdateOk()
    {
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $csv->writeRowToCsv($arr, $this->headers);
        $search = 'Barney Rubble';
        $replace = ['web_person_email' => 'pebbles@flintstone.com','web_person_name' => 'Pebbles Flintstone'];
        $expected = TRUE;
        $actual   = $csv->updateRowInCsv($search, $replace, $this->headers, FALSE);
        $this->assertEquals($expected, $actual);
    }
    public function testUpdateRowInCsvChangedFieldsAreUpdatedOk()
    {
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $csv->writeRowToCsv($arr, $this->headers);
        $search = 'Barney Rubble';
        $replace = ['web_person_email' => 'pebbles@flintstone.com','web_person_name' => 'Pebbles Flintstone'];
        $csv->updateRowInCsv($search, $replace, $this->headers, FALSE);
        $lines = file($csv_fn);
        $row   = array_combine(str_getcsv($lines[0],separator: CsvBase::DEFAULT_DELIM,enclosure: CsvBase::DEFAULT_ENCLOSURE,escape: CsvBase::DEFAULT_ESCAPE), 
                               str_getcsv($lines[2],separator: CsvBase::DEFAULT_DELIM,enclosure: CsvBase::DEFAULT_ENCLOSURE,escape: CsvBase::DEFAULT_ESCAPE));
        $expected = 'pebbles@flintstone.com';
        $actual   = $row['web_person_email'] ?? 'XXX';
        $this->assertEquals($expected, $actual);
    }
    public function testUpdateRowInCsvNonChangedFieldsAreLeftAlone()
    {
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $csv->writeRowToCsv($arr, $this->headers);
        $search = 'Barney Rubble';
        $replace = ['web_person_email' => 'pebbles@flintstone.com','web_person_name' => 'Pebbles Flintstone'];
        $csv->updateRowInCsv($search, $replace, $this->headers, FALSE);
        $lines = file($csv_fn);
        $row   = array_combine(str_getcsv($lines[0],separator: CsvBase::DEFAULT_DELIM,enclosure: CsvBase::DEFAULT_ENCLOSURE,escape: CsvBase::DEFAULT_ESCAPE), 
                               str_getcsv($lines[2],separator: CsvBase::DEFAULT_DELIM,enclosure: CsvBase::DEFAULT_ENCLOSURE,escape: CsvBase::DEFAULT_ESCAPE));
        $expected = 'silver';
        $actual   = $row['add_on_plan'] ?? 'XXX';
        $this->assertEquals($expected, $actual);
    }
    public function testDeleteRowInCsv()
    {
        $email = 'barney@flintstone.com';
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $arr['web_person_email'] = $email;
        $csv->writeRowToCsv($arr, $this->headers);
        $csv->deleteRowInCsv($email, $this->headers);
        $expected = 2;
        $actual   = count($csv->lines);
        $this->assertEquals($expected, $actual, 'Invalid CSV::$lines count');
        $file = file($csv_fn);
        $expected = 2;
        $actual   = count($file);
        $this->assertEquals($expected, $actual, 'Incorrect number of rows in CSV');
    }
    public function testDeleteRowInCsvDoesNotOverwriteIfFlagNotSet()
    {
        $email = 'barney@flintstone.com';
        $csv_fn = $this->csvTestFn;
        $arr = ['silver','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $arr = array_combine($this->headers, $arr);
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr, $this->headers);
        $arr['web_person_email'] = $email;
        $csv->writeRowToCsv($arr, $this->headers);
        $csv->deleteRowInCsv($email, $this->headers, FALSE, FALSE);
        $expected = 2;
        $actual   = count($csv->lines);
        $this->assertEquals($expected, $actual, 'Invalid CSV::$lines count');
        $file = file($csv_fn);
        $expected = 3;
        $actual   = count($file);
        $this->assertEquals($expected, $actual, 'Incorrect number of rows in CSV');
    }
    public function testWriteRowToCsvReturnsExpectedRowsIfHeadersNotUsed()
    {
        $csv_fn = $this->csvTestFn;
        copy($this->csvFn, $csv_fn);
        $arr = ['TEST_275','already_listed','https://unlikelysource.com','test@unlikelysource.com','Barney Rubble','Fred','Flintstone','LSD','doug@unlikelysource.com','M','0','https://mercurysafedentistry.com/order','2022-10-06 22:19:40'];
        $csv = new Csv($csv_fn);
        $csv->writeRowToCsv($arr);
        $expected = count(file($csv_fn));
        $actual = count($csv->lines);
        $this->assertEquals($expected, $actual, 'Internal $lines count does not match');
    }
}
