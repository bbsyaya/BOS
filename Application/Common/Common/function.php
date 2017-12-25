<?php
include_once("common.php");
/**
 * md5加密
 * @param        $str
 * @param string $key
 * @return string
 */
function boss_md5($str, $key = 'b#o$s%s@v&3'){
	return '' === $str ? '' : md5(sha1($str) . $key);
}


/**
 * 记录行为日志
 * @param null   $model 模块
 * @param null   $level 级别
 * @param null   $user 用户
 * @param string $content 内容
 * @param string $kw 关键字
 * @return string
 */
function action_log($model = null, $level = SEASLOG_INFO, $user = null, $content='', $kw='') {
	//参数检查
	if(empty($model) || empty($content) || empty($user)) {
		return '参数不能为空';
	}
	if(empty($user)){
		$user = is_login();
	}
	$content = "[[{$user}]]--[[{$kw}]]--".$content;
	$content = preg_replace("/\s/","",$content);
	\Think\Log::actionWrite($content, $level, $model);

}


/**
 * 权限检测
 * @param string  $rule    检测的规则
 * @param string  $mode    check模式
 * @return boolean
 */
function checkRule($rule, $type=Home\Model\AuthRuleModel::RULE_URL, $mode='url') {
	static $Auth = null;
	if (!$Auth) {
		$Auth = new \Think\Auth();
	}
	return $Auth->check($rule,UID,$type,$mode);
}
function checkRule_xq($rule_id){
	$arr_rule=$_SESSION['userinfo']['fun_config'];

	return in_array($rule_id, $arr_rule);
}
function checkRuleForName_xq($rule_name){
	$data=M('auth_rule')->where("name='".$rule_name."'")->find();
	$arr_rule=$_SESSION['userinfo']['fun_config'];
	return in_array($data['id'], $arr_rule);
}
/**
 * 获取日志
 * @param        $module 模块
 * @param        $level 级别
 * @param string $date  日期 Ymd
 * @param        $key_word 关键字
 * @param        $start
 * @param int    $limit
 * @param        $order
 * @return string
 */
function actionlog_analyzer($module, $level, $date='*', $key_word=null, $start=1 , $limit=20, $order=SEASLOG_DETAIL_ORDER_ASC) {
	//参数检查
	if(empty($module) || empty($level) ){
		return '参数不能为空';
	}

	$ret['data']  = \Think\Log::actionDetail($module, $level, $date, $key_word, $start, $limit, $order);
	$ret['total'] = \Think\Log::actionCount($module,$level,$date,$key_word);
	empty($key_word) && $ret['total']--; //不传关键字数量的问题
	return $ret;
}


/**
 * 下载文件
 * @param      $file
 * @param null $callback
 * @param null $args
 * @return bool
 */
function downLocalFile($file, $callback = null, $args = null){
	if(is_file($file['rootpath'].$file['savepath'].$file['savename'])){
		/* 调用回调函数新增下载数 */
		is_callable($callback) && call_user_func($callback, $args);

		/* 执行下载 */ //TODO: 大文件断点续传
		header("Content-Description: File Transfer");
		header('Content-type: ' . $file['type']);
		header('Content-Length:' . $file['size']);
		if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
			header('Content-Disposition: attachment; filename="' . rawurlencode($file['name']) . '"');
		} else {
			header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
		}
		readfile($file['rootpath'].$file['savepath'].$file['savename']);
		exit;
	} else {
		$this->error = '文件已被删除！';
		return false;
	}
}


/**
 * 解析行为规则
 * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
 * 规则字段解释：table->要操作的数据表，不需要加表前缀；
 *              field->要操作的字段；
 *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
 *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
 *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
 *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
 * 单个行为后可加 ； 连接其他规则
 * @param string $action 行为id或者name
 * @param int $self 替换规则里的变量为执行用户的id
 * @return boolean|array: false解析出错 ， 成功返回规则数组
 * @author huajie <banhuajie@163.com>
 */
