<?php
namespace FileCMSTest\Common\View;

use FileCMS\Common\Transform\TransformInterface;
use FileCMS\Common\View\Html;
use FileCMS\Common\Generic\Messages;
use PHPUnit\Framework\TestCase;
class HtmlTest extends TestCase
{
    const DEFAULT_CARD_DIR = 'cards';
    public $html   = NULL;
    public $uri    = '';
    public $htmDir = '';
    public $config = [];
    public function setup() : void
    {
        $this->config = include BASE_DIR . '/tests/config/test.config.php';
        $this->html   = new Html($this->config, '/home', BASE_DIR . '/templates/site');
    }
    public function testConfigHasDelimCardsAndLayoutKeys()
    {
        $expected = TRUE;
        $actual   = isset($this->config['DELIM']);
        $this->assertEquals($expected, $actual, 'DELIM config key missing');
        $actual   = isset($this->config['CARDS']);
        $this->assertEquals($expected, $actual, 'CARDS config key missing');
        $actual   = isset($this->config['LAYOUT']);
        $this->assertEquals($expected, $actual, 'LAYOUT config key missing');
    }
    public function testInjectMetaTag()
    {
        $body = <<<EOT
<head>
  <title>%%TITLE%%</title>
</head>
EOT;
        $expected = <<<EOT
<head>
  <title>FileCMS</title>
</head>
EOT;
        $this->html->injectMeta($body, 'title', 'FileCMS');
        $actual = $body;;
        $this->assertEquals($expected, $actual, 'Meta tag not injected');
    }
    public function testGetDirReturnsEmptyIfPathNotFound()
    {
        $dir = 'xyz';
        $expected = '';
        $actual = $this->html->getDir($dir);
        $this->assertEquals($expected, $actual);
    }
    public function testGetDirReturnsCorrectPath()
    {
        $dir = 'blog';
        $expected = BASE_DIR . '/templates/site/blog';
        $actual = $this->html->getDir($dir);
        $this->assertEquals($expected, $actual);
    }
    public function testGetDirReturnsCorrectPathIfDirUppercase()
    {
        $dir = 'BLOG';
        $expected = BASE_DIR . '/templates/site/blog';
        $actual = $this->html->getDir($dir);
        $this->assertEquals($expected, $actual);
    }
    public function testGetCardIteratorReturnsIterator()
    {
        $expected = 'ArrayIterator';
        $dir      = BASE_DIR . '/templates/site/blog';
        $iter     = $this->html->getCardIterator($dir, 'cards');
        $actual   = get_class($iter);
        $this->assertEquals($expected, $actual, 'ArrayIterator not produced');
    }
    public function testGetOrderedCardIterator()
    {
        $dir     = $this->html->getDir('blog');
        $iter    = $this->html->getOrderedCardIterator($dir, 'one,two,three');
        $copy    = $iter->getArrayCopy();
        $real    = glob(BASE_DIR . '/templates/site/blog/cards/*.html');
        $actual   = array_diff($real, $copy);
        $expected = [BASE_DIR . '/templates/site/blog/cards/four.html'];
        $this->assertEquals($expected, $actual, 'Ordered results not produced');
    }
    public function testPartialSingle()
    {
        $body     = '<html><body>%%BLOG=1%%</body></html>';
        $body     = $this->html->partial($body);
        $expected = TRUE;
        $actual   = (bool) strpos($body, '<h3 class="card-title">');
        $this->assertEquals($expected, $actual, 'Card not injected');
    }
    public function testPartialTriple()
    {
        $body     = '<html><body>%%BLOG=3%%</body></html>';
        $dir      = BASE_DIR . '/templates/site/blog';
        $body     = $this->html->partial($body);
        $expected = 3;
        $actual   = substr_count($body, '<h3 class="card-title">');
        $this->assertEquals($expected, $actual, 'Multiple cards not injected properly');
    }
    public function testPartialOrdered()
    {
        $body     = '<html><body>%%BLOG=one,two,three%%</body></html>';
        $dir      = BASE_DIR . '/templates/site/blog';
        $body     = $this->html->partial($body);
        $expected = ['<a href="/blog/one">Card One</a>','<a href="/blog/two">Card Two</a>','<a href="/blog/three">Card Three</a>'];
        $pattern  = '!>(.*?)</h3>!';
        $matches  = [];
        preg_match_all($pattern, $body, $matches);
        $actual   = $matches[1] ?? 'Fail';
        $this->assertEquals($expected, $actual, 'Multiple cards not injected in order');
    }
    public function testPartialSingleByName()
    {
        $body     = '<html><body>%%BLOG=one%%</body></html>';
        $dir      = BASE_DIR . '/templates/site/blog';
        $body     = $this->html->partial($body);
        $expected = '<a href="/blog/one">Card One</a>';
        $pattern  = '!>(.*?)</h3>!';
        $matches  = [];
        preg_match($pattern, $body, $matches);
        $actual   = $matches[1] ?? 'Fail';
        $this->assertEquals($expected, $actual, 'Single card by name not injected');
    }
    public function testPartialIgnoresCardsIfCardsFlagSet()
    {
        $body     = '<html><body>%%BLOG%%</body></html>';
        $actual   = $this->html->partial($body, FALSE);
        $expected = $body;
        $this->assertEquals($expected, $actual, 'Card injected despite flag being set FALSE');
        $expected = TRUE;
        $actual   = (bool) strpos($actual, '%%BLOG%%');
        $this->assertEquals($expected, $actual, 'Cards marker is missing');
    }
    public function testRender()
    {
        $expected = TRUE;
        $html     = $this->html->render();
        $actual   = (bool) strpos($html, 'Business Name or Tagline');
        $this->assertEquals($expected, $actual);
    }
    public function testRenderDoesNotInjectCardsIfFlagSet()
    {
        $expected = TRUE;
        $html     = $this->html->render('', FALSE);
        $actual   = (bool) strpos($html, '%%BLOG=3%%');
        $this->assertEquals($expected, $actual);
    }
    public function testConstructorStoresLang()
    {
        $html = new Html($this->config, '/home', BASE_DIR . '/templates/site', 'en');
        $expected = 'en';
        $actual   = $html->lang;
        $this->assertEquals($expected, $actual);
    }
    public function testConstructorDefaultsLangToEmptyString()
    {
        $expected = '';
        $actual   = $this->html->lang;
        $this->assertEquals($expected, $actual, 'lang should default to "" for a flat (non-language) structure');
    }
    public function testPartialFallsBackToFlatStructureWhenLangEmpty()
    {
        // regression guard: existing (pre-language-support) callers never pass $lang,
        // so this must keep resolving templates/site/home.phtml directly
        $expected = TRUE;
        $actual   = (bool) strpos($this->html->partial(), 'Business Name or Tagline');
        $this->assertEquals($expected, $actual);
    }
    public function testRenderUsesConstructorLangByDefault()
    {
        $html = new Html($this->config, '/home', BASE_DIR . '/templates/site', 'en');
        $expected = TRUE;
        $actual   = (bool) strpos($html->render(), 'English Home');
        $this->assertEquals($expected, $actual);
    }
    public function testRenderUsesConstructorLangByDefaultForOtherLanguage()
    {
        $html = new Html($this->config, '/home', BASE_DIR . '/templates/site', 'kh');
        $expected = TRUE;
        $actual   = (bool) strpos($html->render(), 'Khmer Home');
        $this->assertEquals($expected, $actual);
    }
    public function testRenderExplicitLangOverridesConstructorLang()
    {
        $html = new Html($this->config, '/home', BASE_DIR . '/templates/site', 'en');
        $expected = TRUE;
        $actual   = (bool) strpos($html->render('', TRUE, TRUE, 'kh'), 'Khmer Home');
        $this->assertEquals($expected, $actual, 'An explicit $lang argument should win over the constructor value');
    }
    public function testGetBodyFnDoesNotDoublePrefixWhenUriAlreadyContainsLang()
    {
        // $uri already carries the language segment (as it would if a caller still
        // prepends it, or a request lands on an already-prefixed URL like /en/home)
        $html = new Html($this->config, '/en/home', BASE_DIR . '/templates/site', 'en');
        $body = $html->render();
        $expected = TRUE;
        $actual   = (bool) strpos($body, 'English Home');
        $this->assertEquals($expected, $actual);
        $expected = FALSE;
        $actual   = $html->notFound;
        $this->assertEquals($expected, $actual, 'Double-prefixing would miss the file and fall through to the not-found fallback');
    }
    public function testPartialNotFoundFallbackUsesLangSpecificHome()
    {
        $html = new Html($this->config, '/this-page-does-not-exist', BASE_DIR . '/templates/site', 'kh');
        $body = $html->partial();
        $expected = TRUE;
        $actual   = (bool) strpos($body, 'Khmer Home');
        $this->assertEquals($expected, $actual);
        $expected = TRUE;
        $actual   = $html->notFound;
        $this->assertEquals($expected, $actual);
    }
    public function testGetDirWithLangReturnsLangSpecificPath()
    {
        $expected = BASE_DIR . '/templates/site/en/blog';
        $actual   = $this->html->getDir('blog', 'en');
        $this->assertEquals($expected, $actual);
    }
    public function testGetDirWithLangFallsBackToFlatDirIfLangDirMissing()
    {
        // no templates/site/kh/blog directory exists -- getDir() falls back to the
        // flat structure, same as if no $lang had been passed at all
        $expected = BASE_DIR . '/templates/site/blog';
        $actual   = $this->html->getDir('blog', 'kh');
        $this->assertEquals($expected, $actual);
    }
    public function testInjectCardsUsesInstanceLangForCardDirectory()
    {
        $html = new Html($this->config, '/home', BASE_DIR . '/templates/site', 'en');
        $body = $html->partial('<html><body>%%BLOG%%</body></html>');
        $expected = TRUE;
        $actual   = (bool) strpos($body, 'English-Only Card');
        $this->assertEquals($expected, $actual, 'Card from templates/site/en/blog/cards not injected');
        $expected = FALSE;
        $actual   = (bool) strpos($body, 'Card One');
        $this->assertEquals($expected, $actual, 'Flat-structure card should not be injected when $lang is set and a language-specific dir exists');
    }
    public function testRenderReplacesMessageMarker()
    {
        $layout = HTML_DIR . '/testM.html';
        $this->config['LAYOUT'] = $layout;
        $this->html   = new Html($this->config, 'testM', HTML_DIR);
        $marker = $this->config['MSG_MARKER'];
        $body = file_get_contents($layout);
        $expected = <<<EOT
<h1>Test M</h1>
<hr />
<p>Messages</p>
TEST
EOT;
        $this->html->msg = 'TEST';
        $actual = $this->html->render($body);
        $this->assertEquals($expected, trim($actual));
    }
}

