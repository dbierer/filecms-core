<?php
namespace FileCMSTest\Common\Security;

use FileCMS\Common\Security\Csrf;
use PHPUnit\Framework\TestCase;
class CsrfTest extends TestCase
{
    public function setUp() : void
    {
        session_reset();
        unset($_SESSION[Csrf::SESSION_KEY]);
    }
    public function testTokenGeneratesOnFirstCall()
    {
        $expected = TRUE;
        $actual   = empty($_SESSION[Csrf::SESSION_KEY]);
        $this->assertEquals($expected, $actual, 'Session should start with no token');
        $token = Csrf::token();
        $expected = TRUE;
        $actual   = is_string($token) && strlen($token) > 0;
        $this->assertEquals($expected, $actual);
    }
    public function testTokenIsStableAcrossCalls()
    {
        $first  = Csrf::token();
        $second = Csrf::token();
        $this->assertEquals($first, $second);
    }
    public function testVerifyReturnsTrueForMatchingToken()
    {
        $token = Csrf::token();
        $expected = TRUE;
        $actual   = Csrf::verify($token);
        $this->assertEquals($expected, $actual);
    }
    public function testVerifyReturnsFalseForWrongToken()
    {
        Csrf::token();
        $expected = FALSE;
        $actual   = Csrf::verify('not-the-right-token');
        $this->assertEquals($expected, $actual);
    }
    public function testVerifyReturnsFalseWhenNoTokenInSession()
    {
        $expected = FALSE;
        $actual   = Csrf::verify('anything');
        $this->assertEquals($expected, $actual);
    }
    public function testVerifyReturnsFalseForNullSubmitted()
    {
        Csrf::token();
        $expected = FALSE;
        $actual   = Csrf::verify(null);
        $this->assertEquals($expected, $actual);
    }
    public function testVerifyReturnsFalseForNonStringSubmitted()
    {
        Csrf::token();
        $expected = FALSE;
        $actual   = Csrf::verify(['not' => 'a string']);
        $this->assertEquals($expected, $actual);
    }
    public function testFieldRendersHiddenInputWithCurrentToken()
    {
        $token = Csrf::token();
        $html  = Csrf::field();
        $expected = TRUE;
        $actual   = str_contains($html, 'type="hidden"')
                 && str_contains($html, 'name="csrf_token"')
                 && str_contains($html, 'value="' . $token . '"');
        $this->assertEquals($expected, $actual, $html);
    }
    public function testFieldUsesCustomFieldName()
    {
        Csrf::token();
        $html = Csrf::field('my_token');
        $expected = TRUE;
        $actual   = str_contains($html, 'name="my_token"');
        $this->assertEquals($expected, $actual, $html);
    }
}
