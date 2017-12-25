<?php

include 'demo.php';
$oder = $s ->datetime2string(date('Y-m-d H:i:s'));


$t = $s->curl_https_df($s->get_dfjj($oder));
echo  $t;

