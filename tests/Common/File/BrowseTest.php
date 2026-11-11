<?php
namespace FileCMSTest\Common\File;

use FilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use InvalidArgumentException;
use FileCMS\Common\File\Browse;
use PHPUnit\Framework\TestCase;
class BrowseTest extends TestCase
{
    public $config = [];
    public $testImgDir = '';
    public $testFileDir = '';
    public $testImgFileList = [];
    public function setUp() : void
    {
        $this->testFileDir = realpath(__DIR__ . '/../../test_files');
        $this->testImgDir = $this->testFileDir . '/images';
        $this->testImgFileList = file($this->testFileDir . '/list_of_images.txt');
        $this->config = include BASE_DIR . '/tests/config/test.config.php';
        $this->config['UPLOADS']['img_dir'] = $this->testFileDir . '/images';
        $this->config['UPLOADS']['thumb_dir']  = $this->testFileDir . '/thumb';
    }
    public function testUploadThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $config = [];
        $upload = new Browse($config);
    }
    public function testGetThumbFnFromImageFn()
    {
        $browse = new Browse($this->config);
        $img_fn = $this->testFileDir . '/images/blog-1.jpg';
        $expected = $this->testFileDir . '/thumb/blog-1.jpg';
        $actual = $browse->getThumbFnFromImageFn($img_fn);
        $this->assertEquals($expected, $actual, 'Thumb image FN not created correctly');
    }
    public function testGetThumbUrlFromImageUrl()
    {
        $browse = new Browse($this->config);
        $img_url = '/images/blog-1.jpg';
        $img_fn  = $this->testFileDir . $img_url;
        $thumb_fn = $this->testFileDir . '/thumb/blog-1.jpg';
        copy($img_fn, $thumb_fn);
        $expected = '/thumb/blog-1.jpg';
        $actual = $browse->getThumbUrlFromImageUrl($img_url, $img_fn);
        $this->assertEquals($expected, $actual, 'Thumb image URL not created correctly');
    }
    public function testGetThumbUrlFromImageUrlIfThumbFileDoesNotExist()
    {
        $thumb_fn = $this->testFileDir . '/thumb/blog-1.jpg';
        if (file_exists($thumb_fn)) unlink($thumb_fn);
        $browse = new Browse($this->config);
        $img_url = '/images/blog-1.jpg';
        $img_fn  = $this->testFileDir . $img_url;
        $expected = $img_url;
        $actual = $browse->getThumbUrlFromImageUrl($img_url, $img_fn);
        $this->assertEquals($expected, $actual, 'Thumb image URL not created correctly');
    }
    public function testGetThumbUrlReturnsImageUrlIfThumbFileNotFound()
    {
        $browse = new Browse($this->config);
        $img_fn = $this->testFileDir . '/images/blog-2.jpg';
        $img_url = '/images/blog-2.jpg';
        $expected = '/images/blog-2.jpg';
        $actual = $browse->getThumbUrlFromImageUrl($img_url, $img_fn);
        $this->assertEquals($expected, $actual);
    }
    public function testGetThumbUrlAddsImageUrlToQueueIfThumbFileNotFound()
    {
        $browse = new Browse($this->config);
        $img_fn = $this->testFileDir . '/images/blog-2.jpg';
        $img_url = '/images/blog-2.jpg';
        $expected = TRUE;
        $browse->getThumbUrlFromImageUrl($img_url, $img_fn);
        $actual = in_array($img_fn, $browse->queue);
        $this->assertEquals($expected, $actual);
    }
    public function testGetListOfImagesReturnsArrayIterator()
    {
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->path_exclude = [];
        $images = $browse->getListOfImages($this->testImgDir);
        $expected = 'ArrayIterator';
        $actual = get_class($images);
        $this->assertEquals($expected, $actual, 'Browse::getListOfImages() did not return ArrayIterator instance');
    }
    public function testGetListOfImagesReturnsCorrectKey()
    {
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->allowed    = ['jpg'];
        $browse->path_exclude = [];
        $images = $browse->getListOfImages($this->testImgDir);
        $images->rewind();
        $expected = '/images/blog-1.jpg';
        $actual = $images->key();
        $this->assertEquals($expected, $actual, 'Browse::getListOfImages() did not return expected key');
    }
    public function testGetListOfImagesReturnsCorrectValue()
    {
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->allowed    = ['jpg'];
        $browse->path_exclude = [];
        $images = $browse->getListOfImages($this->testImgDir);
        $images->rewind();
        $expected = $this->testImgDir . '/blog-1.jpg';
        $actual = $images->current();
        $this->assertEquals($expected, $actual, 'Browse::getListOfImages() did not return expected value');
    }
    public function testGetListOfImagesReturnsCorrectCount()
    {
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->path_exclude = [];
        $images = $browse->getListOfImages($this->testImgDir);
        $expected = count($this->testImgFileList);
        $actual = $images->count();
        $this->assertEquals($expected, $actual, 'Browse::getListOfImages() did not return expected count');
    }
    public function testGetListOfImagesReturnsCorrectCountIfOnlyPngAllowed()
    {
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->allowed    = ['png'];
        $browse->path_exclude = [];
        $images = $browse->getListOfImages($this->testImgDir);
        $expected = 0;
        foreach ($this->testImgFileList as $name)
            if (strpos($name, '.png') !== FALSE) $expected++;
        $actual = $images->count();
        $this->assertEquals($expected, $actual, 'Browse::getListOfImages() did not return expected value when only PNG allowed');
    }
    public function testGetListOfImagesExcludesFromPath()
    {
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->path_exclude    = ['xyz'];
        $images = $browse->getListOfImages($this->testImgDir);
        $expected = 0;
        foreach ($this->testImgFileList as $name)
            if (strpos($name, 'xyz') === FALSE) $expected++;
        $actual = $images->count();
        $this->assertEquals($expected, $actual);
    }
    public function testMakeThumbnailCreatesImageReturnsFalseIfImageDoesntExist()
    {
        $img_base = '/doesnt_exist.jpg';
        $img_fn  = $this->testImgDir . $img_base;
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->allowed    = ['jpg'];
        $browse->path_exclude = [];
        $result = $browse->makeThumbnail($img_fn);
        $expected = FALSE;
        $actual   = $result;
        $this->assertEquals($expected, $actual, 'Browse::makeThumbnail() does not return FALSE if image file does not exist');
    }
    public function testMakeThumbnailCreatesImage()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        $img_base = '/blog-1.jpg';
        $img_fn  = $this->testImgDir . $img_base;
        $thumb_dir = $this->testFileDir . '/thumb';
        $thumb_fn  = $thumb_dir . $img_base;
        if (file_exists($thumb_fn)) unlink($thumb_fn);
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->thumb_dir  = $thumb_dir;
        $browse->allowed    = ['jpg'];
        $browse->path_exclude = [];
        $result = $browse->makeThumbnail($img_fn);
        $list   = glob($thumb_fn);
        $expected = $thumb_fn;
        $actual   = $list[0] ?? '';
        $this->assertEquals($expected, $actual, 'Browse::makeThumbnail() did not create thumbnail image');
    }
    public function testMakeThumbnailCreatesImageSubdir()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        $img_base = '/test/test.jpg';
        $thumb_dir = $this->testFileDir . '/thumb';
        $img_fn  = $this->testImgDir . $img_base;
        $thumb_fn  = $thumb_dir . $img_base;
        $thumb_sub = dirname($thumb_fn);
        if (file_exists($thumb_fn)) unlink($thumb_fn);
        if (file_exists($thumb_sub)) rmdir($thumb_sub);
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->thumb_dir  = $thumb_dir;
        $browse->path_exclude = [];
        $result = $browse->makeThumbnail($img_fn);
        $list   = glob($thumb_fn);
        $expected = $thumb_fn;
        $actual   = $list[0] ?? '';
        $this->assertEquals(TRUE, file_exists($thumb_sub), 'Browse::makeThumbnail() did not create thumbnail subdirectory');
        $this->assertEquals($expected, $actual, 'Browse::makeThumbnail() did not create thumbnail image in subdirectory');
    }
    public function testHandleReturnsSameCountAsNumberOfImages()
    {
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->allowed    = ['png'];
        $browse->path_exclude = [];
        $generator = $browse->handle();
        $actual = 0;
        foreach ($generator as $html) $actual++;
        $expected = 0;
        foreach ($this->testImgFileList as $name)
            if (strpos($name, '.png') !== FALSE) $expected++;
        $this->assertEquals($expected, $actual, 'Browse::handle() did not return expected number of image references when only PNG allowed');
    }
    public function testHandleKeepsLegacyMarkupAndAddsDataUrl()
    {
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->allowed = ['png'];
        $browse->path_exclude = [];
        $browse->create_thumbs = FALSE;
        $html = $browse->handle()->current();
        $this->assertStringContainsString('onclick="returnFileUrl(\'img_1000\')"', $html, 'Browse::handle() no longer emits the legacy returnFileUrl() onclick');
        $this->assertStringContainsString('<input type="hidden" id="img_1000" value="', $html, 'Browse::handle() no longer emits the legacy hidden input');
        $this->assertMatchesRegularExpression('/data-url="[^"]+\.png"/', $html, 'Browse::handle() did not add data-url attribute');
    }
    public function testHandleEscapesFilenames()
    {
        $dir = sys_get_temp_dir() . '/filecms_browse_' . bin2hex(random_bytes(4));
        mkdir($dir);
        $bad = 'it\'s "bad" <b>&.png';
        copy($this->getFirstPng(), $dir . '/' . $bad);
        try {
            $config = $this->config;
            $config['UPLOADS']['img_dir'] = $dir;
            $config['UPLOADS']['thumb_dir'] = $dir . '/thumb';
            $config['UPLOADS']['create_thumbs'] = FALSE;
            $config['UPLOADS']['allowed_ext'] = ['png'];
            $config['UPLOADS']['path_exclude'] = [];
            $browse = new Browse($config);
            $html = $browse->handle()->current();
            $this->assertStringNotContainsString($bad, $html, 'Browse::handle() did not escape filename');
            $this->assertStringContainsString('it&apos;s &quot;bad&quot; &lt;b&gt;&amp;.png', $html, 'Browse::handle() did not escape filename as expected');
        } finally {
            unlink($dir . '/' . $bad);
            rmdir($dir);
        }
    }
    public function testGetPickerScriptSupportsTinymceAndCkeditor()
    {
        $browse = new Browse($this->config);
        $script = $browse->getPickerScript();
        $this->assertStringContainsString('function returnFileUrl(id)', $script, 'Picker script does not define returnFileUrl()');
        $this->assertStringContainsString('opener.' . Browse::PICKER_CALLBACK . '(url', $script, 'Picker script does not call the TinyMCE picker callback');
        $this->assertStringContainsString('CKEditorFuncNum', $script, 'Picker script lost the legacy CKEditor branch');
        $this->assertStringNotContainsString('jquery', strtolower($script), 'Picker script should not depend on jQuery');
    }
    public function testRenderReturnsFullPageWithAllImagesAndScript()
    {
        $browse = new Browse($this->config);
        $browse->img_dir = $this->testImgDir;
        $browse->allowed = ['png'];
        $browse->path_exclude = [];
        $browse->create_thumbs = FALSE;
        $html = $browse->render('Test <Title>');
        $expected = 0;
        foreach ($this->testImgFileList as $name)
            if (strpos($name, '.png') !== FALSE) $expected++;
        $this->assertStringStartsWith('<!DOCTYPE html>', $html, 'Browse::render() did not return a full page');
        $this->assertStringContainsString('<title>Test &lt;Title&gt;</title>', $html, 'Browse::render() did not escape the title');
        $this->assertEquals($expected, substr_count($html, 'returnFileUrl(\'img_'), 'Browse::render() did not include every image');
        $this->assertStringContainsString('function returnFileUrl(id)', $html, 'Browse::render() did not include the picker script');
    }
    protected function getFirstPng() : string
    {
        foreach ($this->testImgFileList as $name)
            if (strpos($name, '.png') !== FALSE) return BASE_DIR . '/' . trim($name);
        $this->markTestSkipped('No PNG test images found');
    }
}