function parse_action($action = null, $self){
	if(empty($action)){
		return false;
	}

	//参数支持id或者name
	if(is_numeric($action)){
		$map = array('id'=>$action);
	}else{
		$map = array('name'=>$action);
	}

	//查询行为信息
	$info = M('Action')->where($map)->find();
	if(!$info || $info['status'] != 1){
		return false;
	}

	//解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
	$rules = $info['rule'];
	$rules = str_replace('{$self}', $self, $rules);
	$rules = explode(';', $rules);
	$return = array();
	foreach ($rules as $key=>&$rule){
		$rule = explode('|', $rule);
		foreach ($rule as $k=>$fields){
			$field = empty($fields) ? array() : explode(':', $fields);
			if(!empty($field)){
				$return[$key][$field[0]] = $field[1];
			}
		}
		//cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
		if(!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])){
			unset($return[$key]['cycle'],$return[$key]['max']);
		}
	}

	return $return;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 */
function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
	// 创建Tree
	$tree = array();
	if(is_array($list)) {
		// 创建基于主键的数组引用
		$refer = array();
		foreach ($list as $key => $data) {
			$refer[$data[$pk]] =& $list[$key];
		}
		foreach ($list as $key => $data) {
			// 判断是否存在parent
			$parentId =  $data[$pid];
			if ($root == $parentId) {
				$tree[] =& $list[$key];
			}else{
				if (isset($refer[$parentId])) {
					$parent =& $refer[$parentId];
					$parent[$child][] =& $list[$key];
				}
			}
		}
	}
	return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree  原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array  $list  过渡用的中间数组，
 * @return array        返回排过序的列表数组
 */
function tree_to_list($tree, $child = '_child', $order='id', &$list = array()) {
	if(is_array($tree)) {
		foreach ($tree as $key => $value) {
			$reffer = $value;
			if(isset($reffer[$child])){
				unset($reffer[$child]);
				tree_to_list($value[$child], $child, $order, $list);
			}
			$list[] = $reffer;
		}
		$list = list_sort_by($list, $order, $sortby='asc');
	}
	return $list;
}


function list_sort_by($list,$field, $sortby='asc') {
	if(is_array($list)){
		$refer = $resultSet = array();
		foreach ($list as $i => $data)
			$refer[$i] = &$data[$field];
		switch ($sortby) {
			case 'asc': // 正向排序
				asort($refer);
				break;
			case 'desc':// 逆向排序
				arsort($refer);
				break;
			case 'nat': // 自然排序
				natcasesort($refer);
				break;
		}
		foreach ( $refer as $key=> $val)
			$resultSet[] = &$list[$key];
		return $resultSet;
	}
	return false;
}


/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员
 */
function is_administrator($uid = null){
	$uid = is_null($uid) ? is_login() : $uid;
	return $uid && (intval($uid) === C('USER_ADMINISTRATOR'));
}


