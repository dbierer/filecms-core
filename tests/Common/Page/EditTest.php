<?php
namespace FileCMSTest\Common\Page;

use FileCMS\Common\Page\Edit;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
class EditTest extends TestCase
{
    public $edit;
    public $testFileDir = '';
    public $backupDir = '';
    public $new_dir = '';
    public function setUp() : void
    {
        $this->testFileDir = realpath(__DIR__ . '/../../test_files');
        $this->backupDir = realpath($this->testFileDir . '/../backups');
        $config = include BASE_DIR . '/tests/config/test.config.php';
        $this->edit = new Edit($config);
        $this->new_dir = $this->testFileDir . '/new';
        $new = glob($this->new_dir . '/*');
        if (!empty($new))
            foreach ($new as $fn) unlink($fn);

        if (file_exists($this->new_dir)) rmdir($this->new_dir);
    }
    public function testGetKeyFromURL()
    {
        $url = 'https://unlikelysource.com/test1';
        $expected = '/test1';
        $actual   = $this->edit->getKeyFromURL($url);
        $this->assertEquals($expected, $actual, 'Edit::getKeyFromURL() does not produce expected key');
    }
    public function testGetKeyFromURLGetsRidOfExtension()
    {
        $url = 'https://unlikelysource.com/test1.html';
        $expected = '/test1';
        $actual   = $this->edit->getKeyFromURL($url);
        $this->assertEquals($expected, $actual, 'Edit::getKeyFromURL() does not remove .html extension');
    }
    public function testGetKeyFromFilename()
    {
        $fn  = $this->testFileDir . '/test1.html';
        $expected = '/test1';
        $actual   = $this->edit->getKeyFromFilename($fn, $this->testFileDir);
        $this->assertEquals($expected, $actual, 'Edit::getKeyFromFilename() does not produce expected key');
    }
    public function testGetFilenameFromKey()
    {
        $expected = $this->testFileDir . '/test1.html';
        $key      = '/test1';
        $actual   = $this->edit->getFilenameFromKey($key, $this->testFileDir);
        $this->assertEquals($expected, $actual);
    }
    public function testGetBackupFn()
    {
        $fn = $this->testFileDir . '/test1.html';
        $expected = $this->backupDir . '/test1.html';
        $actual = $this->edit->getBackupFn($fn, $this->backupDir, $this->testFileDir);
        $this->assertEquals($expected, $actual);
    }
    public function testGetBackupFnCreatesDirectoryIfNotExists()
    {
        $fn = $this->testFileDir . '/sub/test1.html';
        $backup_fn = $this->edit->getBackupFn($fn, $this->backupDir, $this->testFileDir);
        $expected = TRUE;
        $actual = file_exists($this->backupDir . '/sub');
        $this->assertEquals($expected, $actual);
    }
    public function testBackupReturnsFalseIfEmptyFn()
    {
        $fn = '';
        $expected = FALSE;
        $actual = $this->edit->backup($fn, $this->backupDir, $this->testFileDir);
        $this->assertEquals($expected, $actual);
    }
    public function testBackupActuallyBacksUpFile()
    {
        $fn = $this->testFileDir . '/test1.html';
        $backup_fn = $this->backupDir . '/test1.html';
        if (file_exists($backup_fn)) unlink($backup_fn);
        $this->edit->backup($fn, $this->backupDir, $this->testFileDir);
        $expected = file_get_contents($fn);
        $actual   = (file_exists($backup_fn)) ? file_get_contents($backup_fn) : '';
        $this->assertEquals($expected, $actual);
    }
    public function testBackupBacksUpFileIntoSubDir()
    {
        $fn = $this->testFileDir . '/sub/test1.html';
        $backup_subdir = $this->backupDir . '/sub';
        $backup_fn = $backup_subdir . '/test1.html';
        if (file_exists($backup_fn)) unlink($backup_fn);
        if (file_exists($backup_subdir)) rmdir($backup_subdir);
        $this->edit->backup($fn, $this->backupDir, $this->testFileDir);
        $expected = file_get_contents($fn);
        $actual   = (file_exists($backup_fn)) ? file_get_contents($backup_fn) : '';
        $this->assertEquals($expected, $actual);
    }
    public function testRestore()
    {
        $fn = $this->testFileDir . '/test5.html';
        $backup_fn = $this->backupDir . '/test5.html';
        $text = file_get_contents($fn);
        $text = str_replace('Test 5', 'Test X', $text);
        file_put_contents($backup_fn, $text);
        $this->edit->restore('/test5', $this->backupDir, $this->testFileDir);
        $expected = $text;
        $actual   = (file_exists($backup_fn)) ? file_get_contents($backup_fn) : '';
        $this->assertEquals($expected, $actual);
    }
    public function testGetListOfPagesCreatesArray()
    {
        $pages = $this->edit->getListOfPages($this->testFileDir);
        $expected = TRUE;
        $actual   = (is_array($pages) && !empty($pages));
        $this->assertEquals($expected, $actual, 'Edit::getListOfPages() did not produce array');
    }
    public function testGetListOfPagesIncludesOnlyHtmlFiles()
    {
        $pages = $this->edit->getListOfPages($this->testFileDir);
        $expected = count(glob($this->testFileDir . '/*.htm*'));
        $expected += count(glob($this->testFileDir . '/sub/*.htm*'));
        $expected += count(glob($this->testFileDir . '/bulk/*.htm*'));
        $actual   = count($pages);
        $this->assertEquals($expected, $actual, 'Edit::getListOfPages() did not find only HTML files');
    }
    public function testGetListOfPagesIncludesTest1()
    {
        $pages = $this->edit->getListOfPages($this->testFileDir);
        $expected = realpath($this->testFileDir . '/test1.html');
        $actual   = $pages['/test1'] ?? '';
        $this->assertEquals($expected, $actual, 'Edit::getListOfPages() did not include full path to test1.html');
    }
    public function testGetListOfPagesIncludesHtmFiles()
    {
        $pages = $this->edit->getListOfPages($this->testFileDir);
        $expected = realpath($this->testFileDir . '/test4.htm');
        $actual   = $pages['/test4'] ?? '';
        $this->assertEquals($expected, $actual, 'Edit::getListOfPages() did not include full path to test4.htm');
    }
    public function testGetListOfPagesDoesNotIncludesPhtmlFiles()
    {
        $pages = $this->edit->getListOfPages($this->testFileDir);
        $expected = TRUE;
        $actual   = empty($pages['/not_found']);
        $this->assertEquals($expected, $actual, 'Edit::getListOfPages() should not include PHTML files');
    }
    public function testGetContentsFromPage()
    {
        $expected = file_get_contents($this->testFileDir . '/test1.html');
        $actual   = $this->edit->getContentsFromPage('/test1', $this->testFileDir);
        $this->assertEquals($expected, $actual, 'Edit::getContentsFromPage() did not return expected HTML');
    }
    public function testGetContentsFromPageDoesNotRemoveContentsTag()
    {
        $expected = TRUE;
        $contents = $this->edit->getContentsFromPage('/testW', $this->testFileDir);
        $actual   = (strpos($contents, '%%CONTENTS%%') !== FALSE);
        $this->assertEquals($expected, $actual, 'Edit::getContentsFromPage() removed %%CONTENTS%%');
    }
    public function testGetPageFromURL()
    {
        $expected = file_get_contents($this->testFileDir . '/test1.html');
        $actual   = $this->edit->getPageFromURL('https://unlikelysource.com/test1', $this->testFileDir);
        $this->assertEquals($expected, $actual, 'Edit::getPageFromURL() did not return expected HTML');
    }
    public function testGetListOfPagesDoesNotFindsNonExistentFile()
    {
        $fn = $this->testFileDir . '/testX.html';
        if (file_exists($fn)) unlink($fn);
        $this->edit->pages = [];
        $pages = $this->edit->getListOfPages($this->testFileDir);
        $expected = TRUE;
        $actual   = (empty($pages['/textX']));
        $this->assertEquals($expected, $actual, 'Edit::getListOfPages() listed a file that does not exist');
    }
    public function testGetListOfPagesFindsNewFile()
    {
        $fn = $this->testFileDir . '/testX.html';
        copy($this->testFileDir . '/test1.html', $fn);
        $this->edit->pages = [];
        $pages = $this->edit->getListOfPages($this->testFileDir);
        $expected = $this->testFileDir . '/testX.html';
        $actual   = $pages['/testX'] ?? '';
        $this->assertEquals($expected, $actual, 'Edit::getListOfPages() failed to list a new file');
    }
    public function testSaveOverwritesExistingSuccessfully()
    {
        $contents = file_get_contents($this->testFileDir . '/test1.html');
        file_put_contents($this->testFileDir . '/testX.html', $contents);
        $contents = str_replace('Test 1', 'Test X', $contents);
        $expected = TRUE;
        $actual   = $this->edit->save('/testX', $contents, $this->backupDir, $this->testFileDir);
        $this->assertEquals($expected, $actual, 'Edit::save() did not return TRUE upon successful save');
    }
    public function testSaveOverwritesExistingContents()
    {
        $contents = file_get_contents($this->testFileDir . '/test1.html');
        file_put_contents($this->testFileDir . '/testX.html', $contents);
        $contents = str_replace('Test 1', 'Test X', $contents);
        $response = $this->edit->save('/testX', $contents, $this->backupDir, $this->testFileDir);
        $expected = trim($contents);
        $actual   = file_get_contents($this->testFileDir . '/testX.html');
        $this->assertEquals($expected, $actual, 'Edit::save() did overwrite original');
    }
    #[RequiresPhpExtension('tidy')]
    public function testSaveFixesUsingTidyNoStrip()
    {
        $contents = file_get_contents($this->testFileDir . '/test1.html');
        file_put_contents($this->testFileDir . '/testX.html', $contents);
        $contents = str_replace('Test 1', 'Test X', $contents);
        $response = $this->edit->save('/testX', $contents, $this->backupDir, $this->testFileDir, TRUE, FALSE);
        $expected = '<!DOCTYPE html>';
        $contents = file($this->testFileDir . '/testX.html');
        $actual   = trim($contents[0]);
        $this->assertEquals($expected, $actual, 'Edit::save() did not fix using Tidy');
    }
    #[RequiresPhpExtension('tidy')]
    public function testSaveFixesUsingTidyWithStrip()
    {
        $contents = file_get_contents($this->testFileDir . '/test1.html');
        file_put_contents($this->testFileDir . '/testX.html', $contents);
        $contents = str_replace('Test 1', 'Test X', $contents);
        $response = $this->edit->save('/testX', $contents, $this->backupDir, $this->testFileDir, TRUE, TRUE);
        $expected = '<h1>Test X</h1>';
        $actual   = trim(file_get_contents($this->testFileDir . '/testX.html'));
        $this->assertEquals($expected, $actual, 'Edit::save() did not fix using Tidy');
    }
    public function testSaveCreatesNewFile()
    {
        $new_fn   = $this->testFileDir . '/testY.html';
        if (file_exists($new_fn)) unlink($new_fn);
        $contents = file_get_contents($this->testFileDir . '/test1.html');
        $contents = str_replace('Test 1', 'Test Y', $contents);
        $response = $this->edit->save('/testY', $contents, $this->backupDir, $this->testFileDir);
        $expected = trim($contents);
        $actual   = (file_exists($new_fn)) ? file_get_contents($new_fn) : '';
        $this->assertEquals($expected, $actual, 'Edit::save() did not save new file');
    }
    public function testSaveCreatesNewFileAndNewPath()
    {
        $new_fn   = $this->new_dir . '/testZ.html';
        $contents = file_get_contents($this->testFileDir . '/test1.html');
        $contents = str_replace('Test 1', 'Test Z', $contents);
        $response = $this->edit->save('/new/testZ', $contents, $this->backupDir, $this->testFileDir);
        $expected = TRUE;
        $actual   = (file_exists($new_fn));
        $this->assertEquals($expected, $actual, 'Edit::save() did not create new directory and save new file');
    }
    public function testSaveCreatesNewFileAndNewPathAndContentsMatch()
    {
        $new_fn   = $this->new_dir . '/testZ.html';
        $contents = file_get_contents($this->testFileDir . '/test1.html');
        $contents = str_replace('Test 1', 'Test Z', $contents);
        $response = $this->edit->save('/new/testZ', $contents, $this->backupDir, $this->testFileDir);
        $expected = trim($contents);
        $actual   = (file_exists($new_fn)) ? file_get_contents($new_fn) : '';
        $this->assertEquals($expected, $actual, 'Edit::save() did not create new file in new directory contents do not match');
    }
    public function testDeleteReturnsTrueIfFileIsDeleted()
    {
        $new_fn   = $this->testFileDir . '/testZ.html';
        $contents = file_get_contents($this->testFileDir . '/test1.html');
        $contents = file_put_contents($new_fn, $contents);
        $expected = TRUE;
        $actual   = $this->edit->delete('/testZ', $this->testFileDir);
        $this->assertEquals($expected, $actual, 'Edit::delete() did not return TRUE if file is deleted');
    }
    public function testDeleteReturnsFalseIfFileNotDeleted()
    {
        $expected = FALSE;
        $actual   = $this->edit->delete('/doesnotexist', $this->testFileDir);
        $this->assertEquals($expected, $actual, 'Edit::delete() did not return FALSE if file not deleted');
    }
    public function testDeleteFileIsTrulyGone()
    {
        $new_fn   = $this->testFileDir . '/testQ.html';
        $contents = file_get_contents($this->testFileDir . '/test1.html');
        $contents = str_replace('Test 1', 'Test Q', $contents);
        $response = $this->edit->delete('/testQ', $this->testFileDir);
        $expected = FALSE;
        $actual   = (file_exists($new_fn));
        $this->assertEquals($expected, $actual, 'Edit::delete() did not delete file');
    }
}
