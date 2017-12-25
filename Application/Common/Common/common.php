<?php
/**
 * 公共辅助类
 */
use Common\Service;

function _uplaodfile($name,$dir){
	$upload = new \Think\Upload();// 实例化上传类
	$upload->maxSize   =     10000000000 ;// 设置附件上传大小
	$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg' , 'xlsx', 'zip' , 'rar', 'xls');// 设置附件上传类型

	$upload->rootPath  =     $dir; // 设置附件上传根目录
	$upload->savePath  =     ''; // 设置附件上传（子）目录

	// 上传文件
	$upload->__set('saveName',time().rand(100,999));
	$info   =   $upload->upload();
	if(!$info) {// 上传错误提示错误信息
		return $upload->getError();
	}else{// 上传成功
		return $info;
	}
}
/**
* 删除文件夹
* @param string $dir 文件夹路径
* @return boolean
*/
function deldir($dir) {
	//先删除目录下的文件：
	$dh=opendir($dir);
	while ($file=readdir($dh)) {
		if($file!="." && $file!="..") {
			$fullpath=$dir."/".$file;
			if(!is_dir($fullpath)) {
				unlink($fullpath);
			} else {
				deldir($fullpath);
			}
		}
	}

	closedir($dh);
	//删除当前文件夹：
	if(rmdir($dir)) {
		return true;
	} else {
		return false;
	}
}

/**
 * [科学计算法转换]
 * @param  [type] $m [description]
 * @param  [type] $n [description]
 * @param  [type] $x [description]
 * @return [type]    [description]
 */
function convertScien($m,$n,$x){
	  $errors=array(
	    '被除数不能为零',
	    '负数没有平方根'
	  );
	  switch($x){
	    case 'add':
	      $t=bcadd($m,$n);
	      break;
	    case 'sub':
	      $t=bcsub($m,$n);
	      break;
	    case 'mul':
	      $t=bcmul($m,$n);
	      break;
	    case 'div':
	      if($n!=0){
	        $t=bcdiv($m,$n);
	      }else{
	        return $errors[0];
	      }
	      break;
	    case 'pow':
	      $t=bcpow($m,$n);
	      break;
	    case 'mod':
	      if($n!=0){
	        $t=bcmod($m,$n);
	      }else{
	        return $errors[0];
	      }
	      break;
	    case 'sqrt':
	      if($m>=0){
	        $t=bcsqrt($m);
	      }else{
	        return $errors[1];
	      }
	      break;
	  }
	  $t=preg_replace("/\..*0+$/",'',$t);
	  return $t;
}

/**
 * PHP计算两个时间段是否有交集（边界重叠不算）
 *
 * @param string $beginTime1 开始时间1
 * @param string $endTime1 结束时间1
 * @param string $beginTime2 开始时间2
 * @param string $endTime2 结束时间2
 */
function get_time_cross($beginTime1, $endTime1, $beginTime2, $endTime2){
    $status = $beginTime2 - $beginTime1;
    $has_j = false;
    if ($status > 0){
        $status2 = $beginTime2 - $endTime1;
        if ($status2 > 0){
            $has_j = false;
        }elseif ($status2 <= 0){
            $has_j = true;
        }else{
            $has_j = false;
        }
    }elseif($status < 0){
        $status2 = $endTime2 - $beginTime1;
        if ($status2 >= 0){
            $has_j = true;
        }else if ($status2 < 0){
            $has_j = false;
        }else{
           $has_j = false;
        }
    }else{
        $status2 = $endTime2 - $beginTime1;
        if ($status2 == 0){
            $has_j = false;
        }else{
            $has_j = true;
        }
    }
	if($has_j){
		$jdate = array(
			"start" =>max($beginTime1,$beginTime2),
			"end"   =>min($endTime1,$endTime2)
			);
		$jdate = array(
			"start" =>date("Y-m-d H:i:s",$jdate['start']),
			"end"   =>date("Y-m-d H:i:s",$jdate['end'])
			);
		return $jdate;
	}
	return false;

}
/**
 * 获取前月份list
 * @return [type] [description]
 */
function getPreMonth(){
	$list = array();
	for ($i=1; $i <13 ; $i++) { 
		$list[] = "前".$i."个月数据";
	}
	return $list;
}

/**
 * 相差年数
 * @param  [type] $year_min [description]
 * @param  [type] $year_max [description]
 * @return [type]           [description]
 */
function getYearDiff($year_min,$year_max){
	$diff = floor(($year_max-$year_min)/86400/365);    //整数年
	return $diff;
}


