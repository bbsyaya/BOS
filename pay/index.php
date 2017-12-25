
<?php
/**
说明 仅作接口调用功能测试 PC收银台
 */
include 'demo.php';

$oder = $s ->datetime2string(date('Y-m-d H:i:s'));
$orders = $s ->get_code($oder);
echo $orders ;






