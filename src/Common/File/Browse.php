<?php
namespace FileCMS\Common\File;

/*
 * Handles file browsing
 * Produces the "browse server images" pop-up used by the Super editor.
 * Editor-neutral: works with TinyMCE (via file_picker_callback) and legacy CKEditor 4
 * (via filebrowserImageBrowseUrl) -- see getPickerScript()
 *
 * Author: doug@unlikelysource.com
 * License: BSD
 * Revision Date: 2021-10-06
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 * * Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above
 *   copyright notice, this list of conditions and the following disclaimer
 *   in the documentation and/or other materials provided with the
 *   distribution.
 * * Neither the name of the  nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
use ArrayIterator;
use FilterIterator;
use OuterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FileCMS\Common\Generic\Messages;
use InvalidArgumentException;
class Browse
{

    const DEFAULT_IMG_DIR   = BASE_DIR . '/public/images';
    const DEFAULT_THUMB_DIR = BASE_DIR . '/public/images/thumb';
    const DEFAULT_THUMB_URL = '/images/thumb';
    const DEFAULT_PATH_EXCLUDE = [];
    const THUMB_WIDTH       = 75;
    // name of the window-level JS function the opener (edit.phtml) defines to receive the chosen URL
    // TinyMCE: set inside file_picker_callback: window.filecmsPickerCallback = callback;
    const PICKER_CALLBACK   = 'filecmsPickerCallback';
    const DEFAULT_TITLE     = 'Browsing Files';
    const DISPLAY_STYLE     = 'background-color:#E5E5E5;'
                            . 'margin:10px;'
                            . 'width:100px;'
                            . 'height:100px;'
                            . 'text-align:center;'
                            . 'vertical-align:middle;'
                            . 'border:thin solid black;'
                            . 'display:block;'
                            . 'float:left;';
    const DISP_IMG_STYLE  = 'width:80%;height:80%;';
    const GD_MAP          = ['jpg' => 'jpeg', 'jpeg' => 'jpeg', 'png' => 'png', 'bmp' => 'bmp', 'gif' => 'gif'];
    public $errors        = [];
    public $config        = [];
    public $images        = NULL;
    public $allowed       = [];
    public $img_dir       = '';
    public $img_url       = '';
    public $thumb_dir     = '';
    public $thumb_url     = '';
    public $path_exclude  = [];
    public $create_thumbs = FALSE;
    public $queue         = []; // files queued to make thumbnail images
    /**
     * @param array $config : file upload information; looks for a key 'UPLOADS'
     */
    public function __construct(array $config)
    {
        $this->config = $config['UPLOADS'] ?? [];
        if (empty($this->config))
            throw new InvalidArgumentException(Upload::UPLOAD_ERROR_MISSING);
        $this->allowed = $this->config['allowed_ext'] ?? Upload::UPLOAD_DEFAULT_EXT;
        $this->img_dir = $this->config['img_dir'] ?? self::DEFAULT_IMG_DIR;
        $this->img_url = $this->config['img_url'] ?? Upload::UPLOAD_DEFAULT_URL;
        $this->thumb_dir = $this->config['thumb_dir'] ?? self::DEFAULT_THUMB_DIR;
        $this->thumb_url = $this->config['thumb_url'] ?? self::DEFAULT_THUMB_URL;
        $this->create_thumbs = $this->config['create_thumbs'] ?? FALSE;
        $this->path_exclude  = $this->config['path_exclude'] ?? self::DEFAULT_PATH_EXCLUDE;
    }

    /**
     * Handles file browsing
     *
     * @return Generator : iteration of HTML <div> tags
     */
    public function handle()
    {
        $list  = $this->getListOfImages();
        $html  = '';
        $count = 1000;
        $list->rewind();
        while ($list->valid()) {
            $key = $list->key();
            $fn  = $list->current();
            $size = $this->getStrSize(filesize($fn));
            $thumb_url = $this->getThumbUrlFromImageUrl($key, $fn);
            $id   = 'img_' . $count++;
            // NOTE: markup (onclick="returnFileUrl('id')" + hidden input) is unchanged from earlier
            //       versions so that sites with their own legacy browse.php script keep working
            $html = '<div style="' . self::DISPLAY_STYLE . '">'
                  . '<a style="cursor:pointer;" name="' . $id . '" data-url="' . $this->escape($key) . '"'
                  . ' onclick="returnFileUrl(\'' . $id . '\')">'
                  . '<img src="' . $this->escape($thumb_url) . '" alt="' . $this->escape($key) . '" style="' . self::DISP_IMG_STYLE . '"/>'
                  . '</a>'
                  . '<br />Size: ' . $size
                  . '<input type="hidden" id="' . $id . '" value="' . $this->escape($key) . '" />'
                  . '</div>'
                  . '&nbsp;';
            yield $html;
            $list->next();
        }
    }

    /**
     * Returns the <script> block for the browse pop-up
     * Defines returnFileUrl(id), which hands the chosen image URL back to the editor in the opener window:
     * - legacy CKEditor 4: if the pop-up URL contains "CKEditorFuncNum" (added by CKEditor itself)
     * - TinyMCE: calls window.opener[PICKER_CALLBACK](url, meta); edit.phtml must set this in file_picker_callback:
     *       file_picker_types: 'image',
     *       file_picker_callback: function (callback, value, meta) {
     *           window.filecmsPickerCallback = callback;
     *           window.open('/super/browse', 'filecms_browse', 'width=800,height=500');
     *       },
     *
     * @return string $script : HTML <script> block (no external dependencies)
     */
    public function getPickerScript() : string
    {
        $callback = self::PICKER_CALLBACK;
        return <<<EOT
<script>
    // Returns the selected image URL to the editor that opened this window (TinyMCE or legacy CKEditor 4)
    function returnFileUrl(id) {
        var elem   = document.getElementById(id);
        var url    = (elem) ? elem.value : '';
        var opener = window.opener;
        var match  = window.location.search.match(/[?&]CKEditorFuncNum=([^&]+)/i);
        if (!url || !opener) {
            window.close();
            return;
        }
        if (match && opener.CKEDITOR && opener.CKEDITOR.tools) {
            opener.CKEDITOR.tools.callFunction(decodeURIComponent(match[1]), url);
        } else if (typeof opener.{$callback} === 'function') {
            opener.{$callback}(url, { alt: '' });
        } else {
            alert('Unable to return the image to the editor. Copy this URL instead: ' + url);
            return;
        }
        window.close();
    }
</script>
EOT;
    }

    /**
     * Returns the complete browse pop-up page: list of images + getPickerScript()
     * Typical use in /src/browse.php (after authentication):
     *     echo (new Browse($config))->render();
     *
     * @param string $title : page title
     * @return string $html : full HTML page
     */
    public function render(string $title = self::DEFAULT_TITLE) : string
    {
        $html = '<!DOCTYPE html>' . PHP_EOL
              . '<html lang="en">' . PHP_EOL
              . '<head>' . PHP_EOL
              . '    <meta charset="UTF-8">' . PHP_EOL
              . '    <title>' . $this->escape($title) . '</title>' . PHP_EOL
              . '</head>' . PHP_EOL
              . '<body>' . PHP_EOL;
        foreach ($this->handle() as $item) $html .= $item . PHP_EOL;
        $html .= $this->getPickerScript() . PHP_EOL
              . '</body>' . PHP_EOL
              . '</html>' . PHP_EOL;
        return $html;
    }

    /**
     * Escapes a value for safe use inside an HTML attribute
     *
     * @param string $value
     * @return string $escaped
     */
    protected function escape(string $value) : string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Returns size as nnn M or nnn K
     *
     * @param int $size
     * @return string $str_size
     */
    protected function getStrSize($size)
    {
        $str_size = (string) $size;
        if ($size > 1000000) {
            $str_size = sprintf('%.1fM', $size / 1000000);
        } elseif ($size > 1000) {
            $str_size = sprintf('%.1fK', $size / 1000);
        }
        return $str_size;
    }
    /**
     * Creates thumbnail for an image
     * NOTE: requires the GD extension
     *
     * @param string $fn : image filename
     * @return bool TRUE if thumbnail created OK; FALSE otherwise
     */
    public function makeThumbnail(string $fn)
    {
        if (!file_exists($fn)) return FALSE;
        // grab extension
        $result = FALSE;
        $ext = pathinfo($fn, PATHINFO_EXTENSION);
        // create GD image
        $func = 'imagecreatefrom' . (self::GD_MAP[strtolower($ext)] ?? 'jpeg');
        $image = $func($fn);
        if ($image !== FALSE) {
            // scale to THUMB_WIDTH
            $thumb = imagescale($image, self::THUMB_WIDTH);
            // get thumb FN
            $thumb_fn = $this->getThumbFnFromImageFn($fn);
            // make sure underlying directory is created
            $thumb_dir = dirname($thumb_fn);
            if (!file_exists($thumb_dir))
                mkdir($thumb_dir, 0755, TRUE);
            // save
            $save = 'image' . (self::GD_MAP[strtolower($ext)] ?? 'jpeg');
            $result = $save($thumb, $thumb_fn);
        }
        return $result;
    }

    /**
     * Returns thumbnail filename from image filename
     *
     * @param string $img_fn    : image filename
     * @return string $thumb_fn : thumbnail filename
     */
    public function getThumbFnFromImageFn(string $img_fn)
    {
        return str_replace($this->img_dir, $this->thumb_dir, $img_fn);
    }

    /**
     * Returns thumbnail URL from image URL
     *
     * @param string $img_url    : image filename
     * @param string $img_fn    : image filename
     * @return string $thumb_url : thumbnail filename
     */
    public function getThumbUrlFromImageUrl(string $img_url, string $img_fn)
    {
        // check to see if thumb image exists
        $thumb_fn = $this->getThumbFnFromImageFn($img_fn);
        if (file_exists($thumb_fn)) {
            $thumb_url = str_replace($this->img_url, $this->thumb_url, $img_url);
        } else {
            $thumb_url = $img_url;
            $this->queue[] = $img_fn;
        }
        return $thumb_url;
    }

    /**
     * Returns list of pages from starting point HTML_DIR
     * Note: HTML_DIR is a global constant defined in /public/index.php
     *
     * @param string $path  : starting path (if other than HTML_DIR
     * @return ArrayIterator $pages : [URL key => full path, URL key => full path]
     */
    public function getListOfImages(?string $path = NULL)
    {
        $path = $path ?? $this->img_dir;
        if (empty($this->images)) {
            $iter = new RecursiveDirectoryIterator($path);
            $itIt = new RecursiveIteratorIterator($iter);
            $this->images = new ArrayIterator();
            foreach ($itIt as $name => $obj) {
                // skip '.' and '..' and directories
                if ($obj->isDir()) continue;
                if (basename($name) === '.') continue;
                if (basename($name) === '..') continue;
                $ext = strtolower($obj->getExtension());
                // skip disallowed extensions
                if (!in_array($ext, $this->allowed)) continue;
                // check to see if image is in excluded path
                $ok = TRUE;
                $imgPath = $obj->getPath();
                if (!empty($this->path_exclude)) {
                    foreach ($this->path_exclude as $dir) {
                        if (stripos($imgPath, $dir) !== FALSE) {
                            $ok = FALSE;
                            break;
                        }
                    }
                }
                if ($ok) {
                    $url = $this->img_url . '/' . str_replace($path, '', $name);
                    $url = str_replace('//', '/', $url);
                    $this->images->offsetSet($url, $name);
                }
            }
            $this->images->ksort();
        }
        return $this->images;
    }
    /**
     * Create thumbnails when everything is all over
     *
     */
    public function __destruct()
    {
        $create = $this->create_thumbs ?? FALSE;
        if (!empty($this->queue) && $create) {
            foreach ($this->queue as $fn)
                $this->makeThumbnail($fn);
        }
    }
}