/*  个税计算方法
 *  个税计算方法： =IF(AG4<=0,0,
 *  IF(AG4<=1500,AG4*0.03,IF(AG4<=4500,AG4*0.1-105,IF(AG4<=9000,AG4*0.2-555,IF(AG4<=35000,AG4*0.25-1005,IF(AG4<=55000,AG4*0.3-2755,IF(AG4<=80000,AG4*0.35-5505,IF(AG4>80000,AG4*0.45-13505,0))))))))    注：AG4为应纳税所得额。
 *  
 * @param float $salary_tax_money 应纳税金额,不是应纳税所得额
 * @param float $threshold 起征金额 默认值为3900
 * @return float | false 返回值为应缴税金额 参数错误时返回false
*/
function getPersonalIncomeTax($salary_tax_money,$threshold=3900){
    if(!is_numeric($salary_tax_money) || !is_numeric($threshold)){
            return false;
    }
    $tax_money = 0;
    if($salary_tax_money <= $threshold){
            return $tax_money;
    }
    $salary_diff = $salary_tax_money - $threshold;//应纳税所得额
    if($salary_diff <= 1500){
    	$tax_money = $salary_diff*0.03;
    }
    if((1500 < $salary_diff) && ($salary_diff <= 4500)){
    	$tax_money = $salary_diff*0.1-105;
    }
    if((4500 < $salary_diff) && ($salary_diff <= 9000)){
    	$tax_money = $salary_diff*0.2-555;
    }
    if((9000 < $salary_diff) && ($salary_diff <= 35000)){
    	$tax_money = $salary_diff*0.25-1005;
    }
    if((35000 < $salary_diff) && ($salary_diff <= 55000)){
    	$tax_money = $salary_diff*0.3-2755;
    }
    if((55000 < $salary_diff) && ($salary_diff <= 80000)){
    	$tax_money = $salary_diff*0.35-5505;
    }
    if($salary_diff > 80000){
    	$tax_money = $salary_diff*0.45-13505;
    }
    return $tax_money;
}

/**
 * 根据公司集合
 * @param  [type] $companyID [description]
 * @return [type]            [description]
 */
function getCompanyList(){
	$result = S("getCompanyList");
	if(!$result){
		$depSer = new Service\DepartSettingService();
		$result =  $depSer->getListByWhere(array("type"=>1),"id,name");
		S("getCompanyList",$result,300);
	}
	if(!$result) return false;
	$list = array();
	foreach ($result as $k => $v) {
		$list[$v["id"]] = $v["name"];
	}
	return $list;
}

/**
 * 获取当前用户是否为超级管理员
 * @param  [type]  $uid [description]
 * @return boolean      [description]
 */
function isSurperAdmin($uid){
	$isAdmin = S("isSurperAdmin_".$uid);
	if(!$isAdmin){
		$userSer = new Service\UserService();
		$user = $userSer->getOneUserAuthByWhere(array("uid"=>$uid),"group_id");
		$isAdmin = 2;//不是超级管理员
		if($user["group_id"]==1){
			$isAdmin = 1;
		}
		S("isSurperAdmin_".$uid,$isAdmin,86400);
	}
	return $isAdmin;
}


/**
 * 根据name获取id
 * @param  [type] $name [description]
 * @return [type]       [description]
 */
function getEducateIdByName($name){
	$educate_list = C("OPTION.educate");
	$key_ = 1;
	foreach ($educate_list as $k => $v) {
		if($v==$name){
			$key_ = $k;
		}else{ continue; }
	}
	return $key_;
}

/**
 * 快速获取
 * @return [type] [description]
 */
function getNameByEducateTree(){
	$educate_list = C("OPTION.educate");
	$new_list = array();
	foreach ($educate_list as $k => $v) {
		$one["key"]     = $k;
		$one["val"]     = $v;
		$new_list[$v][] = $one;
	}
	return $new_list;
}

/**
 * 快速获取
 * @return [type] [description]
 */
function getChargingIdByName(){
	$list = C("OPTION.charging_mode");
	$new_list = array();
	foreach ($list as $k => $v) {
		$one["key"]     = $k;
		$one["val"]     = $v;
		$new_list[$v] = $one;
	}
	return $new_list;
}

/**
 * 快速获取
 * @return [type] [description]
 */
function getExtendStatusIdByName(){
	$list = C("OPTION.extend_status");
	$new_list = array();
	foreach ($list as $k => $v) {
		$one["key"]     = $k;
		$one["val"]     = $v;
		$new_list[$v] = $one;
	}
	return $new_list;
}

/**
 * 快速获取
 * @return [type] [description]
 */
function getNameByStatusTree(){
	$educate_list = C("OPTION.employee_status");
	$new_list = array();
	foreach ($educate_list as $k => $v) {
		$one["key"]     = $k;
		$one["val"]     = $v;
		$new_list[$v][] = $one;
	}
	return $new_list;
}
/**
 * 中文转码
 * @return [type] [description]
 */
function iconv_($val){
	$wz = iconv('gbk','utf-8',$val);
	return $wz;
}


/**
 * [获取日期相差信息]
 * @param  [type] $date1 [description]
 * @param  [type] $date2 [description]
 * @return [type]        [description]
 */
function getDatesDiff($date1,$date2){
	$datetime1 = new \DateTime($date1);
	$datetime2 = new \DateTime($date2);
	$interval  = $datetime1->diff($datetime2);
	$time['y']         = $interval->format('%Y');
	$time['m']         = $interval->format('%m');
	$time['d']         = $interval->format('%d');
	$time['h']         = $interval->format('%H');
	$time['i']         = $interval->format('%i');
	$time['s']         = $interval->format('%s');
	$time['a']         = $interval->format('%a');    // 两个时间相差总天数
	return $time;
}
/**
 * 获取一级部门id
 * @param  [type] $depart_name [description]
 * @param  [type] $depart_id   [description]
 * @param  [type] $depart_pid  [description]
 * @return [type]              [description]
 */
