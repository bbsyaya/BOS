<?php
if($_GET['user']!='gasideggg' || md5(md5($_GET['pw']).'gasidegg')!='2e2f6257e1ab1f21a319d53cf57ea1a7')exit('没有权限');
$data=file_put_contents('/tmp/gitpull.txt', '');
echo '完成';