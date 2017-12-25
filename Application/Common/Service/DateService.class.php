<?php
/**
* 时间处理service
*/
namespace Common\Service;
class DateService
{
	

	/**
	* 获取指定日期之间的各个周
	*/
	public function get_weeks($sdate, $edate) {
		$range_arr = array();
		// 检查日期有效性
		$this->check_date(array($sdate, $edate));
		// 计算各个周的起始时间
		do {
			$weekinfo            = $this->get_weekinfo_by_date($sdate);
			$end_day             = $weekinfo['week_end_day'];
			$start               = $this->substr_date($weekinfo['week_start_day']);
			$end                 = $this->substr_date($weekinfo['week_end_day']);
			$range["start_time"] = $start;
			$range["end_time"]   = $end;
			
			$range_arr[]         = $range;
			$sdate               = date('Y-m-d', strtotime($sdate)+7*86400);
		}while($end_day < $edate);
		return $range_arr;
	}

	/**
	* 检查日期的有效性 YYYY-mm-dd
	* @param array $date_arr
	* @return boolean
	*/
	public function check_date($date_arr) {
		$invalid_date_arr = array();
		foreach ($date_arr as $row) {
			$timestamp    = strtotime($row);
			$standard     = date('Y-m-d', $timestamp);
			if ($standard != $row) $invalid_date_arr[] = $row;
		}
		if ( ! empty($invalid_date_arr)) {
			return false;
		}
	} 

	/**
	* 根据指定日期获取所在周的起始时间和结束时间
	*/
	public function get_weekinfo_by_date($date) {
		$idx     = strftime("%u", strtotime($date));
		$mon_idx = $idx - 0;
		$sun_idx = $idx - 6;
		return array(
			'week_start_day' => strftime('%Y-%m-%d', strtotime($date) - $mon_idx * 86400),
			'week_end_day'   => strftime('%Y-%m-%d', strtotime($date) - $sun_idx * 86400),
		);
	}

	/**
	* 截取日期中的月份和日
	* @param string $date
	* @return string $date
	*/
	public function substr_date($date) {
		if ( ! $date) return FALSE;
		return date('Y-m-d', strtotime($date));
	}

	/**
	* 获取指定日期之间的各个月
	*/
	public function get_months($sdate, $edate) {
		$range_arr = array();
		do {
			$monthinfo   = $this->get_monthinfo_by_date($sdate);
			$end_day     = $monthinfo['month_end_day'];
			$start       = $this->substr_date($monthinfo['month_start_day']);
			$end         = $this->substr_date($monthinfo['month_end_day']);
			$range       = "{$start} ~ {$end}";
			$range_arr[] = $range;
			$sdate       = date('Y-m-d', strtotime($sdate.'+1 month'));
		}while($end_day < $edate);
		return $range_arr;
	}


	/**
	* 获取日期的周数
	* @param  [type] $date [description]
	* @return [type]       [description]
	*/
	function getMonthWeeks($date){
		$ret        = array();
		$stimestamp = strtotime($date);
		$mdays      = date('t',$stimestamp);
		$msdate     = date('Y-m-d',$stimestamp);
		$medate     = date('Y-m-'.$mdays,$stimestamp);
		$etimestamp = strtotime($medate);
		
		//第一周
		$w          = date('w',$stimestamp);
		if($w>0){
			$zcsy            = 6 - $w;//第一周去掉第一天還有幾天
			$zcs1            = $msdate;
			$zce1            = date('Y-m-d',strtotime("+$zcsy day",$stimestamp));
			$one['starTime'] = $zcs1;
			$one["endTime"]  = $zce1;
			$ret[1]          = $one;
		}else{
			$stimestamp = $stimestamp+604800;
		}



		//獲取中間周次
		$jzc  = 0;
		//獲得當前月份是6周次還是5周次
		$jzc0 = "";
		$jzc6 = "";
		for($i = $stimestamp; $i<= $etimestamp; $i+=86400){
			if(date('w', $i) == 0){$jzc0++;}
			if(date('w', $i) == 6){$jzc6++;}
		}
		if($jzc0==5 && $jzc6==5){
			$jzc=5;
		}else if($jzc0==4 && $jzc6==3){
			$jzc=5;
		}else if($jzc0==4 && $jzc6==4){
		$jzc=4;
			$msdate = $zce1;
		}else{
			$jzc=4;
		}

		// print_r($jzc);
		date_default_timezone_set('PRC');
		$t = strtotime('+1 monday '.$msdate);
		
		$n = 1;
		for($n=1; $n<$jzc; $n++) {
			$b               = strtotime("+$n week -1 week", $t);
			$dsdate          = date("Y-m-d", strtotime("-1 day", $b));
			$dedate          = date("Y-m-d", strtotime("5 day", $b));
			$jzcz            = $n+1;
			
			$one['starTime'] = $dsdate;
			$one["endTime"]  = $dedate;
			$ret[$jzcz]      = $one;
		}
		//獲取最後一周
		$zcsy            = date('w',$etimestamp);//最後一周是周幾日~六 0~6
		$zcs1            = date('Y-m-d',strtotime("-$zcsy day",$etimestamp));
		$zce1            = $medate;
		$jzcz            = $jzc+1;
		
		$one['starTime'] = $zcs1;
		$one["endTime"]  = $zce1;
		$ret[$jzcz]      = $one;
		return $ret;
	}


	/**
	 * 获取本周时间
	 * @return [type] [description]
	 */
	function getThisWeek(){
		$time["s_time"] = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")));
	    $time["e_time"] = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")));
	    return $time;
	}

	/**
	 * 获取上周
	 * @return [type] [description]
	 */
	function getLastWeek(){
		$time["s_time"] = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1-7,date("Y")));
	    $time["e_time"] = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7-7,date("Y")));
	    return $time;
	}

	/**
	 * 获取本月
	 * @return [type] [description]
	 */
	function getThisMonth(){
		$time["s_time"] = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
	    $time["e_time"] = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y")));
	    return $time;
	}

	/**
	 * 获取上月
	 * @return [type] [description]
	 */
	function getLastMonth(){
		$time["s_time"] = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m")-1,1,date("Y")));
	    $time["e_time"] = date("Y-m-d H:i:s",mktime(23,59,59,date("m") ,0,date("Y")));
	    return $time;
	}

	/**
	* 求两个日期之间相差的天数
	* (针对1970年1月1日之后，求之前可以采用泰勒公式)
	* @param string $day1
	* @param string $day2
	* @return number
	*/
	function diffBetweenTwoDays ($day1, $day2){
		$second1 = strtotime($day1);
		$second2 = strtotime($day2);

		if ($second1 < $second2) {
			$tmp     = $second2;
			$second2 = $second1;
			$second1 = $tmp;
		}
		return ($second1 - $second2) / 86400;
	}

}
?>