<?php
namespace FileCMSTest\Common\Image;

use FileCMS\Common\Image\Captcha;
use PHPUnit\Framework\TestCase;
class CaptchaTest extends TestCase
{
    public $config = [];
    public $imgDir = '';
    public function setUp() : void
    {
        $this->imgDir = sys_get_temp_dir() . '/captcha_test_' . uniqid();
        mkdir($this->imgDir, 0755, true);
        $fontDir = BASE_DIR . '/tests/fonts';
        $this->config = [
            'font_file'  => $fontDir . '/FreeSansBold.ttf',
            'font_files' => [
                $fontDir . '/FreeSansBold.ttf',
                $fontDir . '/FreeSerifBold.ttf',
                $fontDir . '/FreeMonoBold.ttf',
            ],
            'img_dir'   => $this->imgDir,
            'num_bytes' => 3,
        ];
    }
    public function tearDown() : void
    {
        array_map('unlink', glob($this->imgDir . '/*'));
        rmdir($this->imgDir);
    }
    public function testWriteImagesReturnsExactlyOneFusedImage()
    {
        $captcha = new Captcha($this->config);
        $images  = $captcha->writeImages(bin2hex(random_bytes(4)));
        $expected = 1;
        $actual   = count($images);
        $this->assertEquals($expected, $actual);
    }
    public function testWriteImagesWritesAReadablePngFile()
    {
        $captcha = new Captcha($this->config);
        $images  = $captcha->writeImages(bin2hex(random_bytes(4)));
        $path = $this->imgDir . '/' . $images[0];
        $expected = TRUE;
        $actual   = file_exists($path);
        $this->assertEquals($expected, $actual);
        $info = getimagesize($path);
        $this->assertEquals('image/png', $info['mime']);
    }
    public function testCompositeWidthGrowsWithPhraseLength()
    {
        $this->config['num_bytes'] = 2; // 4 hex chars
        $captchaShort = new Captcha($this->config);
        $imagesShort  = $captchaShort->writeImages(bin2hex(random_bytes(4)) . '_short');
        $infoShort    = getimagesize($this->imgDir . '/' . $imagesShort[0]);

        $this->config['num_bytes'] = 6; // 12 hex chars
        $captchaLong = new Captcha($this->config);
        $imagesLong  = $captchaLong->writeImages(bin2hex(random_bytes(4)) . '_long');
        $infoLong    = getimagesize($this->imgDir . '/' . $imagesLong[0]);

        $expected = TRUE;
        $actual   = $infoLong[0] > $infoShort[0];
        $this->assertEquals($expected, $actual, 'Longer phrase should produce a wider image');
    }
    public function testPhraseIsUppercaseHexOfExpectedLength()
    {
        $captcha = new Captcha($this->config);
        $captcha->writeImages(bin2hex(random_bytes(4)));
        $expected = 6; // num_bytes(3) * 2
        $actual   = strlen($captcha->phrase);
        $this->assertEquals($expected, $actual);
        $expected = TRUE;
        $actual   = ctype_xdigit($captcha->phrase) && $captcha->phrase === strtoupper($captcha->phrase);
        $this->assertEquals($expected, $actual);
    }
    public function testNumbersModeProducesNumericPhrase()
    {
        $captcha = new Captcha($this->config);
        $captcha->writeImages(bin2hex(random_bytes(4)), TRUE);
        $expected = TRUE;
        $actual   = ctype_digit($captcha->phrase);
        $this->assertEquals($expected, $actual);
    }
    public function testFallsBackToSingleFontWhenFontFilesNotConfigured()
    {
        unset($this->config['font_files']);
        $captcha = new Captcha($this->config);
        $images  = $captcha->writeImages(bin2hex(random_bytes(4)));
        $expected = 1;
        $actual   = count($images);
        $this->assertEquals($expected, $actual);
    }
}
