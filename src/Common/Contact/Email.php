<?php
namespace FileCMS\Common\Contact;
/*
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

/**
 * Sends email using PHPMailer
 */
use PHPMailer\PHPMailer\PHPMailer;
class Email
{
    const BODY_PATTERN = '%-20s : %s' . "\n";
    const DEFAULT_SUBJECT = 'Customer Request';
    const DEFAULT_SUCCESS = 'SUCCESS: email sent';
    const DEFAULT_ERROR   = 'ERROR: unable to send email';
    const ERR_MX          = 'ERROR: unable to determine email address';
    const DEBUG_MSG       = 'DEBUG mode enabled: mail not sent';
    const ERR_EMAIL       = 'ERROR: mail() failed to send';
    const ERR_ATTACH      = 'ERROR: attachment not found or not readable';
    // you can also directly set $pattern externally if you don't want to set it in config
    public static $pattern   = self::BODY_PATTERN;
    public static $error     = FALSE;
    public static $phpMailer = NULL;
    // populated by trustedSendHtmlMail() in debug mode: ['to','subject','body','headers']
    public static $htmlMail  = [];
    /**
     * @param array $config     : from /src/config/config.php
     * @param array $inputs     : filtered and validated $_POST data
     *                          : $inputs['email'] === sender; $inputs['to'] === recipient
     * @param string $body      : message body is passed back by reference
     * @param bool $mx_check    : if set TRUE, runs "checkdnsrr($email, 'MX')"
     * @param bool $debug       : set TRUE to set debug mode: doesn't send mail
     * @param string $attach_fn : attachment filename; empty == no attachment
     * @return string $msg      : any error or other messages
     */
    public static function processPost( array $config,
                                        array $inputs,
                                        string &$body,
                                        bool $mx_check = FALSE,
                                        bool $debug = FALSE,
                                        string $attach_fn = '',)
    {
        $msg = $config['COMPANY_EMAIL']['ERROR'] ?? self::DEFAULT_ERROR;
        // sanitize "from" email
        $from = $inputs['email'] ?? $config['COMPANY_EMAIL']['from'] ?? '';
        if (!empty($inputs)&& !empty($from) && static::validateEmail($from, $msg, $mx_check)) {
            $from = filter_var($from, FILTER_SANITIZE_EMAIL);
            // set pattern
            $pattern = $config['COMPANY_EMAIL']['pattern']
                     ?? static::$pattern
                     ?? static::BODY_PATTERN;
            // create body
            $body    = (!empty($body)) ? $body : "\n";
            foreach ($inputs as $key => $value)
                $body .= sprintf(self::BODY_PATTERN, ucfirst($key), $value);
            $subject = $inputs['subject']
                     ?? $config['COMPANY_EMAIL']['default_subject']
                     ?? static::DEFAULT_SUBJECT;
            $msg  = $config['COMPANY_EMAIL']['ERROR'] ?? self::DEFAULT_ERROR;
            $to   = $inputs['to']  ?? $config['COMPANY_EMAIL']['to'];
            $cc   = $inputs['cc']  ?? $config['COMPANY_EMAIL']['cc']  ?? '';
            $bcc  = $inputs['bcc'] ?? $config['COMPANY_EMAIL']['bcc'] ?? '';
            $phpmailerConfig = $config['COMPANY_EMAIL']['phpmailer'];
            // validate email
            if (static::validateEmail($to, $msg, $mx_check, $attach_fn)) {
                // send request
                $msg = self::trustedSend($config, $to, $from, $subject, $body, $cc, $bcc, $debug, $attach_fn);
            }
        }
        return $msg;
    }
    /**
     * Sends email using PHPMailer
     * Trusts that all validation is done
     * Sets self::$error to TRUE if there was an error, FALSE otherwise
     *
     * @param array $config     : primary config file
     * @param string $to        : recipient's email address
     * @param string $from      : sender's email address
     * @param string $subject   : email subject line
     * @param string $body      : email message
     * @param mixed  $cc        : optional CC list
     * @param mixed  $bcc       : optional BCC list
     * @param bool   $debug     : set TRUE to set debug mode  : doesn't send mail
     * @param string $attach_fn : attachment filename; empty == no attachment
     * @return string $msg      : success/failure message
     */
    public static function trustedSend( array $config,
                                        string $to,
                                        string $from,
                                        string $subject,
                                        string $body,
                                        $cc = '',
                                        $bcc = '',
                                        bool $debug = FALSE,
                                        string $attach_fn = '')
    {
        self::$error = FALSE;
        $msg = $config['COMPANY_EMAIL']['ERROR'] ?? self::DEFAULT_ERROR;
        $phpmailerConfig = $config['COMPANY_EMAIL']['phpmailer'];
        // send request
        try {
            // set up SMTP
            $mail = new PHPMailer();
            if (!empty($phpmailerConfig['smtp'])) {
                $mail->IsSMTP();
                $mail->Host       = $phpmailerConfig['smtp_host'] ?? '';
                $mail->Port       = $phpmailerConfig['smtp_port'];
                $mail->SMTPAuth   = $phpmailerConfig['smtp_auth'];
                $mail->Username   = $phpmailerConfig['smtp_username'] ?? '';
                $mail->Password   = $phpmailerConfig['smtp_password'] ?? '';
                $mail->SMTPSecure = $phpmailerConfig['smtp_secure'] ?? 'tls';
            }
            // HTML email?
            if (!empty($phpmailerConfig['html'])) $mail->isHTML(TRUE);
            // set up mail obj
            $mail->setFrom($from);
            self::queueOutbound($to, 'to', $mail);
            self::queueOutbound($cc, 'cc', $mail);
            self::queueOutbound($bcc, 'bcc', $mail);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            // add attachment if exists
            if (!empty($attach_fn)) {
                $mail->addAttachment($attach_fn);
            }
            if ($debug === TRUE) {
                self::$phpMailer = $mail;
                $msg = self::DEBUG_MSG;
            } else {
                //send the message, check for errors
                if ($mail->send()) {
                    $msg = $config['COMPANY_EMAIL']['SUCCESS'] ?? self::DEFAULT_SUCCESS;
                } else {
                    $msg = $config['COMPANY_EMAIL']['ERROR'] ?? self::DEFAULT_ERROR;
                    self::$error = TRUE;
                }
            }
        } catch (\Exception $e) {
            self::$error = TRUE;
            $msg = $config['COMPANY_EMAIL']['ERROR'] ?? self::DEFAULT_ERROR;
            error_log(__METHOD__ . ':' . $e->getMessage());
        }
        return $msg;
    }
    /**
     * Sends HTML email using PHP mail() with optional attachment
     * Does not use PHPMailer: builds the multipart/mixed MIME message directly
     * Trusts that all validation is done (except that the attachment, if any, is checked for existence)
     * Sets self::$error to TRUE if there was an error, FALSE otherwise
     * In debug mode the message is composed but not sent, and is stored in self::$htmlMail
     *
     * @param array $config     : primary config file
     * @param string $to        : recipient's email address
     * @param string $from      : sender's email address
     * @param string $subject   : email subject line
     * @param string $in_body   : email message body (HTML)
     * @param mixed  $cc        : optional CC list (string or array)
     * @param mixed  $bcc       : optional BCC list (string or array)
     * @param bool   $debug     : set TRUE to set debug mode  : doesn't send mail
     * @param string $attach_fn : attachment filename; empty == no attachment
     * @param string $file_type : MIME type of attachment (default: "application/pdf")
     * @return string $msg      : success/failure message
     */
    public static function trustedSendHtmlMail( array $config,
                                        string $to,
                                        string $from,
                                        string $subject,
                                        string $in_body,
                                        $cc = '',
                                        $bcc = '',
                                        bool $debug = FALSE,
                                        string $attach_fn = '',
                                        string $file_type = 'application/pdf')
    {
        $msg = $config['COMPANY_EMAIL']['ERROR'] ?? self::DEFAULT_ERROR;
        self::$error    = TRUE;
        self::$htmlMail = [];
        try {
            $eol      = "\r\n";
            $boundary = 'b_' . bin2hex(random_bytes(16));

            // Read attachment first so we don't compose anything if it's missing
            $attach_data = '';
            if (!empty($attach_fn)) {
                if (!is_file($attach_fn) || !is_readable($attach_fn)) {
                    throw new \Exception(self::ERR_ATTACH . ':' . $attach_fn);
                }
                $contents = file_get_contents($attach_fn);
                if ($contents === FALSE) {
                    throw new \Exception(self::ERR_ATTACH . ':' . $attach_fn);
                }
                $attach_data = chunk_split(base64_encode($contents));
            }

            // Format mail headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
                'From: ' . $from,
            ];
            if (!empty($cc)) {
                if (is_array($cc)) $cc = implode(',', $cc);
                $headers[] = 'CC: ' . $cc;
            }
            if (!empty($bcc)) {
                if (is_array($bcc)) $bcc = implode(',', $bcc);
                $headers[] = 'BCC: ' . $bcc;
            }
            $headers[] = 'X-Mailer: PHP/' . phpversion();

            // Body: HTML part
            $body  = '--' . $boundary . $eol;
            $body .= 'Content-Type: text/html; charset=UTF-8' . $eol;
            $body .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
            $body .= chunk_split(base64_encode($in_body)) . $eol;

            if (!empty($attach_fn)) {
                // Body: attachment part
                $file_name = basename($attach_fn);
                $body .= '--' . $boundary . $eol;
                $body .= 'Content-Type: ' . $file_type . '; name="' . $file_name . '"' . $eol;
                $body .= 'Content-Transfer-Encoding: base64' . $eol;
                $body .= 'Content-Disposition: attachment; filename="' . $file_name . '"' . $eol . $eol;
                $body .= $attach_data . $eol;
            }

            // Closing boundary
            $body .= '--' . $boundary . '--';

            // Encode subject for non-ASCII safety
            $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $headers_str     = implode($eol, $headers);

            if ($debug === TRUE) {
                self::$htmlMail = [
                    'to'      => $to,
                    'subject' => $encoded_subject,
                    'body'    => $body,
                    'headers' => $headers_str,
                ];
                self::$error = FALSE;
                $msg = self::DEBUG_MSG;
            } elseif (mail($to, $encoded_subject, $body, $headers_str)) {
                self::$error = FALSE;
                $msg = $config['COMPANY_EMAIL']['SUCCESS'] ?? self::DEFAULT_SUCCESS;
            } else {
                throw new \Exception(self::ERR_EMAIL);
            }
        } catch (\Throwable $e) {
            self::$error = TRUE;
            $msg = $config['COMPANY_EMAIL']['ERROR'] ?? self::DEFAULT_ERROR;
            error_log(__METHOD__ . ':' . $e->getMessage());
        }
        return $msg;
    }
    /**
     * If $data is an array, calls $method for each element
     *
     * @param mixed $data : data to be added to queued items
     * @param string $method : to|cc|bcc
     * @param PHPMailer $mail : PHPMailer instance
     * @return void
     */
    public static function queueOutbound($data, string $method, PHPMailer $mail) : void
    {
        if (!empty($data)) {
            switch ($method) {
                case 'cc' :
                    $method = 'addCC';
                    break;
                case 'bcc' :
                    $method = 'addBCC';
                    break;
                case 'to' :
                default :
                    $method = 'addAddress';
                    break;
            }
            if (is_array($data)) {
                foreach ($data as $item)
                    $mail->$method($item);
            } else {
                $mail->$method($data);
            }
        }
    }
    /**
     * Uses PHPMailer to validate email address
     *
     * @param string $email     : email address to validate
     * @param string $msg       : pass message by reference
     * @param bool $mx_check    : set TRUE to also perform an MX check (takes longer)
     * @param string $attach_fn : checks to see if file exists; empty $attach_fn == skips this check
     * @return bool
     */
    public static function validateEmail(string $email, string &$msg = '', bool $mx_check = FALSE, string $attach_fn = '')
    {
        $expected = 2;
        $actual   = 0;
        $actual  += (int) PHPMailer::validateAddress($email);
        if ($actual === 1 && $mx_check === TRUE) {
            $expected++;
            [$user, $host] = explode('@', $email);
            $actual += (int) checkdnsrr($host, 'MX');
            $msg = static::ERR_MX;
        }
        $actual += (empty($attach_fn)) ? 1 : (int) file_exists($attach_fn);
        $valid = ($actual === $expected);
        if (!$valid)
            error_log(__METHOD__ . ':' . static::DEFAULT_ERROR . ':' . $email);
        return $valid;
    }
}
