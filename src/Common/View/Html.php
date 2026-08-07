<?php
namespace FileCMS\Common\View;
/**
 *
 * @title  : FileCMS\Common\View
 * @date   : 17 Feb 2022
 * @author : doug@unlikelysource.com
 * @todo   : get `injectMeta()` working and tested
 * @license : BSD (see below)
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

use function trim;
use function glob;
use function substr;
use function explode;
use function shuffle;
use function ob_start;
use function array_keys;
use function ctype_digit;
use function file_exists;
use function str_replace;
use function ob_end_clean;
use function ob_get_contents;
use function file_get_contents;
use ArrayIterator;
use LimitIterator;
use RecursiveDirectoryIterator;
use FileCMS\Common\Generic\Messages;
class Html
{
    const DEFAULT_CARD_DIR = 'cards';
    const DEFAULT_LAYOUT   = BASE_DIR . '/layouts/layout.html';
    const DEFAULT_HOME     = HTML_DIR . '/home.phtml';
    const DEFAULT_DELIM    = '%%';
    const DEFAULT_CONTENTS = '%%CONTENTS%%';
    const DEFAULT_EXT      = ['html', 'htm'];
    const FALLBACK_HOME    = 'fallback.html';
    public $uri     = '';
    public $htmlDir = '';
    public $cardDir = '';
    public $delim   = '';
    public $msg     = '';
    public $config  = [];
    public $allowed = [];   // allowed extensions
    public $notFound = FALSE;  // set TRUE if requested page not found
    public $lang    = '';   // e.g. "en" | "fr" -- set once here, not re-passed by every caller
    /**
     * @param string $lang : language-based structure, e.g. templates/site/en, templates/site/fr.
     *     Leave empty for a flat (non-language) structure, e.g. templates/site/home.phtml directly.
     */
    public function __construct(array $config, string $uri, string $htmlDir, string $lang = '')
    {
        $this->config  = $config;
        $this->uri     = $uri;
        $this->htmlDir = $htmlDir;
        $this->lang    = $lang;
        $this->cardDir = $config['CARDS'] ?? static::DEFAULT_CARD_DIR;
        $this->delim   = $config['DELIM'] ?? static::DEFAULT_DELIM;
        $this->allowed = $config['SUPER']['allowed_ext'] ?? self::DEFAULT_EXT;
        $this->msg     = (Messages::getInstance())->getMessages() ?? '';
    }
    /**
     * Produces HTML snippet injected into layout
     *
     * @param string $body : existing body if any
     * @param bool $cards : set to FALSE if you don't want cards injected
     * @param bool $meta : set to FALSE if you don't want meta tags injected
     * @param string $lang  : language-based structure override; defaults to the
     *     $lang passed to the constructor when omitted (empty string here)
     * @return string $html : full HTML page
     */
    public function render(string $body = '', bool $cards = TRUE, bool $meta = TRUE, string $lang = '')
    {
        $lang = ($lang !== '') ? $lang : $this->lang;
        // pull in layout and body
        $output = '';
        $layout = $this->config['LAYOUT'] ?? static::DEFAULT_LAYOUT;
        $fn     = str_replace('//', '/', $layout);
        if (str_ends_with($fn, 'phtml')) {
            ob_start();
            require $fn;
            $layout = ob_get_contents();
            ob_end_clean();
        } else {
            $layout = file_get_contents($fn);
        }
        // inject meta + title tags if $meta === TRUE
        if ($meta) {
            $meta = $this->config['META'][$this->uri] ?? $this->config['META']['default'] ?? [];
            foreach ($meta as $tag => $val) {
                $this->injectMeta($layout, $tag, $val);
            }
        }
        // work with body: if html, just read contents
        $body = $this->partial($body, $cards, $lang);
        // render and deliver final output
        $search   = $this->config['CONTENTS']
                  ?? self::DEFAULT_CONTENTS
                  ?? $this->delim . 'CONTENTS' . $this->delim;
        $output   = str_replace($search, $body, $layout);
        // replace message if present
        if (!empty($this->msg) && strpos($output, $this->config['MSG_MARKER']))
            $output = str_replace($this->config['MSG_MARKER'], $this->msg, $output);
        return $output;
    }
    /**
     * Produces partial HTML snippet to be injected into layout
     *
     * @param string $body : existing body if any
     * @param bool $cards : set to FALSE if you don't want cards injected
     * @param string $lang  : language-based structure override; defaults to the
     *     $lang passed to the constructor when omitted (empty string here)
     * @return string $html : full HTML page
     */
    public function partial(string $body = '', bool $cards = TRUE, string $lang = '')
    {
        $lang = ($lang !== '') ? $lang : $this->lang;
        // pull in layout and body
        $msg    = '';
        $output = '';
        // work with body: if html, just read contents
        if (empty($body)) {
            // try HTML and HTM extensions
            foreach ($this->allowed as $ext) {
                $bodyFn = $this->getBodyFn($ext, $lang);
                if (file_exists($bodyFn)) {
                    $body = file_get_contents($bodyFn);
                    break;
                }
            }
            // if $body still not populated, try PHTML
            if (!$body) {
                $bodyFn = $this->getBodyFn('phtml', $lang);
                // if phtml, use "include" + output buffering to grab contents
                if (file_exists($bodyFn)) {
                    $body = $this->runPhpFile($bodyFn);
                // fallback: just go home
                } else {
                    $this->notFound = TRUE;
                    $this->uri = '/home';
                    $lang_fix = (empty($lang)) ? '/' : '/' . $lang . '/';
                    $home   = $this->config['HOME'] ?? self::DEFAULT_HOME;
                    $bodyFn = str_replace('//', '/', $this->htmlDir . $lang_fix . $home);
                    if (substr($bodyFn, -5) === 'phtml') {
                        $body = $this->runPhpFile($bodyFn);
                    } else {
                        $body = file_get_contents($bodyFn);
                    }
                }
            }
        }
        // inject cards into body
        if ($cards && stripos($body, $this->delim) !== FALSE) {
            $search = '!' . $this->delim . '(.+?)' . $this->delim . '!i';
            $body = preg_replace_callback($search, [$this, 'injectCards'], $body);
        }
        return $body;
    }
    /**
     * Produces body filename, honoring a language-based structure if $lang is set
     * (e.g. templates/site/en/home.phtml) -- falls back to the flat structure
     * (templates/site/home.phtml) if $lang is empty, or if $this->uri already
     * contains the language segment (avoids double-prefixing).
     *
     * @param string $ext  : html | htm | phtml
     * @param string $lang : en | fr | ""
     * @return string $body_fn
     */
    protected function getBodyFn(string $ext, string $lang = '') : string
    {
        $lang_fix = '/' . $lang . '/';
        return (empty($lang) || str_contains($this->uri, $lang_fix))
                ? $this->htmlDir . $this->uri . '.' . $ext
                : str_replace('//', '/', $this->htmlDir . $lang_fix . $this->uri . '.' . $ext);
    }
    /**
     * Populates <HEAD> section with  meta tags and title
     *
     * @param string $body : final HTML to be produced (by ref)
     * @param string $tag  : meta tag we're working on
     * @param string $val  : value to be replaced
     * @return int   $repl : number of replacements made
     */
    public function injectMeta(string &$body, string $tag, string $val)
    {
        $repl = 0;
        $search = $this->delim . strtoupper($tag) . $this->delim;
        if (strpos($body, $search)) {
            $body = str_replace($search, $val, $body);
            $repl++;
        }
        return $repl;
    }
    /**
     * Populates body with cards
     * Called from preg_replace_callback(), which only ever invokes this with a single
     * ($match) argument -- so this reads $this->lang directly rather than taking a
     * $lang parameter a caller could never actually supply.
     *
     * @param array $match : what got matched during this pass
     * @return string $body : HTML w/ cards injected
     */
    public function injectCards(array $match)
    {
        $card = '';
        $item = $match[1] ?? '';
        if ($item === FALSE) return '';
        // figure out if matched item is stand-alone, or has args
        if (strpos($item, '=') !== FALSE) {
            [$dir, $qualifier] = explode('=', $item);
            $qualifier = trim($qualifier);
        } else {
            $dir = $item;
            $qualifier = '';
        }
        $dir = $this->getDir($dir, $this->lang);
        // randomize linked list of cards
        if (empty($qualifier)) {
            $iter = $this->getCardIterator($dir);
        } else {
            // otherwise look for ","
            if (strpos($qualifier, ',') !== FALSE) {
                $iter = $this->getOrderedCardIterator($dir, $qualifier);
            // is a number?
            } elseif (ctype_digit((string) $qualifier)) {
                $iter = $this->getCardIterator($dir, $this->cardDir);
                if (empty($iter)) {
                    $iter = FALSE;
                } else {
                    $temp = clone $iter;
                    $iter = new LimitIterator($temp, 0, (int) $qualifier);
                    $iter->rewind();
                }
            // is a single card?
            } elseif (strlen($qualifier) > 0) {
                $iter = $this->getOrderedCardIterator($dir, $qualifier);
            } else {
                $iter = FALSE;
            }
        }
        // loop through iteration
        if ($iter !== FALSE) {
            while ($iter->valid()) {
                $fn = $iter->current();
                $card .= (file_exists($fn)) ? file_get_contents($fn) : '';
                $iter->next();
            }
        }
        return $card;
    }
    /**
     * Produces confirmed directory path
     *
     * @param string $dir   : relative directory
     * @param string $lang  : set this if you have a language-based structure (i.e. templates/site/en, templates/site/fr, etc.)
     * @return string $path : absolute directory path or '' if not found
     */
    public function getDir(string $dir, string $lang = '')
    {
        $candidates = [];
        if (!empty($lang)) {
            // prefer a language-specific dir, exact case then lowercase (markup
            // often writes %%BLOG%%, the directory on disk is "blog")
            $candidates[] = $this->htmlDir . '/' . $lang . '/' . $dir;
            $candidates[] = $this->htmlDir . '/' . $lang . '/' . strtolower($dir);
        }
        // fall back to the flat (non-language) structure if there's no
        // language-specific version of this directory at all
        $candidates[] = $this->htmlDir . '/' . $dir;
        $candidates[] = $this->htmlDir . '/' . strtolower($dir);
        foreach ($candidates as $path) {
            $path = str_replace('//', '/', $path);
            if (file_exists($path)) {
                return $path;
            }
        }
        return '';
    }
    /**
     * Produces randomized iteration of cards
     *
     * @param string $dir  : current card dir we're working on
     * @return ArrayIterator $list | bool FALSE
     */
    public function getCardIterator(string $dir)
    {
        $iter  = FALSE;
        $cards = [];
        $temp  = [];
        $dir   = str_replace('//', '/', $dir . '/' . $this->cardDir);
        $list  = glob($dir . '/*');
        if ($list) {
            foreach ($list as $fn)
                $cards[substr(basename($fn), 0, -5)] = $fn;
            $linked = array_keys($cards);
            shuffle($linked);
            foreach ($linked as $key)
                $temp[$key] = $cards[$key];
            $iter = new ArrayIterator($temp);
        }
        return $iter;
    }
    /**
     * Produces ordered iteration of cards
     *
     * @param string $dir       : current card dir we're working on
     * @param string $qualifier : something like "adding_intelligence,talk_to_users,etc."
     * @return ArrayIterator $iter | bool FALSE
     */
    public function getOrderedCardIterator(string $dir, string $qualifier)
    {
        // build out directory path
        $list = explode(',', $qualifier);
        $path = $dir . '/' . $this->cardDir;
        foreach ($list as $key => $value) {
            $value = trim($value);
            $value .= (substr($value, -4) !== 'html') ? '.html' : '';
            $list[$key] = str_replace(['//','..'], ['/','.'], $path . '/' . $value);
        }
        // return new iterator
        $iter = new ArrayIterator(array_values($list));
        return $iter;
    }
    /**
     * Produces output from "phtml" or "php" files
     *
     * @param string $fn
     * @return string HTML
     */
    protected function runPhpFile(string $fn)
    {
        $OBJ = $this;
        ob_start();
        include $fn;
        $body = ob_get_contents();
        ob_end_clean();
        return $body;
    }
}
