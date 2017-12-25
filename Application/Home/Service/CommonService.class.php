<?php
/**
 * 公共service
 */
namespace Home\Service;
use Think\Model;
use Common\Service;
class CommonService{
 	
	/*
	* token
	* */
	public function getToten(){
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
		$charid    = strtoupper(md5(uniqid(rand(), true)));
		$hyphen    = chr(45);// "-"
		$uuid      = substr($charid, 0, 8).$hyphen
		.substr($charid, 8, 4).$hyphen
		.substr($charid,12, 4).$hyphen
		.substr($charid,16, 4).$hyphen
		.substr($charid,20,12);
		$gcm       = "/finanInter";
		$key       = "1qaz#EDC5tgb&UJM";
		$middle    = base64_encode($gcm.$key);
		$date_time = date('YmdHi',time());
		$date_time = base64_encode($date_time.'L');
		$token     = $uuid.$middle.$date_time;
		return $token;
	}

	/**
	 * 获取某年某月的实际天数
	 * @param  [type] $year_month [description]
	 * @return [type]             [description]
	 */
	function getMonthDays($year_month){
		return date("t",strtotime($year_month));
	}

	/**
	 * 是否跨月 例如：2017-04
	 * @return boolean [description]
	 */
	function isCrossMonth($now_year,$now_month,$com_start,$com_end){
		$days      = $this->getMonthDays($now_year."-".$now_month);
		$now_start = $now_year."-".$now_month."-01";
		$now_end   = $now_year."-".$now_month."-".$days;
		$result    = array("isCross"=>"none","data"=>array());
		//时间戳转换
		$com_start_num = strtotime($com_start);
		$com_end_num   = strtotime($com_end);
		$now_start_num = strtotime($now_start);
		$now_end_num   = strtotime($now_end);

		//取重合的结束的那一天
		if($com_start_num<$now_start_num && $com_end_num==$now_start_num){
			$result = array(
				"isCross"=>true,
				"data"=>array(
					"j_star"=>date("Y-m-d",strtotime($now_start)),
					"j_end"=>date("Y-m-d",strtotime($now_start)),
					)
				);
		}
		//2
		if($com_start_num<=$now_start_num && $com_end_num>$now_start_num){
			$result = array(
				"isCross"=>true,
				"data"=>array(
					"j_star"=>date("Y-m-d",strtotime($now_start)),
					"j_end"=>date("Y-m-d",strtotime($com_end))
					)
				);
		}
		//3
		if($com_start_num<$now_end_num && $com_end_num>$now_end_num){
			$result = array(
				"isCross"=>true,
				"data"=>array(
					"j_star"=>date("Y-m-d",strtotime($com_start)),
					"j_end"=>date("Y-m-d",strtotime($now_end))
					)
				);
		}
		//4
		if($com_start_num==$now_end_num && $com_end_num>$now_end_num){
			$result = array(
				"isCross"=>true,
				"data"=>array(
					"j_star"=>date("Y-m-d",strtotime($com_start)),
					"j_end"=>date("Y-m-d",strtotime($now_end))
					)
				);
		}
		//5
		if($com_start_num<$now_start_num && $com_end_num>$now_end_num){
			$result = array(
				"isCross"=>true,
				"data"=>array(
					"j_star"=>date("Y-m-d",strtotime($now_start)),
					"j_end"=>date("Y-m-d",strtotime($now_end))
					)
				);
		}

		if($com_start_num>=$now_start_num && $com_end_num<=$now_end_num){
			$result = array("isCross"=>false,"data"=>array());
		}
		return $result;
	}

	/**
	* @param $year 给定的年份
	* @param $month 给定的月份
	* @param $legth 筛选的区间长度 取前六个月就输入6
	* @param int $page 分页
	* @return array
	根据传入的日期，返回规范的日期格式
	*/
	public function getLastTimeArea($year,$month,$legth,$page=1){
		if (!$page) {
			$page = 1;
		}
		$monthNum = $month + $legth - $page*$legth;
		$num = 1;
		if ($monthNum < -12) {
			$num = ceil($monthNum/(-12));
		}
		$timeAreaList = [];
		for($i=0;$i<$legth;$i++) {
			$temMonth = $monthNum - $i;
			$temYear = $year;
			if ($temMonth <= 0) {
				$temYear = $year - $num;
				$temMonth = $temMonth + 12*$num;
				if ($temMonth <= 0) {
					$temMonth += 12;
					$temYear -= 1;
				}
			}
			$startMonth = strtotime($temYear.'-'.$temMonth.'-01');//该月的月初时间戳
			$endMonth = strtotime($temYear.'-'.($temMonth + 1).'-01') - 86400;//该月的月末时间戳
			$res['startMonth'] = $temYear.'-'.$temMonth.'-01'; //该月的月初格式化时间
			$res['endMonth'] = date('Y-m-d',$endMonth);//该月的月末格式化时间
			$res['timeArea'] = implode(',',[$startMonth, $endMonth]);//区间时间戳
			$timeAreaList[] = $res;
		}
		return $timeAreaList;
	}
	/**
	 * 设置验证
	 */
	public function setValidate(){
		//验证
		$params["appid"] = "20170613bosstoyongyou";
		$params["ts"]    = time();
		$appSecret       = "20170613bosstofinance";
		$params["sign"]  = md5($appSecret.$params["ts"]);
		return $params;
	}

	/**
	 * [writeLogs 用友日志]
	 * @param  [type] $url         [description]
	 * @param  [type] $postArray   [description]
	 * @param  [type] $responsData [description]
	 * @param  [type] $type        [description]
	 * @return [type]              [description]
	 */
	public function writeFinLogs($url,$postArray,$responsData,$type){
		//+++++++++++++用友日志
		$data["postUrl"]  = $url;
		$data["postData"] = json_encode($postArray,JSON_UNESCAPED_UNICODE);
		$data["data"]     = $responsData;
		$data["type"]     = 2;
		$data["dateline"] = date("Y-m-d H:i:s",time());
		$finLogSer        = new Service\FinanceLogService();
		$finLogSer->writeLog($data);
		//+++++++++++++用友日志
	}
 } 
?>