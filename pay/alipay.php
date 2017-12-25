<?php
include 'demo.php';

$oder = $s ->datetime2string(date('Y-m-d H:i:s'));
$money =$s ->curl_https_alipay( $s ->get_alipay($oder));
var_dump($money);
