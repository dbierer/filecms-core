<?php
namespace FileCMSTest\Common\Security;

use Exception;
use FileCMS\Common\Security\Profile;
use PHPUnit\Framework\TestCase;
class ProfileTest extends TestCase
{
    public $config = [];
    public function setUp() : void
    {
        session_reset();
        $this->config = include BASE_DIR . '/tests/config/test.config.php';
    }
    public function testInitUsesConfigKeys()
    {
        $_SERVER['TEST'] = 'TEST';
        $this->config['SUPER']['profile'] = ['TEST'];
        Profile::init($this->config);
        $expected = [
            'TEST' => 'TEST',
            'HTTP_USER_AGENT' => date('Y-m-d'),
        ];
        $actual   = $_SESSION[Profile::PROFILE_KEY];
        $this->assertEquals($expected, $actual);
    }
    public function testVerifyWorksWithDefaults()
    {
        Profile::init($this->config);
        $expected = TRUE;
        $actual   = Profile::verify();
        $this->assertEquals($expected, $actual);
    }
    public function testVerifyUsesConfigKeys()
    {
        $_SERVER['TEST'] = 'TEST';
        $this->config['SUPER']['profile'] = ['TEST'];
        Profile::init($this->config);
        $expected = TRUE;
        $actual   = Profile::verify(FALSE,$this->config);
        $this->assertEquals($expected, $actual);
    }
    public function testVerifyReturnsFalseSessionValuesDontMatchActual()
    {
        $_SERVER['TEST'] = 'TEST';
        $this->config['SUPER']['profile'] = ['TEST'];
        Profile::init($this->config);
        $_SERVER['TEST'] = 'XXX';
        $expected = FALSE;
        $actual   = Profile::verify(FALSE,$this->config);
        $this->assertEquals($expected, $actual);
    }
    public function testVerifyLogsIfFlagSet()
    {
        Profile::init($this->config);
        $err_log = ini_get('error_log');
        file_put_contents($err_log, '');
        Profile::verify(TRUE);
        $contents = file_get_contents($err_log);
        $expected = TRUE;
        $actual   = is_string($contents);
        $this->assertEquals($expected, $actual, 'Contents not a string');
        $expected = TRUE;
        $actual   = (bool) strpos($contents, date('Y-m-d'));
        $this->assertEquals($expected, $actual, 'Does not contain expected contents');
    }
    public function testLogoutWipesOutSession()
    {
        $_SESSION['test'] = 'TEST';
        Profile::logout();
        $expected = TRUE;
        $actual   = empty($_SESSION['test']);
        $this->assertEquals($expected, $actual);
    }
    public function testAuthenticateReturnsTrueForMatchingDefaultCredentials()
    {
        $this->config['SUPER']['username'] = 'admin';
        $this->config['SUPER']['password'] = password_hash('secret', PASSWORD_BCRYPT);
        $expected = TRUE;
        $actual   = Profile::authenticate($this->config, 'admin', 'secret');
        $this->assertEquals($expected, $actual);
    }
    public function testAuthenticateReturnsFalseForWrongPassword()
    {
        $this->config['SUPER']['username'] = 'admin';
        $this->config['SUPER']['password'] = password_hash('secret', PASSWORD_BCRYPT);
        $expected = FALSE;
        $actual   = Profile::authenticate($this->config, 'admin', 'wrong');
        $this->assertEquals($expected, $actual);
    }
    public function testAuthenticateReturnsFalseForUnknownUsername()
    {
        $this->config['SUPER']['username'] = 'admin';
        $this->config['SUPER']['password'] = password_hash('secret', PASSWORD_BCRYPT);
        $expected = FALSE;
        $actual   = Profile::authenticate($this->config, 'nobody', 'secret');
        $this->assertEquals($expected, $actual);
    }
    public function testAuthenticateUsesAltLoginsWhenNameMatches()
    {
        $this->config['SUPER']['username'] = 'admin';
        $this->config['SUPER']['password'] = password_hash('secret', PASSWORD_BCRYPT);
        $this->config['SUPER']['alt_logins'] = [
            'editor' => [
                'username' => 'editor',
                'password' => password_hash('otherSecret', PASSWORD_BCRYPT),
            ],
        ];
        $expected = TRUE;
        $actual   = Profile::authenticate($this->config, 'editor', 'otherSecret');
        $this->assertEquals($expected, $actual);
    }
    public function testAuthenticateDoesNotFallBackToDefaultPasswordForAltLoginName()
    {
        $this->config['SUPER']['username'] = 'admin';
        $this->config['SUPER']['password'] = password_hash('secret', PASSWORD_BCRYPT);
        $this->config['SUPER']['alt_logins'] = [
            'editor' => [
                'username' => 'editor',
                'password' => password_hash('otherSecret', PASSWORD_BCRYPT),
            ],
        ];
        $expected = FALSE;
        $actual   = Profile::authenticate($this->config, 'editor', 'secret');
        $this->assertEquals($expected, $actual);
    }
    public function testAuthenticateReturnsFalseWhenSuperConfigMissing()
    {
        $expected = FALSE;
        $actual   = Profile::authenticate([], 'admin', 'secret');
        $this->assertEquals($expected, $actual);
    }
}
