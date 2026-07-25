<?php
namespace FileCMS\Common\Image\Strategy;
// https://www.php.net/manual/en/function.imagettftext.php
/**
 * Adds lines to image background
 */
use FileCMS\Common\Image\SingleChar;
class LineFill
{
    /**
     * Writes lines onto image following this strategy
     *
     * @param SingleChar $char
     * @param int $num : number of lines
     * @param int $colorMin : minimum value (per channel) for line color --
     *        keeping this close to the text color's range (rather than
     *        full 0-255 random) denies an attacker an easy win by simply
     *        thresholding out very light or very saturated noise
     * @param int $colorMax : maximum value (per channel) for line color
     * @return void
     */
    public static function writeFill(SingleChar $char, int $num, int $colorMin = 0, int $colorMax = 255) : void
    {
        for ($x = 0; $x < $num; $x++) {
            // calc random x1, y1 (start)
            $x1 = rand(1, $char->width - SingleChar::MARGIN);
            $y1 = rand(1, $char->height - SingleChar::MARGIN);
            // calc random x2, y2 (end)
            $x2 = rand(1, $char->width - SingleChar::MARGIN);
            $y2 = rand(1, $char->height - SingleChar::MARGIN);
            // calc random color
            $r = rand($colorMin, $colorMax);
            $g = rand($colorMin, $colorMax);
            $b = rand($colorMin, $colorMax);
            $color = \imagecolorallocate($char->image, $r, $g, $b);
            \imageline($char->image, $x1, $y1, $x2, $y2, $color);
        }
    }
}
