<?php
namespace FileCMSTest\Common\Security;

use FileCMS\Common\Security\Validation;
use PHPUnit\Framework\TestCase;
class ValidationTest extends TestCase
{
    public function testAlpha()
    {
        $text     = 'ABC';
        $expected = TRUE;
        $actual   = Validation::alpha($text);
        $this->assertEquals($expected, $actual);
    }
    public function testAlphaReturnsFalseIfNonAlphaCharsFound()
    {
        $text     = 'ABC123';
        $expected = FALSE;
        $actual   = Validation::alpha($text);
        $this->assertEquals($expected, $actual);
    }
    public function testAlphaAllowsWhiteSpace()
    {
        $text     = 'ABC TEST';
        $expected = TRUE;
        $actual   = Validation::alpha($text, ['allowed' => [' ']]);
        $this->assertEquals($expected, $actual);
    }
    public function testAlphaAllowsColonAndComma()
    {
        $text     = 'ABC:ONE,TWO,THREE';
        $expected = TRUE;
        $actual   = Validation::alpha($text, ['allowed' => [':',',']]);
        $this->assertEquals($expected, $actual);
    }
    public function testDigits()
    {
        $text     = '123';
        $expected = TRUE;
        $actual   = Validation::digits($text);
        $this->assertEquals($expected, $actual);
    }
    public function testDigitsReturnsFalseIfNonDigitCharsFound()
    {
        $text     = 'ABC123';
        $expected = FALSE;
        $actual   = Validation::digits($text);
        $this->assertEquals($expected, $actual);
    }
    public function testDigitsAllowsWhiteSpace()
    {
        $text     = '123 456';
        $expected = TRUE;
        $actual   = Validation::digits($text, ['allowed' => [' ']]);
        $this->assertEquals($expected, $actual);
    }
    public function testAlnum()
    {
        $text     = 'ABC123';
        $expected = TRUE;
        $actual   = Validation::alnum($text);
        $this->assertEquals($expected, $actual);
    }
    public function testAlnumReturnsFalseIfNonAlnumCharsFound()
    {
        $text     = 'ABC:123';
        $expected = FALSE;
        $actual   = Validation::alnum($text);
        $this->assertEquals($expected, $actual);
    }
    public function testAlnumAllowsWhiteSpace()
    {
        $text     = 'ABC 123';
        $expected = TRUE;
        $actual   = Validation::alnum($text, ['allowed' => [' ']]);
        $this->assertEquals($expected, $actual);
    }
    public function testAlphaAllowsMultibyteScript()
    {
        // Khmer for "hello", combining vowel signs and all
        $text     = 'សួស្តី';
        $expected = TRUE;
        $actual   = Validation::alpha($text);
        $this->assertEquals($expected, $actual);
    }
    public function testAlnumAllowsMultibyteScriptWithCombiningMarks()
    {
        // "ចាន់" includes U+17CB (SIGN BANTOC), a combining mark (\p{M}), not a letter
        $text     = 'ចាន់ សុភា';
        $expected = TRUE;
        $actual   = Validation::alnum($text, ['allowed' => [' ']]);
        $this->assertEquals($expected, $actual);
    }
    public function testAlnumRejectsDisallowedPunctuationInMultibyteText()
    {
        $text     = 'ចាន់_សុភា';
        $expected = FALSE;
        $actual   = Validation::alnum($text, ['allowed' => [' ']]);
        $this->assertEquals($expected, $actual);
    }
    public function testPhone()
    {
        $text     = '+1 111-222-3333';
        $allowed  = ['allowed' => ['+',' ', '-']];
        $expected = TRUE;
        $actual   = Validation::phone($text, $allowed);
        $this->assertEquals($expected, $actual);
    }
    public function testEmail()
    {
        $text     = 'doug@unlikelysource.com';
        $expected = TRUE;
        $actual   = Validation::email($text);
        $this->assertEquals($expected, $actual);
    }
    public function testEmailReturnsFalseIfBad()
    {
        $text     = 'bad email address';
        $expected = FALSE;
        $actual   = Validation::email($text);
        $this->assertEquals($expected, $actual);
    }
    public function testUrl()
    {
        $text     = 'https://unlikelysource.com/about';
        $expected = TRUE;
        $actual   = Validation::url($text);
        $this->assertEquals($expected, $actual);
    }
    public function testUrlReturnsFalseIfBad()
    {
        $text     = 'bad url address';
        $expected = FALSE;
        $actual   = Validation::url($text);
        $this->assertEquals($expected, $actual);
    }
    public function testNotTooLongReturnsFalseIfStringIsTooLong()
    {
        $text     = 'TEST0000';
        $expected = FALSE;
        $actual   = Validation::notTooLong($text, ['size' => 4]);
        $this->assertEquals($expected, $actual);
    }
    public function testNotTooLongReturnsTrueIfStringIsNotTooLong()
    {
        $text     = 'TEST';
        $expected = TRUE;
        $actual   = Validation::notTooLong($text, ['size' => 4]);
        $this->assertEquals($expected, $actual);
    }
    public function testNotTooLongReturnsTrueIfNoSize()
    {
        $text     = 'TEST0000';
        $expected = TRUE;
        $actual   = Validation::notTooLong($text);
        $this->assertEquals($expected, $actual);
    }
    public function testNotTooShortReturnsFalseIfStringIsTooShort()
    {
        $text     = 'TEST';
        $expected = FALSE;
        $actual   = Validation::notTooShort($text, ['size' => 8]);
        $this->assertEquals($expected, $actual);
    }
    public function testNotTooShortReturnsTrueIfStringIsNotTooShort()
    {
        $text     = 'TEST0000';
        $expected = TRUE;
        $actual   = Validation::notTooShort($text, ['size' => 8]);
        $this->assertEquals($expected, $actual);
    }
    public function testNotTooShortReturnsTrueIfNoSize()
    {
        $text     = 'TEST0000';
        $expected = TRUE;
        $actual   = Validation::notTooShort($text);
        $this->assertEquals($expected, $actual);
    }
    public function testNotTooLongCountsCharactersNotBytes()
    {
        // 4 Khmer characters, 3 bytes each = 12 bytes -- byte-counting would
        // wrongly reject this against a size of 4
        $text     = 'ខមែរ';
        $expected = TRUE;
        $actual   = Validation::notTooLong($text, ['size' => 4]);
        $this->assertEquals($expected, $actual);
    }
    public function testNotTooShortCountsCharactersNotBytes()
    {
        // same 4-character string; byte-counting (12 bytes) would wrongly pass
        // this against a size of 8 that character-counting (4) correctly fails
        $text     = 'ខមែរ';
        $expected = FALSE;
        $actual   = Validation::notTooShort($text, ['size' => 8]);
        $this->assertEquals($expected, $actual);
    }
    public function testGetMessages()
    {
        Validation::$errMessage = [];
        $text     = 'ABC123';
        $expected = Validation::ERR_ALPHA;
        $result   = Validation::alpha($text);
        $actual   = Validation::getMessages();
        $this->assertEquals($expected, $actual);
    }
    public function testRunValidators()
    {
        $callbacks = [
            'notTooLong' => ['size' => 8],
            'notTooShort' => ['size' => 4],
        ];
        $text     = 'TEST0000';
        $expected = TRUE;
        $actual   = Validation::runValidators($text, $callbacks);
        $this->assertEquals($expected, $actual);
    }
    public function testRunValidatorsReturnsFalseIfOneConditionIsFalse()
    {
        $callbacks = [
            'notTooLong' => ['size' => 6],
            'notTooShort' => ['size' => 4],
        ];
        $text     = 'TEST0000';
        $expected = FALSE;
        $actual   = Validation::runValidators($text, $callbacks);
        $this->assertEquals($expected, $actual);
    }
    public function testGetMultipleMessages()
    {
        Validation::$errMessage = [];
        $callbacks = [
            'alpha' => [],
            'notTooLong' => ['size' => 6],
        ];
        $text     = 'TEST0000';
        $expected = [Validation::ERR_ALPHA,Validation::ERR_TOO_LONG];
        $result   = Validation::runValidators($text, $callbacks);
        $actual   = Validation::$errMessage;
        $this->assertEquals($expected, $actual);
    }
}
