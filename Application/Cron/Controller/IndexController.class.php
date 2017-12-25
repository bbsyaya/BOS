<?php
namespace Cron\Controller;
use Think\Controller;

/**
 * Class IndexController
 * @package Cron\Controller
 */
class IndexController extends Controller  {

	public function index() {

		echo 'log' . '<br>';
		/*\SeasLog::setBasePath(ACTION_LOG_PATH);//
		\SeasLog::setLogger('test');//*/
		/*\SeasLog::log(SEASLOG_INFO, 'testttttesetstset');
		$arr   = \SeasLog::analyzerDetail(SEASLOG_INFO, '*', NULL, 1, 50, SEASLOG_DETAIL_ORDER_ASC);*/
		//$count =  \SeasLog::analyzerCount(SEASLOG_INFO,date('Ymd',time()));

		//$arr = actionlog_analyzer('test',SEASLOG_INFO);

		/*$arr = actionlog_analyzer('test',SEASLOG_INFO);
		P($arr,true,true);*/

		/*
		$arr   = \SeasLog::analyzerDetail(SEASLOG_INFO, '*', NULL, 1, 20, SEASLOG_DETAIL_ORDER_ASC);
		$count =  \SeasLog::analyzerCount(SEASLOG_INFO, '*', '');
		var_dump($arr,$count);*/
		/*for($i=0;$i<20;$i++) {
			action_log('test', SEASLOG_INFO, 'admin', '记录-xxx....YYYZZZZ','kw'.$i);
		}
		echo 'ok';*/


	}

}