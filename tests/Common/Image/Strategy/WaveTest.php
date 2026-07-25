<?php
namespace FileCMSTest\Common\Image\Strategy;

use FileCMS\Common\Image\SingleChar;
use FileCMS\Common\Image\Strategy\Wave;
use PHPUnit\Framework\TestCase;
class WaveTest extends TestCase
{
    public function testDistortMutatesTheCanvasInPlace()
    {
        $fontFile = BASE_DIR . '/tests/fonts/FreeSansBold.ttf';
        $char = new SingleChar('A', $fontFile, 120, 80);
        $bgColor = $char->colorAlloc([255, 255, 255]);
        imagefilledrectangle($char->image, 0, 0, 120, 80, $bgColor);
        // draw a single vertical black line down the middle
        $black = imagecolorallocate($char->image, 0, 0, 0);
        imageline($char->image, 60, 0, 60, 79, $black);
        $before = $this->pixelSignature($char->image, 120, 80);
        Wave::distort($char, 8.0, 5.0, $bgColor);
        $after = $this->pixelSignature($char->image, 120, 80);
        $expected = FALSE;
        $actual   = ($before === $after);
        $this->assertEquals($expected, $actual, 'Distortion should change pixel data');
    }
    public function testDistortPreservesCanvasDimensions()
    {
        $fontFile = BASE_DIR . '/tests/fonts/FreeSansBold.ttf';
        $char = new SingleChar('A', $fontFile, 150, 90);
        $bgColor = $char->colorAlloc([255, 255, 255]);
        imagefilledrectangle($char->image, 0, 0, 150, 90, $bgColor);
        Wave::distort($char, 6.0, 4.0, $bgColor);
        $expected = [150, 90];
        $actual   = [imagesx($char->image), imagesy($char->image)];
        $this->assertEquals($expected, $actual);
    }
    private function pixelSignature($image, int $w, int $h) : string
    {
        $sig = '';
        for ($x = 0; $x < $w; $x += 5) {
            for ($y = 0; $y < $h; $y += 5) {
                $sig .= imagecolorat($image, $x, $y) . ',';
            }
        }
        return $sig;
    }
}
