<?php
/**
 * 邮件函数库文件，依赖PHPMailer（http://phpmailer.worxware.com）
 * @package W2
 * @author 琐琐
 * @since 1.0
 * @version 1.0
 */

class W2Mail {

    const REQUIRE_PATH = '../PHPMailer/class.phpmailer.php';

    /**
     * 检查依赖项是否存在
     * @return true|false
     */
    private static function checkRequire(){
        $_libPath = __dir__.'/'.W2Mail::REQUIRE_PATH;
        if(class_exists('PHPMailer')){
            return true;
        } else if(file_exists($_libPath)){
            include($_libPath);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 发送邮件，使用gmail帐户，返回发送结果
     * @param string $p_toMail 收件人地址
     * @param string $p_subject 主题
     * @param string $p_content 正文
     * @param array  $p_attachment 附件
     * @param string $p_senderName 发件人名称
     * @param string $p_senderMail 发件人地址，必须为gmail账户
     * @param string $p_senderPassword 发件人密码
     * @return array 结果
     */
    public static function sendMailWithGmail($p_toMail, $p_subject, $p_content, $p_attachment=null, $p_senderName, $p_senderMail, $p_senderPassword) {
        $_r = array();
        if(!W2Mail::checkRequire()){
            $_r['result'] = 3;
            $_r['message'] = 'require PHPMailer';
        } else {
            $mail = new PHPMailer(true);
            $mail->IsSMTP();

            try {
                $mail->CharSet    = 'UTF-8';
                $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
                $mail->SMTPDebug  = 0;                     // enables SMTP debug information (for testing)
                                                           // 1 = errors and messages
                                                           // 2 = messages only
                $mail->SMTPAuth   = true;                  // enable SMTP authentication
                $mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
                $mail->Port       = 465;                   // set the SMTP port for the GMAIL server
                $mail->Username   = $p_senderMail;          // GMAIL username
                $mail->Password   = $p_senderPassword;            // GMAIL password
                $mail->AddReplyTo($p_senderMail, $p_senderName);
                $mail->AddAddress($p_toMail);
                $mail->SetFrom($p_senderMail, $p_senderName);
                $mail->Subject = $p_subject;
                $mail->MsgHTML($p_content);
                if (is_array($p_attachment)) {
                    foreach ($p_attachment as $_a) {
                        if (file_exists($_a)) {
                            $mail->AddAttachment($_a);
                        }
                    }
                }
                $mail->Send();
                $_r['result'] = 0;
            } catch (phpmailerException $e) {
                $_r['result'] = 1;
                // $_r['message'] = $e->errorMessage();
                $_r['message'] = $e->getMessage();
            } catch (Exception $e) {
                $_r['result'] = 2;
                $_r['message'] = $e->getMessage();
            }
        }
        return $_r;
    }
}

/*
if (isset($argv) && is_array($argv) && realpath($argv[0])==__file__) {
    $_r = W2Mail::sendMailWithGmail('wantiegang@gmail.com', '邮件主题', '<html><head></head><body>查看列表内容<ul><li>1</li><li>2</li><li>3</li></ul></body></html>',
        array(),
        'AppJK Team', 'appjk.team@gmail.com', ''
    );
    var_dump($_r);
}
*/

?>