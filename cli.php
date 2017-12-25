<?php
//计划任务cron模块: */1  *  *  *   * root /opt/remi/php70/root/usr/bin/php /data/wwwroot/boss/cli.php Index/index >/dev/null 2>&1
define('__ROOT__',  dirname(__FILE__));
define('_PHP_FILE_', __ROOT__.'/');

define('APP_PATH', __ROOT__.'/Application/');
define('APP_DEBUG',false); //TODO:
define('APP_MODE','cli');
define('BIND_MODULE','Cron');
require __ROOT__.'/ThinkPHP/ThinkPHP.php';
