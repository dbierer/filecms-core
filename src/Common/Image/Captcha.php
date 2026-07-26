<?php
namespace FileCMS\Common\Image;
/**
 * Creates a CAPTCHA
 */
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FileCMS\Common\Image\SingleChar;
use FileCMS\Common\Image\Strategy\ {LineFill,DotFill,RotateText,Wave};
class Captcha
{
    public const DEFAULT_FONT_FILE = __DIR__ . '/../../fonts/FreeSansBold.ttf';
    public const DEFAULT_IMG_DIR   = __DIR__ . '/../../../public/img/captcha';
    public const DEFAULT_NUM_BYTES = 4;
    // layout of the fused phrase image
    public const CANVAS_HEIGHT      = 110;
    public const CANVAS_SIDE_MARGIN = 28;
    public const CHAR_ADVANCE       = 46;   // nominal per-character horizontal advance, before overlap is subtracted
    public const FONT_SIZE_MIN      = 34;
    public const FONT_SIZE_MAX      = 42;
    public const BASELINE_Y         = 70;
    public const BASELINE_Y_JITTER  = 14;
    public const FG_COLOR_MIN       = 0x10;
    public const FG_COLOR_MAX       = 0x60;
    public const NOISE_COLOR_MIN    = 0x40;
    public const NOISE_COLOR_MAX    = 0x90;
    public const BG_COLOR           = [0xF5, 0xF5, 0xF0];
    public const DEFAULT_ROTATE_MIN       = -40;
    public const DEFAULT_ROTATE_MAX       = 40;
    public const DEFAULT_OVERLAP_MIN      = 9;    // pixels shaved off CHAR_ADVANCE so adjacent glyphs touch/overlap
    public const DEFAULT_OVERLAP_MAX      = 17;
    public const DEFAULT_LINE_MIN         = 20;
    public const DEFAULT_LINE_MAX         = 40;
    public const DEFAULT_DOT_MIN          = 40;
    public const DEFAULT_DOT_MAX          = 70;
    public const DEFAULT_WAVE_X_AMPLITUDE = 1.0;
    public const DEFAULT_WAVE_Y_AMPLITUDE = 1.0;
    public static $old_files = 360;     // # seconds old CAPTCHA files can remain
    public static $num_bytes = 4;
    public static $font_file  = '';
    public static $font_files = [];     // pool of fonts randomized per character
    public static $img_dir   = '';
    public static $min = 1000;  // used if only numbers
    public static $max = 9999;  // used if only numbers
    public static $rotate_min       = self::DEFAULT_ROTATE_MIN;
    public static $rotate_max       = self::DEFAULT_ROTATE_MAX;
    public static $overlap_min      = self::DEFAULT_OVERLAP_MIN;
    public static $overlap_max      = self::DEFAULT_OVERLAP_MAX;
    public static $line_min         = self::DEFAULT_LINE_MIN;
    public static $line_max         = self::DEFAULT_LINE_MAX;
    public static $dot_min          = self::DEFAULT_DOT_MIN;
    public static $dot_max          = self::DEFAULT_DOT_MAX;
    public static $wave_x_amplitude = self::DEFAULT_WAVE_X_AMPLITUDE;
    public static $wave_y_amplitude = self::DEFAULT_WAVE_Y_AMPLITUDE;
    public $token   = '';
    public $phrase  = '';
    public $images  = [];
    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        self::$font_file  = $config['font_file'] ?? static::DEFAULT_FONT_FILE;
        self::$font_files = !empty($config['font_files']) ? $config['font_files'] : [self::$font_file];
        self::$img_dir    = $config['img_dir']   ?? static::DEFAULT_IMG_DIR;
        self::$num_bytes  = $config['num_bytes'] ?? static::DEFAULT_NUM_BYTES;
        self::$rotate_min = $config['rotate_min'] ?? static::DEFAULT_ROTATE_MIN;
        self::$rotate_max = $config['rotate_max'] ?? static::DEFAULT_ROTATE_MAX;
        self::$overlap_min = $config['overlap_min'] ?? static::DEFAULT_OVERLAP_MIN;
        self::$overlap_max = $config['overlap_max'] ?? static::DEFAULT_OVERLAP_MAX;
        self::$line_min   = $config['line_min']  ?? static::DEFAULT_LINE_MIN;
        self::$line_max   = $config['line_max']  ?? static::DEFAULT_LINE_MAX;
        self::$dot_min    = $config['dot_min']   ?? static::DEFAULT_DOT_MIN;
        self::$dot_max    = $config['dot_max']   ?? static::DEFAULT_DOT_MAX;
        self::$wave_x_amplitude = $config['wave_x_amplitude'] ?? static::DEFAULT_WAVE_X_AMPLITUDE;
        self::$wave_y_amplitude = $config['wave_y_amplitude'] ?? static::DEFAULT_WAVE_Y_AMPLITUDE;
    }
    /**
     * Writes out a single CAPTCHA image containing the whole phrase
     *
     * Characters are drawn directly onto one shared canvas with randomized
     * font/size/rotation/baseline and overlapping (negative-kerning)
     * placement, then the finished composite is put through a 2D wave
     * distortion. Rendering every character into its own separate file (the
     * previous approach) does an automated reader's segmentation step for
     * it for free; fusing the phrase into one image and warping it denies
     * that free segmentation.
     *
     * @param string $token  : used to identify this user
     * @param bool $numbers  : numbers only
     * @return array $images : filenames of CAPTCHA images produced (always 1 entry)
     */
    public function writeImages(string $token, bool $numbers = FALSE)
    {
        // generate random hex number for CAPTCHA
        if ($numbers) {
            $phrase = (string) rand(static::$min, static::$max);
        } else {
            $phrase = strtoupper(bin2hex(random_bytes(static::$num_bytes)));
        }
        $length = strlen($phrase);
        $width  = (2 * static::CANVAS_SIDE_MARGIN) + ($length * static::CHAR_ADVANCE);
        $height = static::CANVAS_HEIGHT;
        // shared canvas that every character and all noise draws onto
        $canvas  = new SingleChar('', static::$font_file, $width, $height);
        $bgColor = $canvas->colorAlloc(static::BG_COLOR);
        \imagefilledrectangle($canvas->image, 0, 0, $width, $height, $bgColor);
        // background noise, drawn before the text so it sits behind it
        LineFill::writeFill(
            $canvas, rand(static::$line_min, static::$line_max),
            static::NOISE_COLOR_MIN, static::NOISE_COLOR_MAX);
        // draw each character directly onto the shared canvas; negative
        // kerning (subtracting a random overlap from the advance) makes
        // adjacent glyphs touch or overlap, denying clean per-character
        // segmentation
        $cursor = static::CANVAS_SIDE_MARGIN;
        for ($x = 0; $x < $length; $x++) {
            $font  = static::$font_files[array_rand(static::$font_files)];
            $size  = rand(static::FONT_SIZE_MIN, static::FONT_SIZE_MAX);
            $textY = static::BASELINE_Y + rand(-static::BASELINE_Y_JITTER, static::BASELINE_Y_JITTER);
            $char  = new SingleChar($phrase[$x], $font, $width, $height, $size, 0, $cursor, $textY, $canvas->image);
            $char->randFgColor(static::FG_COLOR_MIN, static::FG_COLOR_MAX);
            RotateText::writeText($char, static::$rotate_min, static::$rotate_max);
            $char->writeText();
            $cursor += static::CHAR_ADVANCE - rand(static::$overlap_min, static::$overlap_max);
        }
        // foreground noise, drawn after the text so some of it sits on top too
        DotFill::writeFill(
            $canvas, rand(static::$dot_min, static::$dot_max),
            static::NOISE_COLOR_MIN, static::NOISE_COLOR_MAX);
        // warp the finished composite
        Wave::distort($canvas, static::$wave_x_amplitude, static::$wave_y_amplitude, $bgColor);
        $fn = '0_' . $token . '.png';
        $canvas->save(static::$img_dir . '/' . $fn);
        $this->images[] = $fn;
        $this->phrase = $phrase;
        return $this->images;
    }
    /**
     * Erase images older than self::$old_files number of seconds
     */
    public function __destruct()
    {
        $iter = new \RecursiveDirectoryIterator(static::$img_dir);
        $now = time();
        $expired = $now - self::$old_files;
        foreach ($iter as $name => $obj) {
            // find files older than 24 hours
            if ($obj->isFile() && $obj->getCTime() < $expired) {
                unlink($name);
            }
        }
    }
}