/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 */
function is_login(){
	$user = session('userinfo');
	if (empty($user)) {
		return 0;
	} else {
		return session('user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
	}
}


/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 */
function data_auth_sign($data) {
	//数据类型检测
	if(!is_array($data)){
		$data = (array)$data;
	}
	ksort($data); //排序
	$code = http_build_query($data); //url编码并生成query字符串
	$sign = sha1($code); //生成签名
	return $sign;
}


/**
 *  根据字符串返回bool值
 */
function get_bool($str) {
	if ($str == 'true' || $str == '1') {
		return true;
	} else if ($str == 'false' || $str == '0') {
		return false;
	} else {
		return false;
	}

}


function db_field_unique($model, $field, $where) {
	$res = $model->where($where)->field($field)->count();
	return $res > 0 ? false : true;
}


function P($param,$termimal=false,$detail=false){
	echo '<pre>';
	if ($detail) {
		var_dump($param);
	} else {
		print_r($param);
	}

	$termimal && exit();
}

function twonum($data,$str=''){//保留2位小数
	$data=number_format(round($data,2),2,'.',$str);
	return $data;
}

function bossPostData($url, $data=array(), $header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8"),$timeout = 1800){
		

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		$response = curl_exec($ch);
		if($error=curl_error($ch)){
			die($error);
		}
		curl_close($ch);

		useApiLog($url,json_encode($data),$response);
		return $response;
	}
function bossPostData_json($url, $data, $header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8"),$timeout = 180000 ){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "fileName=".$data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	$response = curl_exec($ch);
	if($error=curl_error($ch)){
		die($error);
	}
	curl_close($ch);
	useApiLog($url,json_encode($data),$response);
	return $response;
}

function bossGetData($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT,10000);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$output = curl_exec($ch);
	curl_close($ch);
	// useApiLog($url,'',$output);
	//记录api日志
	$data["url"]     = $url;
	$data["data"]    = "";
	$data["res"]     = $output;
	$data["addtime"] = date("Y-m-d H:i:s",time());
	$row = M("apilog")->add($data);
	unset($data);
	return $output;
}
function postDatatoorther($dataid,$status,$setid='',$st=''){//同步结算单状态
	if($st=='')$st=$status;
	$appid=$dataid[0]['lineid'];
	if(in_array($appid,array(1))){
		$url='http://dist.youxiaoad.com/api.php/Coststatus';
		$appsecret = "b#asb%svp&^";
		$data['ts'] = time();
		$data['sign'] = md5($appsecret.$data['ts']);
		$json_data[0]['id'] = $setid;
		$json_data[0]['status'] = $st;
		$data['data'] = json_encode($json_data);
		$result=bossPostData($url, $data);
		return json_decode($result);
	}
	if(I("showsql")=="showsql023"){
		print_r($appid);
	}
	if($appid==2){
		// $result=bossGetData('http://sspadmin.youxiaoad.com/api/bosapi.php?action=settlement&method=settlementStatusSave&setid='.$setid.'&status='.$st);

		//php curl host 设置访问指定主机
		$PostUrl = "http://60.205.150.74/api/bosapi.php?".'action=settlement&method=settlementStatusSave&setid='.$setid.'&status='.$st;
		$host = array("Host: sspadmin.youxiaoad.com");
		$ch = curl_init();
		$res= curl_setopt($ch, CURLOPT_URL,$PostUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		//curl_setopt($ch, CURLOPT_HEADER, 0);
		//curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$host);
		$output = curl_exec ($ch);
		curl_close($ch);

		//记录api日志
		$data["url"]     = $PostUrl;
		$data["data"]    = $PostData;
		$data["res"]     = $output;
		$data["addtime"] = date("Y-m-d H:i:s",time());
		$row = M("apilog")->add($data);
		unset($data);
		return json_decode($output);
	}
	
}
function postDatatoFenfa($data_a){//同步收入数据
	$url = 'http://dist.youxiaoad.com/api.php/Income';
	$appsecret = "b#asb%svp&^";
	$data['ts'] = time();
	$data['sign'] = md5($appsecret.$data['ts']);
	foreach ($data_a as $key => $value) {
		$data_b=array();
		$data_b['adddate']=$value['adddate'];
		$data_b['bos_id']=$value['id'];
		$data_b['cl_id']=$value['jfid'];
		$data_b['datanum']=$value['datanum'];
		$data_b['newdata']=$value['newdata'];
		$data_b['money']=$value['money'];
		$data_b['newmoney']=$value['newmoney'];
		$data_b['status']=$value['status'];
		$data_b['price']=$value['price'];
		$data_b['comid']=$value['comid'];
		$json_data[]=$data_b;
	}
	$data['data'] = json_encode($json_data);

	$result = bossPostData($url, $data);
	return json_decode($result);
}
function postStatustoFenfa($data_a){//同步收入状态
	$url = 'http://dist.youxiaoad.com/api.php/Incomestatus';
	$appsecret = "b#asb%svp&^";
	$data['ts'] = time();
	$data['sign'] = md5($appsecret.$data['ts']);
	foreach ($data_a as $key => $value) {
		$data_b=array();
		$data_b['bos_id']=$value['id'];
		$data_b['status']=$value['status'];
		$data_b['adddate']=$value['adddate'];
		$data_b['cl_id']=$value['jfid'];
		$json_data[]=$data_b;
	}
	$data['data'] = json_encode($json_data);
	$result = bossPostData($url, $data);

	return json_decode($result);
}
function checktbok($data_a){//检查同步是否成功
	$res=M('daydata')->where("id in ($data_a) && istbok=2")->find();
	if($res)echo '<script>alert("部分数据未向分发同步成功");</script>';
}
/**
 * 编码阶梯价格 array('11,22','33,44') => 33,44|33,44
 * @param $tPriceArr
 * @return string    =>  11,22|33,44
 */
function tieredprice_encode($tPriceArr) {
	$ret = '';
	if (is_array($tPriceArr) && !empty($tPriceArr)) {
		foreach ($tPriceArr as &$val) {
			if (is_array($val)) {
				$val = implode(',', $val);
			}
			if (!check_tieredprice($val)) {
				return false; //格式不对
			}
		}
		$ret = implode('|', $tPriceArr);
	}
	return $ret;
}


/**
 * 解码阶梯价格 33,44|33,44| => array('11,22','33,44')
 * @param $tPriceArr
 * @param $retMulti 是否返回多维数组 array(array('11','22),array('33','44'))
 * @return string
 */
function tieredprice_decode($tprice, $retMulti=false) {
	$ret = array();
	if (is_string($tprice) && !empty($tprice)) {
		$tpriceArr = explode('|', $tprice);
		foreach ($tpriceArr as $val) {
			if (!check_tieredprice($val)) {
				return false; //格式不对
			} else {
				if ($retMulti) {
					$ret[] = explode(',', $val);
				} else {
					$ret[] = $val;
				}
			}
		}
	}
	return $ret;
}
function tieredprice_decodetojson($tprice) {

	$ret = array();
	if (is_string($tprice) && !empty($tprice)) {
		$tpriceArr = explode('|', $tprice);
		foreach ($tpriceArr as $val) {
			$arr = explode(',', $val);
			$ret[]=array('num'=>$arr[0],'price'=>$arr[1]);
		}
	}
	echo json_encode($ret);
}
function tieredprice_decodetostring($tprice){

	$ret = array();
	if (is_string($tprice) && !empty($tprice)) {
		$tpriceArr = explode('|', $tprice);
		foreach ($tpriceArr as $val) {
			$arr = explode(',', $val);
			$ret[]=$arr[1];
		}
	}
	return implode(',',$ret);

}


//检查阶梯价格格式
function check_tieredprice($tprice) {
	return preg_match('/^(\d{1,9}|\d{1,9}\.\d{1,3}|\+)\,(\d{1,9}|\d{1,9}\.\d{1,3})$/', $tprice);
}

function check_tpstr($tprice) {
	if (is_string($tprice) && !empty($tprice)) {
		$tpriceArr = explode('|', $tprice);
		foreach ($tpriceArr as $val) {
			if (!check_tieredprice($val)) {
				return false; //格式不对
			}
		}
	}
	return true;
}


/**
 * /广告主评级
 * @param $strengthLevel 实力等级
 * @param $source_type 来源类型
 * @param $found_time 成立时间 时间戳
 * @param $capitalLevel 资本等级
 * @param $cooperation_time 合作开始时间
 */
function ad_rating($strengthLevel,$source_type,$found_time,$capitalLevel,$cooperation_time) {
	$score = 0;
	//实力等级
	$_subScre = 0;
	switch ($strengthLevel) {
		case 1:
			$_subScre = 50;
			break;
		case 2:
			$_subScre = 40;
			break;
		case 3:
			$_subScre = 30;
			break;
		case 4:
			$_subScre = 20;
			break;
		default:
			$_subScre = 0;
			break;
	}
	$score += $_subScre;

	//来源类型
	$_subScre = 0;
	switch ($source_type) {
		case 1:
			$_subScre = 15;
			break;
		case 2:
			$_subScre = 10;
			break;
		default:
			$_subScre = 0;
			break;
	}
	$score += $_subScre;

	//成立时间
	$_subScre = 0;
	$datetime1 = date_create(date('Y-m-d'));
	$datetime2 = empty($found_time) ? $datetime1 : date_create($found_time);

	$interval = date_diff($datetime1, $datetime2);
	$year = $interval->format('%y');
	if ($year > 10) {
		$_subScre = 10;
	} else if ($year >5 && $year <=10) {
		$_subScre = 8;
	} else {
		$_subScre = 5;
	}
	$score += $_subScre;

	//注册资本1 => '1000万以上',2 => '500-1000万',3 => '500万以下',
	$_subScre = 0;
	switch ($capitalLevel) {
		case 1:
			$_subScre = 5;
			break;
		case 2:
			$_subScre = 3;
			break;
		case 3:
			$_subScre = 2;
			break;
		default:
			$_subScre = 0;
			break;
	}
	$score += $_subScre;

	//合作时间
	$_subScre = 0;
	$datetime1 = date_create(date('Y-m-d'));
	$datetime2 = empty($cooperation_time) ? $datetime1 : date_create($cooperation_time);
	$interval = date_diff($datetime1, $datetime2);
	$year = $interval->format('%y');
	if ($year > 3) {
		$_subScre = 20;
	} else if ($year >=2 && $year <=3) {
		$_subScre = 10;
	}else if ($year >=1 && $year <=2) {
		$_subScre = 5;
	} else {
		$_subScre = 0;
	}
	$score += $_subScre;

	//计算广告主等级
	$level = 'D';
	if($score >= 90 && $score <= 100) {
		$level = 'S';
	} else if ($score >= 75 && $score < 90) {
		$level = 'A';
	} else if ($score >= 60 && $score < 75) {
		$level = 'B';
	} else if ($score >= 50 && $score < 60) {
		$level = 'C';
	} else if ($score >= 0 && $score < 50) {
		$level = 'D';
	}
	return $level;

}


function uplaodfile($name,$dir){
	if(!empty($_FILES[$name]['tmp_name'])){
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
	}else{
		return '没有上传数据';
	}
}



function defaultDate($date) {
	if (empty($date)) {
		return null;
	} else {
		return $date;
	}
}

function getorderurl($str){
	$get=I('get.');
	$get['order']=$str.'_desc';
	if(I('get.order')==$str.'_desc'){
		$get['order']=$str;
	}
	$url=U(ACTION_NAME,$get);
	return $url;
}

function getorderclass($str){
	if(I('get.order')==$str){
		return ' class="sorting_desc" ';
	}elseif(I('get.order')==$str.'_desc'){
		return ' class="sorting_asc" ';
	}else{
		return ' class="sorting" ';
	}
}

function actionlog(){
	if(!is_dir('./upload/log'))mkdir('./upload/log',0777,true);
	$datas['url']=CONTROLLER_NAME.'/'.ACTION_NAME;
	$datas['user_id']=$_SESSION['userinfo']['uid'];
	$datas['action_ip']=$_SERVER["HTTP_X_FORWARDED_FOR"];
	$datas['create_time']=date('Y-m-d H:i:s');
	$datas['post']=I('post.');
	$datas['get']=I('get.');
	//$datas['sql']=substr($GLOBALS['allsql'],0,-4);
	$data=json_encode($datas);
	file_put_contents('./upload/log/actionlog_'.date('Y-m-d').'.log', '@&@'.$data ,FILE_APPEND);
}

function logtomysql($iss=0){
	$data=file_get_contents('./upload/log/apilog.txt');
	file_put_contents('./upload/log/apilog.txt','');
	$datas=explode('@&@',$data);
	foreach ($datas as $k => $v) {
		if(trim($v)=='')continue;
		$thisdata=json_decode($v,true);
		M('apilog')->add($thisdata);
	}
}
function useApiLog($url,$data,$res){
	if(!is_dir('./upload/log'))mkdir('./upload/log',0777,true);
	$datas['url']=$url;
	$datas['data']=$data;
	$datas['res']=$res;
	$datas['addtime']=date('Y-m-d H:i:s');
	$data=json_encode($datas);
	file_put_contents('./upload/log/apilog.txt', '@&@'.$data ,FILE_APPEND);
	if(mt_rand(0,100)==22){
		logtomysql();
	}
}

//uploadify 上传插件
function uploadify($targetPath) {

	if (!empty($_FILES)) {
		$tempFile = $_FILES['Filedata']['tmp_name'];
		$targetFile = rtrim($targetPath,'/') . '/' . $_FILES['Filedata']['name'];

		// Validate the file type
		$fileTypes = array('jpg','jpeg','gif','png','rar','zip'); // File extensions
		$fileParts = pathinfo($_FILES['Filedata']['name']);

		if (in_array($fileParts['extension'], $fileTypes)) {
			move_uploaded_file($tempFile,$targetFile);
			return array('code'=>0, 'info'=>$targetFile);
		} else {
			return array('code'=>1, 'info'=>'类型错误');
		}
	}

}

function bossPostData_Flow($url, $data=array(), $header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8"),$timeout = 60){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);//最多重定向5次      
	$response = curl_exec($ch);
	if($error=curl_error($ch)){
		die($error);
	}
	curl_close($ch);
	useApiLog($url,json_encode($data),$response);
	return $response;

}

function bjalltj($data,$data1){
	//对比两个url参数是否相同
	$data=htmlspecialchars_decode($data);
	$data1=htmlspecialchars_decode($data1);
	$arr=strtoarray($data);
	$arr1=strtoarray($data1);
	if(I('get.xq')=='gasidegg'){
		var_dump($arr);
		var_dump($arr1);
		var_dump($arr==$arr1);
	}
	if($arr==$arr1)return true;
	else return false;
}
function strtoarray($data){
	$data=str_replace(array('%5B%5D','/index.php','.html','%2C','%28','%29'), array('[]','','',',','(',')'), $data);
	$c=explode('?', $data);
	if($c[1]!=''){
		$arr=explode('&', $c[1]);
		$alldata=array();
		foreach ($arr as $key => $value) {
			$a=explode('=', $value);
			if(substr($a[0],-2)=='[]'){
				$n=substr($a[0], 0,-2);
				$alldata[$n][$a[1]]=$a[1];
			}else{
				$alldata[$a[0]]=$a[1];
			}
		}
	}
	$arr=explode('/', $c[0]);
	for($i=1;$i<count($arr);$i+=2){
		$alldata[$arr[$i]]=$arr[$i+1];
	}
	unset($alldata['inandout']);
	unset($alldata['p']);
	return $alldata;
}

function getpagedata($allnum,$ajaxurl=false){
	$url='/'.MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME.'?';
	foreach (I('get.') as $key => $value) {
		if($key!='p'){
			$url.=$key.'='.$value.'&';
		}
	}
	$url.="p=";
	if(!$ajaxurl)return "<script>
		var page_allnum=$allnum;
	    var page_pagenum=10;
	    var page_nowpage=".I('get.p',1).";
	    var page_url='$url';
    </script>
    <div id='page_div'></div>";
    else return "<script>
		var page_allnum=$allnum;
	    var page_pagenum=10;
	    var page_nowpage=".I('get.p',1).";
	    var page_url='$url';
    </script>
    <div id='page_div' data='$ajaxurl'></div>";
}


function num2rmb($number = 0, $int_unit = '元', $is_round = TRUE, $is_extra_zero = FALSE) 
{ 
    // 将数字切分成两段 
    $parts = explode('.', $number, 2); 
    $int = isset($parts[0]) ? strval($parts[0]) : '0'; 
    $dec = isset($parts[1]) ? strval($parts[1]) : ''; 
 
    // 如果小数点后多于2位，不四舍五入就直接截，否则就处理 
    $dec_len = strlen($dec); 
    if (isset($parts[1]) && $dec_len > 2) 
    { 
        $dec = $is_round 
                ? substr(strrchr(strval(round(floatval("0.".$dec), 2)), '.'), 1) 
                : substr($parts[1], 0, 2); 
    } 
 
    // 当number为0.001时，小数点后的金额为0元 
    if(empty($int) && empty($dec)) 
    { 
        return '零'; 
    } 
 
    // 定义 
    $chs = array('0','壹','贰','叁','肆','伍','陆','柒','捌','玖'); 
    $uni = array('','拾','佰','仟'); 
    $dec_uni = array('角', '分'); 
    $exp = array('', '万'); 
    $res = ''; 
 
    // 整数部分从右向左找 
    for($i = strlen($int) - 1, $k = 0; $i >= 0; $k++) 
    { 
        $str = ''; 
        // 按照中文读写习惯，每4个字为一段进行转化，i一直在减 
        for($j = 0; $j < 4 && $i >= 0; $j++, $i--) 
        { 
            $u = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位 
            $str = $chs[$int{$i}] . $u . $str; 
        } 
        //echo $str."|".($k - 2)."<br>"; 
        $str = rtrim($str, '0');// 去掉末尾的0 
        $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0 
        if(!isset($exp[$k])) 
        { 
            $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位 
        } 
        $u2 = $str != '' ? $exp[$k] : ''; 
        $res = $str . $u2 . $res; 
    } 
 
    // 如果小数部分处理完之后是00，需要处理下 
    $dec = rtrim($dec, '0'); 
 
    // 小数部分从左向右找 
    if(!empty($dec)) 
    { 
        $res .= $int_unit; 
 
        // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求 
        if ($is_extra_zero) 
        { 
            if (substr($int, -1) === '0') 
            { 
                $res.= '零'; 
            } 
        } 
 
        for($i = 0, $cnt = strlen($dec); $i < $cnt; $i++) 
        { 
            $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位 
            $res .= $chs[$dec{$i}] . $u; 
        } 
        $res = rtrim($res, '0');// 去掉末尾的0 
        $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0 
    } 
    else 
    { 
        $res .= $int_unit . '整'; 
    } 
    return $res; 
} 