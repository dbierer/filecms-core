<?php
namespace FileCMS\Common\Image\Strategy;
/**
 * Warps an image with a 2D sine-wave pixel displacement
 */
use FileCMS\Common\Image\SingleChar;
class Wave
{
    /**
     * Ripples the entire canvas: each pixel row is shifted horizontally
     * based on sin(y), then each pixel column is shifted vertically based
     * on sin(x). Combined, this warps every character drawn on the canvas
     * in a way plain OCR / template matching does not normalize for, while
     * remaining a smooth, coherent distortion a human eye can read through.
     *
     * @param SingleChar $char : canvas to distort ($char->image is mutated in place)
     * @param float $xAmplitude : max horizontal pixel displacement
     * @param float $yAmplitude : max vertical pixel displacement
     * @param int   $bgColor    : fill color for canvas edges exposed by the shift
     * @return void
     */
    public static function distort(
        SingleChar $char,
        float $xAmplitude,
        float $yAmplitude,
        int $bgColor) : void
    {
        $width  = $char->width;
        $height = $char->height;
        $xFreq  = rand(80, 130)  / 1000;
        $xPhase = rand(0, 628)   / 100;
        $yFreq  = rand(100, 150) / 1000;
        $yPhase = rand(0, 628)   / 100;
        $source = $char->image;
        // horizontal ripple: shift each row left/right based on sin(y)
        $rowShifted = \imagecreatetruecolor($width, $height);
        \imagefill($rowShifted, 0, 0, $bgColor);
        for ($y = 0; $y < $height; $y++) {
            $dx = (int) round($xAmplitude * sin(($y * $xFreq) + $xPhase));
            \imagecopy($rowShifted, $source, $dx, $y, 0, $y, $width, 1);
        }
        // vertical ripple: shift each column up/down based on sin(x)
        $final = \imagecreatetruecolor($width, $height);
        \imagefill($final, 0, 0, $bgColor);
        for ($x = 0; $x < $width; $x++) {
            $dy = (int) round($yAmplitude * sin(($x * $yFreq) + $yPhase));
            \imagecopy($final, $rowShifted, $x, $dy, $x, 0, 1, $height);
        }
        \imagecopy($source, $final, 0, 0, 0, 0, $width, $height);
    }
}
