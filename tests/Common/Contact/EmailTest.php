<?php
namespace FileCMSTest\Common\Contact;

use FileCMS\Common\Contact\Email;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;
class EmailTest extends TestCase
{
    public $config = [];
    public $mail   = NULL;
    public function setUp() : void
    {
        $this->config = include BASE_DIR . '/tests/config/test.config.php';
        $this->mail = new class() extends PHPMailer {
            public function getTo() { return $this->to; }
            public function getCc() { return $this->cc; }
            public function getBcc() { return $this->bcc; }
            public function getMIMEBody() { return $this->MIMEBody; }
        };
    }
    public function testValidateEmailReturnsFalseIfBadEmail()
    {
        $msg      = '';
        $email    = 'bad@email@bad.bad';
        $expected = FALSE;
        $actual   = Email::validateEmail($email, $msg);
        $this->assertEquals($expected, $actual);
    }
    public function testValidateEmailReturnsTrueIfGoodEmailNoMxCheck()
    {
        $msg      = '';
        $email    = 'doug@unlikelysource.com';
        $expected = TRUE;
        $actual   = Email::validateEmail($email, $msg, FALSE);
        $this->assertEquals($expected, $actual);
    }
    public function testValidateEmailReturnsFalseIfBadMxRecord()
    {
        $msg      = '';
        $email    = 'bad@unlikelysource.biz';
        $expected = FALSE;
        $actual   = Email::validateEmail($email, $msg, TRUE);
        $this->assertEquals($expected, $actual);
    }
    public function testValidateEmailReturnsTrueIfMxRecordExists()
    {
        $msg      = '';
        $email    = 'doug@unlikelysource.com';
        $expected = TRUE;
        $actual   = Email::validateEmail($email, $msg, TRUE);
        $this->assertEquals($expected, $actual);
    }
    public function testQueueOutboundAddsToCcBcc()
    {
        $data = [
            'to'  => 'doug@unlikelysource.com',
            'cc'  => ['test1@unlikelysource.com','test2@unlikelysource.com'],
            'bcc' => 'info@unlikelysource.com',
        ];
        foreach ($data as $key => $value)
            Email::queueOutbound($value, $key, $this->mail);
        $expected = $data['to'];
        $actual   = $this->mail->getTo()[0][0];
        $this->assertEquals($expected, $actual, 'To does not match');
        $expected = [ 0 => [ 0 => $data['cc'][0], 1 => ''],
                      1 => [ 0 => $data['cc'][1], 1 => '']
        ];
        $actual   = $this->mail->getCc();
        $this->assertEquals($expected, $actual, 'CC does not match');
        $expected = $data['bcc'];
        $actual   = $this->mail->getBcc()[0][0];
        $this->assertEquals($expected, $actual, 'BCC does not match');
    }
    public function testTrustedSendReturnsFailMsgOnInvalidSend()
    {
        $to = 'doug@unlikelysource.com';
        $from = 'test@unlikelysource.com';
        $subject = 'TEST ' . date('Y-m-d H:i:s');
        $body = $subject;
        $cc = '';
        $bcc = '';
        $debug = FALSE;
        $msg = Email::trustedSend($this->config, $to, $from, $subject, $body, $cc, $bcc, $debug);
        $expected = $this->config['COMPANY_EMAIL']['ERROR'];
        $actual   = $msg;
        $this->assertEquals($expected, $actual);
    }
    public function testTrustedSendDoesNotUseSMTP()
    {
        $to = 'doug@unlikelysource.com';
        $from = 'test@unlikelysource.com';
        $subject = 'TEST ' . date('Y-m-d H:i:s');
        $body = $subject;
        $cc = '';
        $bcc = '';
        $debug = TRUE;
        $this->config['COMPANY_EMAIL']['phpmailer']['smtp'] = FALSE;
        $msg = Email::trustedSend($this->config, $to, $from, $subject, $body, $cc, $bcc, $debug);
        $expected = 'mail';
        $actual   = get_object_vars(Email::$phpMailer)['Mailer'];
        $this->assertEquals($expected, $actual);
    }
    public function testTrustedSendIsHtmlIfConfigSet()
    {
        $to = 'doug@unlikelysource.com';
        $from = 'test@unlikelysource.com';
        $subject = 'TEST ' . date('Y-m-d H:i:s');
        $body = '<p>' . $subject . '</p>';
        $cc = '';
        $bcc = '';
        $debug = TRUE;
        $this->config['COMPANY_EMAIL']['phpmailer']['html'] = TRUE;
        $msg = Email::trustedSend($this->config, $to, $from, $subject, $body, $cc, $bcc, $debug);
        $expected = 'text/html';
        $actual   = get_object_vars(Email::$phpMailer)['ContentType'];
        $this->assertEquals($expected, $actual);
    }
    public function testTrustedSendIsNotHtmlIfConfigSetFalse()
    {
        $to = 'doug@unlikelysource.com';
        $from = 'test@unlikelysource.com';
        $subject = 'TEST ' . date('Y-m-d H:i:s');
        $body = '<p>' . $subject . '</p>';
        $cc = '';
        $bcc = '';
        $debug = TRUE;
        $this->config['COMPANY_EMAIL']['phpmailer']['html'] = FALSE;
        $msg = Email::trustedSend($this->config, $to, $from, $subject, $body, $cc, $bcc, $debug);
        $expected = 'text/plain';
        $actual   = get_object_vars(Email::$phpMailer)['ContentType'];
        $this->assertEquals($expected, $actual);
    }
    public function testTrustedSendUsesSMTPIfConfigSet()
    {
        $to = 'doug@unlikelysource.com';
        $from = 'test@unlikelysource.com';
        $subject = 'TEST ' . date('Y-m-d H:i:s');
        $body = $subject;
        $cc = '';
        $bcc = '';
        $debug = TRUE;
        $this->config['COMPANY_EMAIL']['phpmailer']['smtp'] = TRUE;
        $msg = Email::trustedSend($this->config, $to, $from, $subject, $body, $cc, $bcc, $debug);
        $expected = 'smtp';
        $actual   = get_object_vars(Email::$phpMailer)['Mailer'];
        $this->assertEquals($expected, $actual);
    }
    public function testProcessPostReturnsExpectedErrorIfEmailInputMissing()
    {
        $inputs   = ['email' => ''];
        $body     = 'TEST ' . date('Y-m-d H:i:s');
        $mx_check = FALSE;
        $debug    = TRUE;
        $msg      = Email::processPost($this->config, $inputs, $body, $mx_check, $debug);
        $expected = $this->config['COMPANY_EMAIL']['ERROR'];
        $actual   = $msg;
        $this->assertEquals($expected, $actual);
    }
    public function testProcessPostReturnsExpectedErrorIfEmailInvalid()
    {
        $inputs   = ['email' => 'bad@bad@email'];
        $body     = 'TEST ' . date('Y-m-d H:i:s');
        $mx_check = FALSE;
        $debug    = TRUE;
        $msg      = Email::processPost($this->config, $inputs, $body, $mx_check, $debug);
        $expected = $this->config['COMPANY_EMAIL']['ERROR'];
        $actual   = $msg;
        $this->assertEquals($expected, $actual);
    }
    public function testProcessPostReturnsExpectedErrorIfEmailInvalidMx()
    {
        $inputs   = ['email' => 'doug@unlikelysource.biz'];
        $body     = 'TEST ' . date('Y-m-d H:i:s');
        $mx_check = TRUE;
        $debug    = TRUE;
        $msg      = Email::processPost($this->config, $inputs, $body, $mx_check, $debug);
        $expected = Email::ERR_MX;
        $actual   = $msg;
        $this->assertEquals($expected, $actual);
    }
    public function testProcessPostReturnsExpectedBody()
    {
        Email::$phpMailer->Body = '';
        $inputs   = ['email' => 'doug@unlikelysource.com', 'one' => 111, 'two' => 222];
        $body     = '';
        $mx_check = FALSE;
        $debug    = TRUE;
        $msg      = Email::processPost($this->config, $inputs, $body, $mx_check, $debug);
        $expected = <<<EOT

Email                : doug@unlikelysource.com
One                  : 111
Two                  : 222

EOT;
        $actual   = $body;
        $this->assertEquals($expected, $actual);
    }
    public function testProcessPostReturnsExpectedBodyInMailInstance()
    {
        Email::$phpMailer->Body = '';
        $inputs   = ['email' => 'doug@unlikelysource.com', 'one' => 111, 'two' => 222];
        $body     = 'TEST ' . date('Y-m-d H:i:s');
        $mx_check = FALSE;
        $debug    = TRUE;
        $msg      = Email::processPost($this->config, $inputs, $body, $mx_check, $debug);
        $expected = $body;
        $actual   = Email::$phpMailer->Body;
        $this->assertEquals($expected, $actual);
    }
    /*
     * trustedSendHtmlMail() tests
     */
    protected function htmlMailDebug(string $attach_fn = '', string $file_type = 'application/pdf', $cc = '', $bcc = '') : string
    {
        Email::$htmlMail = [];
        return Email::trustedSendHtmlMail($this->config,
                                          'doug@unlikelysource.com',
                                          'test@unlikelysource.com',
                                          'TEST Subject',
                                          '<p>TEST Body</p>',
                                          $cc,
                                          $bcc,
                                          TRUE,
                                          $attach_fn,
                                          $file_type);
    }
    /**
     * Runs trustedSendHtmlMail() (non-debug) in a child PHP process with the given sendmail_path
     * (sendmail_path is PHP_INI_SYSTEM, so it cannot be changed with ini_set() inside this process)
     *
     * @return array [string $msg, bool $error]
     */
    protected function htmlMailSubprocess(string $sendmail_path) : array
    {
        $script = tempnam(sys_get_temp_dir(), 'filecms_email_test_') . '.php';
        $code   = '<?php' . PHP_EOL
                . 'require ' . var_export(BASE_DIR . '/vendor/autoload.php', TRUE) . ';' . PHP_EOL
                . '$config = ' . var_export(['COMPANY_EMAIL' => $this->config['COMPANY_EMAIL']], TRUE) . ';' . PHP_EOL
                . '$msg = FileCMS\Common\Contact\Email::trustedSendHtmlMail($config, "doug@unlikelysource.com", "test@unlikelysource.com", "TEST", "<p>TEST</p>");' . PHP_EOL
                . 'echo json_encode([$msg, FileCMS\Common\Contact\Email::$error]);' . PHP_EOL;
        file_put_contents($script, $code);
        $cmd = escapeshellarg(PHP_BINARY)
             . ' -d ' . escapeshellarg('sendmail_path=' . $sendmail_path)
             . ' -d error_log=/dev/null -d display_errors=0 '
             . escapeshellarg($script);
        $output = shell_exec($cmd);
        unlink($script);
        $result = json_decode((string) $output, TRUE);
        $this->assertIsArray($result, 'Child process did not return JSON: ' . $output);
        return $result;
    }
    public function testTrustedSendHtmlMailDebugReturnsDebugMsgAndDoesNotSend()
    {
        $msg = $this->htmlMailDebug();
        $this->assertEquals(Email::DEBUG_MSG, $msg);
        $this->assertFalse(Email::$error);
        $this->assertNotEmpty(Email::$htmlMail, 'Debug mode should store the composed message');
    }
    public function testTrustedSendHtmlMailBuildsMultipartHeaders()
    {
        $this->htmlMailDebug();
        $headers = Email::$htmlMail['headers'];
        $this->assertStringContainsString('MIME-Version: 1.0', $headers);
        $this->assertMatchesRegularExpression('/Content-Type: multipart\/mixed; boundary="(b_[0-9a-f]{32})"/', $headers);
        $this->assertStringContainsString('From: test@unlikelysource.com', $headers);
        $this->assertEquals('doug@unlikelysource.com', Email::$htmlMail['to']);
    }
    public function testTrustedSendHtmlMailEncodesSubject()
    {
        $this->htmlMailDebug();
        $expected = '=?UTF-8?B?' . base64_encode('TEST Subject') . '?=';
        $this->assertEquals($expected, Email::$htmlMail['subject']);
    }
    public function testTrustedSendHtmlMailBodyHasHtmlPartAndClosingBoundary()
    {
        $this->htmlMailDebug();
        preg_match('/boundary="(b_[0-9a-f]{32})"/', Email::$htmlMail['headers'], $matches);
        $boundary = $matches[1];
        $body     = Email::$htmlMail['body'];
        $this->assertStringStartsWith('--' . $boundary . "\r\n", $body);
        $this->assertStringContainsString('Content-Type: text/html; charset=UTF-8', $body);
        $this->assertStringContainsString(base64_encode('<p>TEST Body</p>'), $body);
        $this->assertStringEndsWith('--' . $boundary . '--', $body);
    }
    public function testTrustedSendHtmlMailNoAttachmentPartIfAttachFnEmpty()
    {
        $this->htmlMailDebug();
        $this->assertStringNotContainsString('Content-Disposition: attachment', Email::$htmlMail['body']);
    }
    public function testTrustedSendHtmlMailIncludesAttachment()
    {
        $attach_fn = BASE_DIR . '/tests/test_files/upload.txt';
        $this->htmlMailDebug($attach_fn, 'text/plain');
        $body = Email::$htmlMail['body'];
        $this->assertStringContainsString('Content-Type: text/plain; name="upload.txt"', $body);
        $this->assertStringContainsString('Content-Disposition: attachment; filename="upload.txt"', $body);
        $this->assertStringContainsString(chunk_split(base64_encode(file_get_contents($attach_fn))), $body);
    }
    public function testTrustedSendHtmlMailAddsCcAndBccHeaders()
    {
        $cc  = ['test1@unlikelysource.com', 'test2@unlikelysource.com'];
        $bcc = 'info@unlikelysource.com';
        $this->htmlMailDebug('', 'application/pdf', $cc, $bcc);
        $headers = Email::$htmlMail['headers'];
        $this->assertStringContainsString('CC: test1@unlikelysource.com,test2@unlikelysource.com', $headers);
        $this->assertStringContainsString('BCC: info@unlikelysource.com', $headers);
    }
    public function testTrustedSendHtmlMailReturnsErrorIfAttachmentMissing()
    {
        $msg = $this->htmlMailDebug(BASE_DIR . '/tests/test_files/does_not_exist.pdf');
        $this->assertEquals($this->config['COMPANY_EMAIL']['ERROR'], $msg);
        $this->assertTrue(Email::$error);
        $this->assertEmpty(Email::$htmlMail, 'Nothing should be composed if the attachment is missing');
    }
    public function testTrustedSendHtmlMailReturnsConfigErrorIfMailFails()
    {
        [$msg, $error] = $this->htmlMailSubprocess('/bin/false');
        $this->assertEquals($this->config['COMPANY_EMAIL']['ERROR'], $msg);
        $this->assertTrue($error);
    }
    public function testTrustedSendHtmlMailReturnsConfigSuccessIfMailSucceeds()
    {
        [$msg, $error] = $this->htmlMailSubprocess('cat > /dev/null');
        $this->assertEquals($this->config['COMPANY_EMAIL']['SUCCESS'], $msg);
        $this->assertFalse($error);
    }
}
