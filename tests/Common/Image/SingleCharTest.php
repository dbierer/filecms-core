<?php
namespace FileCMSTest\Common\Image;

use FileCMS\Common\Image\SingleChar;
use PHPUnit\Framework\TestCase;
class SingleCharTest extends TestCase
{
    public $fontFile = '';
    public function setUp() : void
    {
        $this->fontFile = BASE_DIR . '/tests/fonts/FreeSansBold.ttf';
    }
    public function testConstructorCreatesOwnImageWhenNoneProvided()
    {
        $char = new SingleChar('A', $this->fontFile, 50, 60);
        $expected = [50, 60];
        $actual   = [imagesx($char->image), imagesy($char->image)];
        $this->assertEquals($expected, $actual);
    }
    public function testConstructorReusesProvidedSharedImage()
    {
        $shared = imagecreatetruecolor(200, 80);
        $char   = new SingleChar('A', $this->fontFile, 200, 80, 40, 0, 0, 0, $shared);
        $expected = TRUE;
        $actual   = ($char->image === $shared);
        $this->assertEquals($expected, $actual);
    }
    public function testMultipleInstancesCanDrawOntoTheSameSharedImage()
    {
        $shared = imagecreatetruecolor(200, 80);
        $bg = imagecolorallocate($shared, 255, 255, 255);
        imagefilledrectangle($shared, 0, 0, 200, 80, $bg);
        $charA = new SingleChar('A', $this->fontFile, 200, 80, 40, 0, 10, 60, $shared);
        $charA->randFgColor(0, 0); // pure black text
        $charA->writeText();
        $charB = new SingleChar('B', $this->fontFile, 200, 80, 40, 0, 100, 60, $shared);
        $charB->randFgColor(0, 0);
        $charB->writeText();
        // both characters drew onto the same image -- confirm non-background
        // (non-white) pixels exist near both text positions
        $expected = TRUE;
        $actual   = $this->hasDarkPixelNear($shared, 10, 40, 200, 80)
                 && $this->hasDarkPixelNear($shared, 100, 40, 200, 80);
        $this->assertEquals($expected, $actual);
    }
    public function testRandFgColorRespectsMinMaxRange()
    {
        $char = new SingleChar('A', $this->fontFile, 50, 60);
        $char->randFgColor(0x30, 0x40);
        $rgb = imagecolorsforindex($char->image, $char->fgColor);
        foreach (['red', 'green', 'blue'] as $channel) {
            $expected = TRUE;
            $actual   = $rgb[$channel] >= 0x30 && $rgb[$channel] <= 0x40;
            $this->assertEquals($expected, $actual, "$channel out of range");
        }
    }
    private function hasDarkPixelNear($image, int $cx, int $cy, int $w, int $h) : bool
    {
        $x1 = max(0, $cx - 20);
        $x2 = min($w - 1, $cx + 20);
        $y1 = max(0, $cy - 20);
        $y2 = min($h - 1, $cy + 20);
        for ($x = $x1; $x <= $x2; $x++) {
            for ($y = $y1; $y <= $y2; $y++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r < 100 && $g < 100 && $b < 100) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
}
