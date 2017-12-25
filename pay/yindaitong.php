<?php
/**
 * Created by PhpStorm.
 * User: h
 * Date: 2017-07-24
 * Time: 8:23
 * 银贷通代收签约协议接口,本页面仅作接口测试。
 */

include 'demo.php';

$no = $s->ECBEncrypt("370705197804099954","shanghu_test");
$ds = $s->curl_https_inner($s->get_inner($no));
var_dump($no);


