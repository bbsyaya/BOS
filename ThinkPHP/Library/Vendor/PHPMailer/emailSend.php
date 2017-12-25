<?php
/**
* 发送邮件类
*/
class emailSend {
    
    /**
     * 发送邮件
     * @param  需要发送的参数集合
     * @return [type]         [description]
     */
    function send($config)
    {
     
        require_once("class.phpmailer.php"); //下载的文件必须放在该文件所在目录
        $mail = new PHPMailer();
        $mail->isSMTP();    
        // $mail->SMTPDebug = 2;    //调试模式
        $mail->Host = C("EMAIL_HOST");  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->CharSet = "UTF-8";//编码格式
        $mail->Username = C("EMAIL_USERNAME");                 // SMTP username
        $mail->Password = C("EMAIL_PWD");                           // SMTP password

        $mail->setFrom(C("EMAIL_USERNAME"), C("EMAIL_USERNAME"));
        $mail->addAddress($config["recpEmailAddress"], $config["recpEmailAddress"]);     // Add a recipient
        $mail->isHTML(true);        //发送正文是否带有html标签  
        $mail->Subject = $config["subject"];
        
        // $mail->AddEmbeddedImage($config["imgUrl"], "img", $config["imgUrl"]); //设置邮件中的图片
        $mail->Body    = $config["body"]; //发送带有html标签文本
        // $mail->AltBody    = $config["body"]; //发送不带有html标签文本
        // 发送带文件
        if($config["file"]){
            $mail->msgHTML($config["file"], dirname(__FILE__));
        }
        if($config["imgUrl"]){
            $mail->addAttachment($config["imgUrl"]);
        }

        //抄送
        if($config["cc_user_list"]){
            foreach ($config["cc_user_list"] as $k => $v) {
                $mail->addCC($v); 
            }
        }

        // $res = $mail->addAttachment("2e3f19fd-8e3f-405b-9854-41dd19ddebf8.jpg");
        // var_dump($res);exit;
        $result = array("status"=>0,"msg"=>"");
        if(!$mail->send()) {
            $result["msg"] = 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            $result["status"] = 1;
        }
        return $result;
    }
    
}

?>