function getLeveDepartId($depart_name,$depart_id,$depart_pid){
	$leve_id = 0;
	if($depart_name=="总裁办" || $depart_name=="财务部" || $depart_name=="风控部" || $depart_name=="人力行政部" || $depart_name=="品牌公关部"||$depart_name=="事业发展部"){
		$leve_id = $depart_id;
	}else{
		$leve_id = $depart_pid;
	}
	return $leve_id;
}

/**
 * 获取一级部门id
 * @param  [type] $depart_name [description]
 * @param  [type] $depart_id   [description]
 * @param  [type] $depart_pid  [description]
 * @return [type]              [description]
 */
function getFirstLeveDepartName($fist_departName,$second_departName){
	$departList = array();
	if($second_departName=="总裁办" || $second_departName=="财务部" || $second_departName=="风控部" || $second_departName=="人力行政部" || $second_departName=="品牌公关部"||$second_departName=="事业发展部"){
		$departList["fist_departName"] = $second_departName;
		$departList["second_departName"] = "/";
	}else{
		$departList["fist_departName"] = $fist_departName;
		$departList["second_departName"] = $second_departName;
	}
	return $departList;
}


/**
 * 获取是否有权限
 * @param  [type]  $query_fact_url   [description]
 * @param  [type]  $session_save_url [description]
 * @return boolean                   [description]
 */
function isHasAuthToQuery($query_fact_url,$uid){
	$adSer       = new Service\AuthAccessService();
	$isHas_check = $adSer->currentIsHasAuth($query_fact_url,$uid);
	return $isHas_check;
}

/**
 * 根据身份证号获取年龄
 * @param  [type] $id [description]
 * @return [type]     [description]
 */
function getAgeByBodyNo($id){ 
	//过了这年的生日才算多了1周岁 
	if(empty($id)) return ''; 
	$date = strtotime(substr($id,6,8));
	//获得出生年月日的时间戳 
	$today = strtotime('today');
	//获得今日的时间戳 
	$diff = floor(($today-$date)/86400/365);
	//得到两个日期相差的大体年数 
	//strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比 
	$age = strtotime(substr($id,6,8).' +'.$diff.'years')>$today?($diff+1):$diff; 
	return $age; 
} 

/**
 * 获取某年某月的实际天数
 * @param  [type] $year_month [description]
 * @return [type]             [description]
 */
function getMonthDays_com($year_month){
	return date("t",strtotime($year_month));
}


/**
 * 四舍五入-保留小数
 * @param  [type]  $number [description]
 * @param  integer $last   [description]
 * @return [type]          [description]
 */
function parseFloat2($number,$last=2){
	$a = number_format($number,$last, '.', '');
	return $a;
}

/**
 * 获取版本号
 * @return [type] [description]
 */
function getVersion(){
	return substr(time(), 6,4);
}

/**
 * 返回业务线tree
 * @return [type] [description]
 */
function getLineIDTree(){
	$buSer                 = new Service\BusinessLineService();
	$lineList              = $buSer->getListByWhere("1=1","id,name");
	$tree = array();
	foreach ($lineList as $k => $v) {
		$tree[$v["id"]]["name"] = $v["name"];
	}
	return $tree;
}

/**
 * 返回业务线tree
 * @return [type] [description]
 */
function getLineNameTree(){
	$buSer                 = new Service\BusinessLineService();
	$lineList              = $buSer->getListByWhere("1=1","id,name");
	$tree = array();
	foreach ($lineList as $k => $v) {
		$tree[$v["name"]]["id"] = $v["id"];
	}
	return $tree;
}

/**
 * 判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
 * @return [type] [description]
 */
function getCurrentUserIsOnlyReadMyselfData($user_id="",$user_name=""){
	$isRead  = false;
	//id判断
	$userList_ids = array(
			0 =>634,//wangmin
			//1 =>717,
			2 =>790,//penghui
			3 =>793,//liaoyun
			4 =>809,//yangliu
			5 =>810,//shiyingying
			6 =>811,//wangyunmeng
			7 =>812,//xiayuanjun
		);
	if(in_array($user_id, $userList_ids)){
		$isRead = true;
	}
	//中文名判断
	$userList_name = array(
			0 =>"王敏",//wangmi
			//1 =>"陶艳",//taoya
			2 =>"彭辉",//penghui
			3 =>"廖赟",//liaoyun
			4 =>"杨柳",//yangliu
			5 =>"师迎莹",//shiyingying
			6 =>"王运萌",//wangyunmeng
			7 =>"夏苑君",//xiayuanjun
	);

	if(in_array($user_name, $userList_name)){
		$isRead = true;
	}

	return $isRead;
}

/**
 * [getSiteTitle description]
 * @return [type] [description]
 */
function getSiteTitle($title=""){
	$title_ = "智能办公平台-".$title;;
	if(empty($title)){
		$title_ = "智能办公平台";
	}
	return $title_;
}




?>
