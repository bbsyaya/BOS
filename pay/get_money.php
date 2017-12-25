

<?php
/**
仅作接口测试 余额查询接口
 */
include 'demo.php';


$money =$s ->curl_https( $s ->get_money());
var_dump($money);
