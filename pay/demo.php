<?php
/**
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究银盛支付接口使用，只是提供一个参考。
 */
class demo
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     * @return void
     */
    function __construct()
    {
        $this->demo();
        date_default_timezone_set('PRC');
        define('BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . "/");
    }

    /**
     * 仅为实例化商户加密证书 银盛公钥 商户加密证书密码
     */
    function demo()
    {

        $this->param = array();
        $this->param['pfxpath'] = 'http://' . $_SERVER['HTTP_HOST'] . "/pay/certs/shanghu_test.pfx";
        $this->param['businessgatecerpath'] = 'http://' . $_SERVER['HTTP_HOST'] . "/pay/certs/businessgate.cer";
        $this->param['pfxpassword'] = "123456";
    }

    /**
     * PC收银台接口 测试环境仅需使用pc收银台->网银支付,作为商户测试环境校验.
     */
    /*
    function get_code($order)
    {

        $myParams = array();
        $myParams['business_code'] = '01000010';
        $myParams['charset'] = 'utf-8';
        $myParams['method'] = 'ysepay.online.directpay.createbyuser';
        $myParams['notify_url'] = 'http://yspay.ngrok.cc/pay/notify.php';
        $myParams['out_trade_no'] = $order;
        $myParams['partner_id'] = 'shanghu_test';
        $myParams['return_url'] = 'http://yspay.ngrok.cc/pay/respond.php';
        $myParams['seller_id'] = 'shanghu_test';
        $myParams['seller_name'] = '银盛支付商户测试公司';
        $myParams['sign_type'] = 'RSA';
        $myParams['subject'] = '支付测试';
        $myParams['timeout_express'] = '1d';
        $myParams['timestamp'] = date('Y-m-d H:i:s', time());
        $myParams['total_amount'] = '3000';
        $myParams['version'] = '3.0';
//        网银直连需添加以下参数
//        $myParams['pay_mode']           = 'internetbank';
//        $myParams['bank_type']           = '';
//        $myParams['bank_account_type']           = 'personal';
//        $myParams['support_card_type']           = 'debit';
        ksort($myParams);
        $data = $myParams;
        $signStr = "";
        foreach ($myParams as $key => $val) {
            $signStr .= $key . '=' . $val . '&';
        }
        $signStr = rtrim($signStr, '&');
        $sign = $this->sign_encrypt(array('data' => $signStr));
        $myParams['sign'] = trim($sign['check']);
        var_dump($myParams);
        $action = "https://mertest.ysepay.com/openapi_gateway/gateway.do";
        //$action = "https://openapi.ysepay.com/gateway.do";
        $def_url = "<br /><form style='text-align:center;' method=post action='" . $action . "' target='_blank'>";
        while ($param = each($myParams)) {
            $def_url .= "<input type = 'hidden' id='" . $param['key'] . "' name='" . $param['key'] . "' value='" . $param['value'] . "' />";
        }
        $def_url .= "<input type=submit value='点击提交' " . @$GLOBALS['_LANG']['pay_button'] . "'>";
        $def_url .= "</form>";

        return $def_url;
    }
*/
    /**
     * 说明 余额查询接口
     */
    function get_money()
    {
        $myParams = array();
        $myParams['charset'] = 'utf-8';
        $myParams['method'] = 'ysepay.online.user.account.get';
        $myParams['partner_id'] = 'shanghu_test';
        $myParams['sign_type'] = 'RSA';
        $myParams['timestamp'] = date('Y-m-d H:i:s', time());
        $myParams['version'] = '3.0';

        $biz_content_arr = array(
            "user_code" => "shanghu_test",
            "user_name" => "银盛支付商户测试公司"
        );
        $myParams['biz_content'] = json_encode($biz_content_arr, JSON_UNESCAPED_UNICODE);//构造字符串
        ksort($myParams);
        $signStr = "";
        foreach ($myParams as $key => $val) {
            $signStr .= $key . '=' . $val . '&';
        }
        $signStr = rtrim($signStr, '&');
        $sign = $this->sign_encrypt(array('data' => $signStr));
        $myParams['sign'] = trim($sign['check']);
        return $myParams;
        var_dump($myParams);
    }

    /**
     * 说明:单笔代付加急接口
     */
    function get_dfjj($order)
    {
        $myParams = array();
        $myParams['charset'] = 'utf-8';
        $myParams['method'] = 'ysepay.df.single.quick.accept';
        $myParams['notify_url'] = 'http://yspay.ngrok.cc/pay/respond_notify.php';
        $myParams['partner_id'] = 'shanghu_test';
        $myParams['sign_type'] = 'RSA';
        $myParams['timestamp'] = date('Y-m-d H:i:s', time());
        $myParams['version'] = '3.0';
        $biz_content_arr = array(
            "out_trade_no" => "$order",
            "business_code" => "01000009",
            "currency" => "CNY",
            "total_amount" => "100.00",
            "subject" => "测试",
            "bank_name" => "工商银行深圳支行",
            "bank_city" => "深圳市",
            "bank_account_no" => "621483782233747700",
            "bank_account_name" => "工行",
            "bank_account_type" => "personal",
            "bank_card_type" => "debit"
        );
        $myParams['biz_content'] = json_encode($biz_content_arr, JSON_UNESCAPED_UNICODE);//构造字符串
        var_dump($myParams);
        ksort($myParams);
        $signStr = "";
        foreach ($myParams as $key => $val) {
            $signStr .= $key . '=' . $val . '&';
        }
        $signStr = rtrim($signStr, '&');
        var_dump($signStr);
        $sign = $this->sign_encrypt(array('data' => $signStr));
        $myParams['sign'] = trim($sign['check']);
        var_dump($myParams);
        return $myParams;
    }

    /**
     *  支付宝二维码接口 测试环境无法模拟真实场景 仅作同步验签 商户自行修改商户号 商户名等参数
     */
    /*
    function get_alipay($order)
    {
        $myParams = array();
        $myParams['charset'] = 'utf-8';
        $myParams['method'] = 'ysepay.online.qrcodepay';
        $myParams['partner_id'] = 'shanghu_test';
        $myParams['sign_type'] = 'RSA';
        $myParams['timestamp'] = date('Y-m-d H:i:s', time());
        $myParams['version'] = '3.0';
        $myParams['return_url'] = 'http://yspay.ngrok.cc/pay/respond.php';
        $myParams['notify_url'] = 'http://yspay.ngrok.cc/pay/respond_notify.php';
        $biz_content_arr = array(
            "out_trade_no" => "$order",
            "subject" => "测试扫码",
            "total_amount" => "10",
            "seller_id" => "shanghu_test",
            "seller_name" => "银盛支付商户测试公司",
            "timeout_express" => "24h",
            "business_code" => "01000010",
            "bank_type" => "1903000"
        );
        $myParams['biz_content'] = json_encode($biz_content_arr, JSON_UNESCAPED_UNICODE);//构造字符串
        ksort($myParams);
        var_dump($myParams);
        $signStr = "";
        foreach ($myParams as $key => $val) {
            $signStr .= $key . '=' . $val . '&';
        }
        $signStr = rtrim($signStr, '&');
        var_dump($signStr);
        $sign = $this->sign_encrypt(array('data' => $signStr));
        $myParams['sign'] = trim($sign['check']);
        var_dump($myParams['sign'] );
        return $myParams;
        var_dump($myParams);
    }
*/
    /**
     * 代收签约接口 传入已加密的证件号码
     */
    function get_inner($no){
        $myParams = array();
        $myParams['charset'] = 'utf-8';
        $myParams['method'] = 'ysepay.ds.protocol.single.accept';
        $myParams['partner_id'] = 'shanghu_test';
        $myParams['sign_type'] = 'RSA';
        $myParams['timestamp'] = date('Y-m-d H:i:s', time());
        $myParams['version'] = '3.0';
        $myParams['notify_url'] = 'http://yspay.ngrok.cc/pay/respond_notify.php';
        $biz_content_arr = array(
            "protocol_no" => 'DS' .$this->datetime2string(date('Y-m-d H:i:s')),
            "business_code" => "1010004",
      /* 生效和失效时间由商户和客户进行协商,生效期内无需重复签约,使用协议号进行交易操作即可 */
            "effect_date" => "20180101",
            "expire_date" => "20180701",
            "bank_account_type" => "personal",
            "bank_card_type" => "debit",
            "bank_name" => "工商银行深圳支行",
            "bank_account_no" => "621483782233747700",
            "bank_account_name" => "工行",
            "bank_city" => "深圳市",
            "bank_telephone_no" => "13821382138",
            "cert_type" => "00",
            "cert_no" => $no,
            "cert_expire"=>"20200808",
            "month_num_limit"=>"10000",
            "month_amount_limit"=>"1000000"
        );
        $myParams['biz_content'] = json_encode($biz_content_arr,320);//构造字符串
        ksort($myParams);
        var_dump($myParams);
        $signStr = "";
        foreach ($myParams as $key => $val) {
            $signStr .= $key . '=' . $val . '&';
        }
        $signStr = rtrim($signStr, '&');
        var_dump($signStr);
        $sign = $this->sign_encrypt(array('data' => $signStr));
        $myParams['sign'] = trim($sign['check']);
        var_dump($myParams['sign'] );
        return $myParams;
        var_dump($myParams);
    }

    /**
     * 同步响应操作
     */
    function respond()
    {

        //返回的数据处理
        $sign = trim($_POST['sign']);
        $result = $_POST;
        unset($result['sign']);
        ksort($result);
        $url = "";
        foreach ($result as $key => $val) {
            if ($val) $url .= $key . '=' . $val . '&';
        }
        $data = trim($url, '&');
        var_dump($data);
        /*写入日志*/
        $file = BASE_PATH . "log/respond.txt";
        file_put_contents($file, "\r\n", FILE_APPEND);
        file_put_contents($file, "return|data:" . $data . "|sign:" . $sign, FILE_APPEND);
        /* 验证签名 仅作基础验证*/
        if ($this->sign_check($sign, $data) == true) {
            echo "验证签名成功!";
        } else {
            echo '验证签名失败!';
        }
        /* 验证签名,并更改订单状态*/
//    if($this->sign_check($sign,$data)  != true){
//        echo "验证签名失败！";
//        exit;
//    }
//    if($result['trade_status'] == 'TRADE_SUCCESS'){
//        /* 改变订单状态 */
//        order_paid($result['out_trade_no']);
//        return true;
//    }else{
//        return false;
//    }

    }

    /**
     * 异步响应操作
     */
    function respond_notify()
    {
        //返回的数据处理
        @$sign = trim($_POST['sign']);
        $result = $_POST;
        unset($result['sign']);
        ksort($result);
        $url = "";
        foreach ($result as $key => $val) {
            if ($val) $url .= $key . '=' . $val . '&';
        }
        $data = trim($url, '&');
        /*写入日志*/
        $file = BASE_PATH . "log/notify.txt";
        /* 验证签名 仅作基础验证*/
        if ($this->sign_check($sign, $data) == true) {
            file_put_contents($file, "\r\n", FILE_APPEND);
            file_put_contents($file, "Verify success!|notify|:" . $data . "|sign:" . $sign, FILE_APPEND);
        } else {
            file_put_contents($file, "\r\n", FILE_APPEND);
            file_put_contents($file, "Validation failure!|notify|:" . $data . "|sign:" . $sign, FILE_APPEND);
        }
        /*

           开发须知:

           收到异步通知后,必须响应success给银盛,用于告诉银盛已成功接收到异步消息,
           多次不返回success的商户银盛将不会往商户异步地址发送异步消息(并拉黑商户异步地址)


         */
        echo 'success';
        exit;
        /* 验证签名,并更改订单状态*/
//    if($this->sign_check($sign,$data) != true){
//        echo "fail";
//        exit;
//    }else{
//        if($result['trade_status']  == 'TRADE_SUCCESS'){
//             order_paid($result['out_trade_no']);
//        }
//        /*写入日志*/
//        $file = BASE_PATH. "log/ok.txt";
//        file_put_contents( $file, "\r\n", FILE_APPEND);
//        file_put_contents( $file,'success:1' , FILE_APPEND);
//        echo 'success';
//        exit;
//    }

    }

    /**
     *日期转字符
     *输入参数：yyyy-MM-dd HH:mm:ss
     *输出参数：yyyyMMddHHmmss
     */
    function datetime2string($datetime)
    {

        return preg_replace('/\-*\:*\s*/', '', $datetime);
    }

    /**
     * 验签转明码
     * @param input check
     * @param input msg
     * @return data
     * @return success
     */

    function sign_check($sign, $data)
    {

        $publickeyFile = $this->param['businessgatecerpath']; //公钥
        $certificateCAcerContent = file_get_contents($publickeyFile);
        $certificateCApemContent = '-----BEGIN CERTIFICATE-----' . PHP_EOL . chunk_split(base64_encode($certificateCAcerContent), 64, PHP_EOL) . '-----END CERTIFICATE-----' . PHP_EOL;
        // 签名验证
        $success = openssl_verify($data, base64_decode($sign), openssl_get_publickey($certificateCApemContent), OPENSSL_ALGO_SHA1);

        return $success;
    }


    /**
     * 签名加密
     * @param input data
     * @return success
     * @return check
     * @return msg
     */
    function sign_encrypt($input)
    {

        $return = array('success' => 0, 'msg' => '', 'check' => '');
        $pkcs12 = file_get_contents($this->param['pfxpath']); //私钥
        if (openssl_pkcs12_read($pkcs12, $certs, $this->param['pfxpassword'])) {
            $privateKey = $certs['pkey'];
            $publicKey = $certs['cert'];
            $signedMsg = "";
            if (openssl_sign($input['data'], $signedMsg, $privateKey, OPENSSL_ALGO_SHA1)) {
                $return['success'] = 1;
                $return['check'] = base64_encode($signedMsg);
                $return['msg'] = base64_encode($input['data']);

            }
        }

        return $return;
    }

    /**
     * 数组转字符串
     */
    function arrayToString($arr)
    {
        if (is_array($arr)) {
            return implode(',', array_map('arrayToString', $arr));
        }
        return $arr;
    }

    /**
     * DES加密方法
     * @param $data 传入需要加密的证件号码
     * @param $key key为商户号前八位.不足八位的需在商户号末尾补0
     * @return string 返回加密后的字符串
     */
    function ECBEncrypt($data,$key)
    {
        $encrypted = openssl_encrypt($data,'DES-ECB',$key,1);
        return base64_encode($encrypted);
    }

    /**
     * DES解密方法
     * @param $data 传入需要解密的字符串
     * @param $key key为商户号前八位.不足八位的需在商户号末尾补0
     * @return string 返回解密后的证件号码
     */
    function doECBDecrypt($data,$key)
    {
        $encrypted = base64_decode($data);
        $decrypted = openssl_decrypt($encrypted, 'DES-ECB', $key, 1);
        return $decrypted;
    }
    /** curl 获取 https 请求 余额查询
     * @param qa 要发送的数据
     */
    function curl_https($qa)
    {
        $ch = curl_init("https://mertest.ysepay.com/openapi_gateway/gateway.do");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($qa));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            var_dump($ch);
        } else {
            $response = json_decode($response, true);
            var_dump($response);
            $sign = $response['sign'];
            echo $sign;
            $data = json_encode($response['ysepay_online_user_account_get_response'], JSON_UNESCAPED_UNICODE);
            $data = $this->arrayToString($data);
            var_dump($data);
            /* 验证签名 仅作基础验证*/
            if ($this->sign_check($sign, $data) == true) {
                echo "验证签名成功!";
            } else {
                echo '验证签名失败!';
            }
        }
    }

    /** curl 获取 https 请求 单笔代付加急
     * @param qa 要发送的数据
     */
    function curl_https_df($qa)
    {
        $ch = curl_init("https://mertest.ysepay.com/openapi_dsf_gateway/gateway.do");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($qa));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        var_dump($response);
        if (curl_errno($ch)) {
            var_dump($ch);
        } else {
            $response = json_decode($response, true);
            var_dump($response);
            $sign = $response['sign'];
            echo $sign;
            $data = json_encode($response['ysepay_df_single_quick_accept_response'], JSON_UNESCAPED_UNICODE);
            $data = $this->arrayToString($data);
            var_dump($data);
            /* 验证签名 仅作基础验证*/
            if ($this->sign_check($sign, $data) == true) {
                echo "验证签名成功!";
            } else {
                echo '验证签名失败!';
            }
        }
    }

    /** curl 获取 https 请求 支付宝等二维码接口 仅在正式环境下有效.
     * @param qa 要发送的数据
     */
    function curl_https_alipay($qa)
    {
        $ch = curl_init("https://qrcode.ysepay.com/gateway.do");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($qa));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        var_dump($response);
        if (curl_errno($ch)) {
            var_dump($ch);
        } else {
            $response = json_decode($response, true);
            var_dump($response);
            $sign = $response['sign'];
            echo $sign;
            $data = json_encode($response['ysepay_online_qrcodepay_response'],320);
            var_dump($data);
            /* 验证签名 仅作基础验证*/
            if ($this->sign_check($sign, $data) == true) {
                echo "验证签名成功!";
            } else {
                echo '验证签名失败!';
            }
        }
    }


/** curl 获取 https 请求 代收签约协议
 * @param qa 要发送的数据
 */
function curl_https_inner($qa)
{
    $ch = curl_init("https://mertest.ysepay.com/openapi_dsf_gateway/gateway.do");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($qa));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    var_dump($response);
    if (curl_errno($ch)) {
        var_dump($ch);
    } else {
        $response = json_decode($response, true);
        var_dump($response);
        $sign = $response['sign'];
        echo $sign;
        $data = json_encode($response['ysepay_ds_protocol_single_accept_response'], JSON_UNESCAPED_UNICODE);
        $data = $this->arrayToString($data);
        var_dump($data);
        /* 验证签名 仅作基础验证*/
        if ($this->sign_check($sign, $data) == true) {
            echo "验证签名成功!";
        } else {
            echo '验证签名失败!';
        }
    }
}
}
/**
 * 测试接口
 */
$s = new demo();