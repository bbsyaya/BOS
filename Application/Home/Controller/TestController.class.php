<?php
namespace Home\Controller;
use Think\Controller;
use Common\Service;
use Common\Model;
/**
* 
*/
class TestController extends Controller
{

	public function testNotify(){
		$notify = M('notify');
		$notifyData = $notify->field('ATTACHMENT_ID,NAME')
			->select();
		foreach($notifyData as $val){
			$attachment_id = explode('@',$val['attachment_id']);
			$id_string=explode('_',$attachment_id[1]);
			$a = $id_string[0];
			$b = rtrim($id_string[1], ",");
			$basedir = "./upload/notify/".$a."/";
			if ($dh = opendir($basedir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file != '.' && $file != '..'){
						if (!is_dir($basedir."/".$file)) {
							$id_s=explode('.',$file);
							if($id_s[0] == $b){
								$hz = substr($file, strrpos($file, '.')+1);
								//echo "./upload/notify/".$a."/".$file.'<br/>';
								//echo "./upload/notify/".$a."/".$b.".".$hz.'<br/>';
								if(rename("./upload/notify/".$a."/".$file,"./upload/notify/".$a."/".$b.".".$hz ) ){
									//echo "更名成功";
								}else{
									echo "更名失败";
								}
							}

						}
					}
				}
				closedir($dh);
			}

			//$notifyData['attachment_name'] = "./upload/notify/".$a."/".$b.".".$notifyData['name'];
		}
	}

	function testlog(){
		// $a 
		// $reIP=$_SERVER["REMOTE_ADDR"]; 
		// echo $reIP; 
		// $url="/Public/static/404.html";
		// $time = 0;
		// $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
  //       if ($time != 0)
  //           $str .= $msg;
  //       exit($str);
		// // $a=1
		// print_r("test");
		// $list = array();
		// for ($i=0; $i < 10; $i++) { 
		// 	$list[] = $i."导入成功\r\n";
		// }
		// $list = implode(",", $list);
		// // $officeCateSer = new Service\OfficeCateService();
		// // $url = $officeCateSer->writeLogs($list,"/officecatetest_".time().".txt");
		// // $url = $this->writeLogs($list,"/officecatetest_".time().".html","html");
		// // $logurl = ltrim($url,".");
		// $this->ajaxReturn(array("url"=>$list));
	}

	function showLog(){
		$list = explode(",", $_REQUEST["datalog"]);
		foreach ($list as $k => $v) {
			echo $v."<br/>";
		}
	}

	function writeLogs($data,$file_name="",$type=""){
		$contents = "";
		$lastBr = $type=="html"?"<br/>":"";
		foreach ($data as $k => $v) {
			$contents .=$v."<br/>";
		}
		$file_url = "./upload/log";
		switch ($type) {
			case 'html':
				$html = '<!doctype html>
				<html lang="en">
				<head>
				<meta charset="UTF-8">
				<title>Document</title>
				</head>
				<body>'.$contents.'</body>
				</html>';
				break;
			
			default:
				$html = $contents;
				break;
		}
		if(!is_dir($file_url))mkdir($file_url,0777,true);
		file_put_contents($file_url.$file_name,$html);
		return $url = $file_url.$file_name;
	}


	function index(){
		$token = trim(I("token"));
		if($token==="frompost"){
			$msg = $this->testapi();
			$this->assign("msg",$msg);
		}
		$this->display();
	}

	function test1(){
		$this->finLogSer = !$this->finLogSer ? new Service\FinanceLogService() : $this->finLogSer;
		$token      = $this->token();
		$url        = C("AUDIT_API_HTTP").C("AUDIT_API_URL.queryPamentData_Url").$token;

		$endTime = date("Y-m-d",time());
		$nowTime = date("Y-m-d",strtotime("-1 month"));//默认一个月前

		//Boss数据归属时间
		$map['boss_sdate'] = !empty($_REQUEST["boss_sdate"])?trim($_REQUEST["boss_sdate"]):$nowTime;
		$map['boss_edate'] = !empty($_REQUEST["boss_edate"])?trim($_REQUEST["boss_edate"]):$endTime;
		//用友记账时间
		$map['yy_sdate']   = !empty($_REQUEST["yy_sdate"])?trim($_REQUEST["yy_sdate"]):$nowTime;
		$map['yy_edate']   = !empty($_REQUEST["yy_edate"])?trim($_REQUEST["yy_edate"]):$endTime;
		//流水到款时间
		$map['ls_sdate']   = !empty($_REQUEST["ls_sdate"])?trim($_REQUEST["ls_sdate"]):$nowTime;
		$map['ls_edate']   = !empty($_REQUEST["ls_edate"])?trim($_REQUEST["ls_edate"]):$endTime;
		$this->assign("map",$map);
		
		//时间整合
		$last_startTime = " 00:00:00";
		$last_endTime   = " 23:59:59";
		$map["boss_sdate"] .=$last_startTime;
		$map["boss_edate"] .=$last_endTime;
		$map["yy_sdate"]   .=$last_startTime;
		$map["yy_edate"]   .=$last_endTime;
		$map['ls_sdate']   .=$last_startTime;
		$map['ls_edate']   .=$last_endTime;

		//求3个时间段的交集
		$crossTime_1 = get_time_cross(strtotime($map["boss_sdate"]),strtotime($map["boss_edate"]),strtotime($map["yy_sdate"]),strtotime($map["yy_edate"]));
		$cross = array();
		if($crossTime_1){
			$cross["c_start"] = $crossTime_1["start"];
			$cross["c_end"]   = $crossTime_1["end"];

			// $crossTime_2 = get_time_cross(strtotime($cross["c_start"]),strtotime($cross["c_end"]),strtotime($map["ls_sdate"]),strtotime($map["ls_edate"]));
			// if($crossTime_2){
			// 	$cross["c_start"] = $crossTime_2["start"];
			// 	$cross["c_end"]   = $crossTime_2["end"];
			// }else{
			// 	$this->assign("error","Boss数据归属时间、用友记账时间、流水到款时间3个时间段无数据");
			// 	$this->display();
			// 	exit;
			// }
		}else{
			// $this->assign("error","Boss数据归属时间、用友记账时间、流水到款时间3个时间段无数据");
			$this->assign("error","Boss数据归属时间、用友记账时间2个时间段无数据");
			$this->display();
			exit;
		}
		$showall     = I("showall");//显示全部
		$list        = array("startDate"=>$cross["c_start"],"endDate"=>$cross["c_end"]);
		$postData    = json_encode($list);
		$postArray   = array("params"=>$postData);

		$html     = $this->buildRequestForm($postArray,"post",$url);
		$this->assign("html",$html);
		$this->display();
		// $responsData = bossPostData($url,$postArray);
		// $moneyList = array(
		// 		"yinshou_money_total" =>0,
		// 		"pz_money_total"      =>0
		// 	);//总金额统计
		// $list = json_decode($responsData,true);

		// print_r($list);exit;
	}

	/**
	 * 测试api
	 * @return [type] [description]
	 */
	function testapi(){
		$riskSer = new \Home\Service\RiskCheckService();
		//验证
		$params["appid"] = "20170613bosstoyongyou";
		$params["ts"]    = time();
		$appSecret       = "20170613bosstofinance";
		$params["sign"]  = md5($appSecret.$params["ts"]);
		
		//参数
		$map['boss_sdate'] = trim(I("boss_sdate"));
		$map['boss_edate'] = trim(I("boss_edate"));
		//替换+
		$map['boss_sdate'] = str_replace("+", " ", $map['boss_sdate']);
		$map['boss_edate'] = str_replace("+", " ", $map['boss_edate']);


		if(!$map['boss_sdate'] && !$map['boss_edate']){
			//当前时间前一个月
			$map['boss_sdate'] = $map['boss_edate'] = date("Y-m",time());
		}
		if($map['boss_sdate'] && !$map['boss_edate']){
			$map['boss_edate'] = $map['boss_sdate'];
		}
		if(!$map['boss_sdate'] && $map['boss_edate']){
			//结束时间前一个月
			$map['boss_sdate'] = $map['boss_edate'];
		}
		if($map['boss_sdate'] && $map['boss_edate']){
			$map['boss_sdate'] = date("Y-m",strtotime($map['boss_sdate']));
			$map['boss_edate'] = date("Y-m",strtotime($map['boss_edate']));
		}
		$this->assign("map",$map);
		$map["boss_sdate"] .= "-01";
		$month_fact_days = $riskSer->getMonthDays($map['boss_edate']);
		$map["boss_edate"] .= "-".$month_fact_days;

		
		$url             = I("url");
		$this->assign("url",$url);
		$date_type       = I("date_type");
		$this->assign("date_type",$date_type);

		$params["where"] = $date_type.">='".$map["boss_sdate"]."' and ".$date_type."<='".$map["boss_edate"]."'";
		$url_prev        = "http://bos3api.yandui.com:188".$url;
		$responsData     = bossPostData($url_prev,$params);

		$data["postUrl"]  = $url;
		$data["postData"] = json_encode($params,JSON_UNESCAPED_UNICODE);
		$data["data"]     = $responsData;
		$data["type"]     = 1;
		$finLogSer = new Service\FinanceLogService();
		$finLogSer->writeLog($data);


		// print_r($responsData);
		// print_r(1);
		$yy_list     = json_decode($responsData,true);
		// return "共查询到".$yy_list["data"][0]["num"]."条数据";
		print_r($yy_list);



		// $url = "http://www.boss127.com/Api/SynApi/querySalerUsers.html";
		// $params_list = array("startDate"=>$map["boss_sdate"],"endDate"=>$map["boss_edate"]);
		// $postData    = json_encode($params);
		// // $postArray   = array("params"=>$postData);
		// // print_r($postData);
		// http_build_query($postData);
		// exit;
		// $responsData = bossPostData($url,$postArray);

		// // $responsData = bossPostData($url,$params);
		// $yy_list     = json_decode($responsData,true);
		// print_r($yy_list);
		// $data["pro_id"] = 3264;
		// $data["adp_name"] = "hyios";
		// $data["sup_id"] = 2161;
		// $data["bl_id"] = 46;
		// $data["sb_id"] = 3;
		// $data["promotion_stime"] = "2016-10-28";

		// //提交
		// $post_url = "";
		// $action_method = "/ChargingLogo/createNotDist.html";
		// switch ($type) {
		// 	case '1':
		// 		$post_url = "http://www.boss127.com/Api".$action_method;
		// 		break;
			
		// 	case '2':
		// 		$post_url = "http://devboss3.yandui.com/Api".$action_method;
		// 		break;

		// 	case '3':
		// 		$post_url = "http://bos3.yandui.com/Api".$action_method;
		// 		break;
		// }

		// $html     = $this->buildRequestForm($params,"post",$url);
		// $this->assign("html",$html);
		// $this->display();


		//curl  post

		// $this->testapic($post_url,$data);
	}

    private function iconv2utf8($Result) {        
        // $Row  = array();                   
        // $key1 = array_keys($Result);  //取查询结果$Result的数组的键值          
        // //print_r($key1);          
        // $key2 = array_keys($Result[$key1[0]]);   
        // //取查询结果$Result的第一个数组（$key1[0]）的键值           
        // //print_r($key2);                  
        // for($i = 0;$i < count($key1);$i++) {  
        //     for($j = 0;$j < count($key2);$j++) {                        
        //         //取查询结果编码改为UTF－8，并存入$Row，且$Row与$Result键与值一致                      
        //         $Row[$key1[$i]][$key2[$j]] = iconv('gb2312','utf-8',$Result[$key1[$i]][$key2[$j]]); 
        //     }         
        // }       
        print_r($Result);exit;
        return eval('return '.iconv('gb2312','utf-8',var_export($Result,true).';')); 
        
    }


	public function testapic($post_url,$data){
		// print_r(http_build_query($data));exit;
		// $header[] = "Content-type: text/xml";
		// echo bossPostData($url,$data,$header);


		$list = array("startDate"=>"2017-01-01 00:00:00","endDate"=>"2017-01-02 00:00:00");
		$postData = json_encode($list);
		$postArray = array("params"=>$postData);
		echo bossPostData($post_url,$data);
	}

	/**
	 * 表单组装
	 * @param  [type] $para_temp [description]
	 * @param  [type] $method    [description]
	 * @param  [type] $post_url  [description]
	 * @return [type]            [description]
	 */
	function buildRequestForm($para_temp, $method,$post_url) {
		//待请求参数数组
		$para = ksort($para_temp);
		// print_r($para_temp);exit;
		$sHtml = "<form id='dataform' name='dataform' action='".$post_url."' method='post'>";
		while (list ($key, $val) = each ($para_temp)) {
			$sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
		}

		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml."<input type='hidden' value='正在提交数据...'></form>正在提交数据...";
		$sHtml = $sHtml."<script>document.forms['dataform'].submit();</script>";
		return $sHtml;
	}

	/**
	 * 生成发票数据
	 * @return [type] [description]
	 */
	function makeInvokeData(){
		$page = trim($_REQUEST["p"]);
		$where["is_export"] = 1;
		$list = M("settlement_in")->field("invoiceinfo,id,expresscode,is_export")->where($where)->limit(0,1000)->select();
		$test = '[{"money":"558.2500","code":"test454545"}]';
		// print_r(json_decode($test,true));
		if(!$list){
			echo "data is null";exit;
		}
		foreach ($list as $k => $v) {
			if($v["invoiceinfo"]){
				$jsList = json_decode($v["invoiceinfo"],true);
				//添加
				foreach ($jsList as $kj => $vj) {
					$datas                 = array();
					$datas["money"]        = $vj["money"];
					$datas["invoice_no"]   = $vj["code"];
					$datas["income_st_id"] = $v["id"];
					$datas["track_no"]     = $v["expresscode"];
					$row                   = M("invoice")->add($datas);
				}
				//修改导出
				$row = M("settlement_in")->where(array("id"=>$v["id"]))->save(array("is_export"=>0));
			}	
		}
		echo "over";
	}

	/**
	 * 同步用户数据
	 * @return [type] [description]
	 */
	function synUserToHr(){
		$token = I("token");
		if($token){
			$hrSer     = new Service\HrManageService();
			$list = M("user")->select();
			echo "list_count:".count($list)."<br>";
			$index_=0;
			$has_count = 0;
			foreach ($list as $k => $v) {
				//检查员工表中是否已有
				$hrOne = $hrSer->getHRListCountByWhere(array("user_id"=>$v["id"]));
				if($hrOne>0){
					echo "员工:".$v["id"]."在oa_hr_manager表中已存在<br>";
					$has_count++;
					continue;
				}
				$data_["company_id"]  = 160;
				$data_["user_name"]  = $v["real_name"];
				$data_["depart_id"]  = 155;
				$data_["sex"]        = $v["sex"];
				$data_["phone"]      = $v["mobile"];
				$data_["qq"]         = $v["qq"];
				$data_["nation"]     = $v["ethnic_group"];
				$data_["profession"] = $v["major"];
				$data_["adress"]     = $v["address"];
				$data_["body_no"]    = time().$v["id"];//默认值，避免出错
				$data_["job_no"]     = $v['employee_number'];
				$data_["user_id"]    = $v["id"];
				$data_["status"]     = $v["status"]==0?1:3;
				$data_["dateline"]   = date("Y-m-d H:i:s",time());
				$data_["duty"]       = 1;
				$row                 = $hrSer->addHRData($data_);
				if($row){
					$index_++;
				}
			}
			echo "add_count:".$index_."<br>";
			echo "has_count:".$has_count."<br>";
		}
		echo "over";
		
	}

	/**
	 * 同步用户部门
	 * @return [type] [description]
	 */
	function synUserDepart(){
		$token = I("token");
		if($token){
			$hrSer   = new Service\HrManageService();
			$userSer = new Service\UserService();
			$depSer = new Service\DepartSettingService();
			$list    = $hrSer->getHRListByWhere("1=1","user_id,id");
			$couont = 0;
			foreach ($list as $k => $v) {
				$userOne = $userSer->getOneByWhere(array("id"=>$v["user_id"]),"dept_id");
				$depInfo = $depSer->getOneByWhere(array("id"=>$userOne["dept_id"]),"name");
				$one["depart_id"] = $userOne["dept_id"];
				$one["depart_name"] = $depInfo["name"];
				$row = $hrSer->saveHRData(array("id"=>$v["id"]),$one);
				if($row){
					echo "succes_count:".$couont."<br>";
					$couont++;
				}
			}
		}
		echo "over";
	}

	/**
	 * 修复部门数据
	 * @return [type] [description]
	 */
	function synDepart(){
		$this->display();
	}

	/**
	 * 同步部门
	 * @return [type] [description]
	 */
	function importDepart(){
		import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Reader.Excel5");
		import("Org.Util.PHPExcel.Reader.Excel2007");
		$upload = new \Think\Upload();// 实例化上传类
		//检查客户端上传文件参数设置
		$result = array("msg"=>"上传失败，请联系超级管理员","code"=>500,"data"=>"");
		$upload->rootPath = "./upload/excel/";//保存根路径
		$upload->savePath = "";//保存根路径
		$upload->saveRule = 'uniqid';//是否自动命名
		if (! file_exists ( $upload->savePath )) {
			mkdir ( $upload->savePath );
		}
		$upload->uploadReplace = true;
		$info = $upload->upload ();
		if (! $info) {
			$result["msg"] = $upload->getError ();
			$this->ajaxReturn ($result);
		}else{
			$file = $info["files"];
			$savename ['savename'] = $filePath = $upload->rootPath .$file["savepath"]. $file ['savename'];
			$savename ['name'] = $file ['name'];
			//读取excel数据
			$PHPReader = new \PHPExcel_Reader_Excel2007();
			if(!$PHPReader->canRead($filePath)){
				$PHPReader = new \PHPExcel_Reader_Excel5();
				if(!$PHPReader->canRead($filePath)){
					$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
					return $result;
				}
			}
			
			$PHPExcel     = $PHPReader->load($filePath);
			$currentSheet = $PHPExcel->getSheet(0);
			$allColumn    = $currentSheet->getHighestColumn();
			$allRow       = $currentSheet->getHighestRow();
			// print_r($allRow);exit;
			$excelData = array();
			//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			//从哪列开始，A表示第一列
				for ($currentColumn = 'A'; $currentColumn <= "I"; $currentColumn++) {
					//数据坐标
					$address = $currentColumn.$currentRow;
					//读取到的数据，保存到数组$arr中
					$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();

				}
			}
			if(count($excelData)==0){
				unlink ($filePath);
				$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
				return $result;
			}

			//转换为数组
			
			$result = $this->doExcelToArrayDo($excelData);
			//生成导入数据日志
			$logdata = implode(",", $result["data"]);
			if($result["status"]){
				unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logdata"=>$logdata);
				return $result;
			}else{
				$objReader=\PHPExcel_IOFactory::createReader("Excel5");
				$objExcel=$objReader->load($filePath);
				foreach($result["error"] as $v){
					$objExcel->getActiveSheet()->setCellValue('D'.$v[0], $v[1]);
				}
				foreach($result["success"] as $v){
					$objExcel->getActiveSheet()->setCellValue('D'.$v[0], '导入成功！');
				}
				$objWriter=\PHPExcel_IOFactory::createWriter($objExcel,"Excel5");
				$objWriter->save($filePath);
				$result = array("msg"=>"导入失败","code"=>500,"data"=>$savename."(".$filePath.")","logdata"=>$logdata);
				return $result;
			}
		}
	}


	function doExcelToArrayDo($data,$type=""){
		$returnArray = array(
						"status" =>"false",
						"data"   =>array()
        	);
        if(is_array($data)&&count($data)>0){
			$badArray   = array();//没查库就错误的数组
			$rightArray = array();//最后成功的数组
			$toDbArray  = array();//要去查库的数组
            foreach($data as $k=>$v){
                $isDoDb = true;
                if($isDoDb){//操作开关为真的时候，插入可以查库的数组
                    $toDbArray[$k] = $v;
                }
                else{//操作开关为假的时候，这行执行完啦，整理错误
                    $badArray[] = array($k,$msgStr);
                }
            }
            $doExcelDbResult = array();
            switch ($type) {
            	case 'importProdanwei':
            		$doExcelDbResult        = $this->synDanweiData($toDbArray);
            		break;
            	case 'updateYginfo':
            		$doExcelDbResult        = $this->synUpdateYginfo($toDbArray);
            		break;
        		case 'importxueli':
            		$doExcelDbResult        = $this->synUpdateimportxueli($toDbArray);
            		break;
            	default:
            		$doExcelDbResult        = $this->synData($toDbArray);
            		break;
            }
          

			$returnArray["status"] = $doExcelDbResult["status"];
			$returnArray["data"]    = $doExcelDbResult["data"];
        }else{
            $returnArray["status"] = false;
        }
        return $returnArray;
	}


	function synData($data){
		// print_r($data);exit;
		$rel  = array(
			"status" =>true,
			"data"   =>array(),
		);
		if(!$data){ return false;}
		foreach ($data as $k => $v ){
			$depart_1   = trim($v["A"]);
			$depart_2   = trim($v["B"]);
			$username   = trim($v["C"]);
			//获取实际所在部门id
			$depart_id = 0;
			$depart_name = "";
			if(!empty($depart_1) && empty($depart_2)){
				//用第一个
				$depart = M("user_department")->field("id")->where(array("name"=>$depart_1))->find();
				$depart_id = $depart["id"];
				$depart_name = $depart_1;
			}

			if(!empty($depart_1) && !empty($depart_2)){
				//用第1个pid+第2个name
				$depart_one = M("user_department")->field("id")->where(array("name"=>$depart_1))->find();
				
				$depart_sec = M("user_department")->field("id")->where(array("name"=>$depart_2,"pid"=>$depart_one["id"]))->find();
				$depart_id = $depart_sec["id"];
				$depart_name = $depart_2;
			}



			//将部门同步到user,hr_manage表
			$user = M("user")->where(array("real_name"=>$username))->find();
			if($user){
				//修改部门id
				M("user")->where(array("id"=>$user["id"]))->save(array("dept_id"=>$depart_id));

			}
			$hr = M("oa_hr_manage")->where(array("user_name"=>$username))->find();
			if($hr){
				// $compay = M("user_department")->field("id")->where(array("name"=>$comanyName))->find();
				// $data_["company_id"] = $compay["id"];
				$data_["depart_id"] = $depart_id;
				$data_["depart_name"] = $depart_name;
				M("oa_hr_manage")->where(array("id"=>$hr["id"]))->save($data_);
			}
		}
		return $rel;
	}



	function synapp(){
		$token = I("token");
		if($token){
			$list = M("oa_office_apply")->select();
			if($list){
				foreach ($list as $k => $v) {
					$user = M("user")->where(array("id"=>$v["uid"]))->find();
					if($user){
						$dept_id = $user["dept_id"];
						$sa["depart_id"] =  $dept_id;
						$depart_one = M("user_department")->field("id,name")->where(array("id"=>$dept_id))->find();
						$sa["depart_name"] =  $depart_one["name"];

						$row = M("oa_office_apply")->where(array("id"=>$v["id"]))->save($sa);
						echo "row<br/>";
					}
				}
			}
		}
		echo "over";
	}

	/**
	 * 同步单位
	 * @return [type] [description]
	 */
	function importProdanwei(){
		import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Reader.Excel5");
		import("Org.Util.PHPExcel.Reader.Excel2007");
		$upload = new \Think\Upload();// 实例化上传类
		//检查客户端上传文件参数设置
		$result = array("msg"=>"上传失败，请联系超级管理员","code"=>500,"data"=>"");
		$upload->rootPath = "./upload/excel/";//保存根路径
		$upload->savePath = "";//保存根路径
		$upload->saveRule = 'uniqid';//是否自动命名
		if (! file_exists ( $upload->savePath )) {
			mkdir ( $upload->savePath );
		}
		$upload->uploadReplace = true;
		$info = $upload->upload ();
		if (! $info) {
			$result["msg"] = $upload->getError ();
			$this->ajaxReturn ($result);
		}else{
			$file = $info["files"];
			$savename ['savename'] = $filePath = $upload->rootPath .$file["savepath"]. $file ['savename'];
			$savename ['name'] = $file ['name'];
			//读取excel数据
			$PHPReader = new \PHPExcel_Reader_Excel2007();
			if(!$PHPReader->canRead($filePath)){
				$PHPReader = new \PHPExcel_Reader_Excel5();
				if(!$PHPReader->canRead($filePath)){
					$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
					return $result;
				}
			}
			
			$PHPExcel     = $PHPReader->load($filePath);
			$currentSheet = $PHPExcel->getSheet(0);
			$allColumn    = $currentSheet->getHighestColumn();
			$allRow       = $currentSheet->getHighestRow();
			// print_r($allRow);exit;
			$excelData = array();
			//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			//从哪列开始，A表示第一列
				for ($currentColumn = 'A'; $currentColumn <= "D"; $currentColumn++) {
					//数据坐标
					$address = $currentColumn.$currentRow;
					//读取到的数据，保存到数组$arr中
					$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();

				}
			}
			if(count($excelData)==0){
				unlink ($filePath);
				$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
				return $result;
			}

			//转换为数组
			
			$result = $this->doExcelToArrayDo($excelData,"importProdanwei");
			//生成导入数据日志
			$logdata = implode(",", $result["data"]);
			if($result["status"]){
				unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logdata"=>$logdata);
				return $result;
			}else{
				$objReader=\PHPExcel_IOFactory::createReader("Excel5");
				$objExcel=$objReader->load($filePath);
				foreach($result["error"] as $v){
					$objExcel->getActiveSheet()->setCellValue('D'.$v[0], $v[1]);
				}
				foreach($result["success"] as $v){
					$objExcel->getActiveSheet()->setCellValue('D'.$v[0], '导入成功！');
				}
				$objWriter=\PHPExcel_IOFactory::createWriter($objExcel,"Excel5");
				$objWriter->save($filePath);
				$result = array("msg"=>"导入失败","code"=>500,"data"=>$savename."(".$filePath.")","logdata"=>$logdata);
				return $result;
			}
		}
	}


	function synDanweiData($data){
		$rel  = array(
			"status" =>true,
			"data"   =>array(),
		);
		if(!$data){ return false;}
		foreach ($data as $k => $v) {
			$name   = trim($v["A"]);
			$format = trim($v["B"]);
			$unit   = trim($v["C"]);

			$one = M("oa_office_product")->where(array("name"=>$name,"format"=>$format))->find();
			if($one){
				$row = M("oa_office_product")->where(array("id"=>$one["id"]))->save(array("unit"=>$unit));
				if($row){
					$rel["data"][] = $one["id"]."--".$name."--".$format."修改成功";
				}else{
					$rel["data"][] = $one["id"]."--".$name."--".$format."修改失败";
				}
			}
		}
		return $rel;
	}

	/**
	 * 根据身份证id修改hrmanager信息
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function synUpdateYginfo($data){
		// print_r($data);exit;
		$rel  = array(
			"status" =>true,
			"data"   =>array(),
		);
		if(!$data){ return false;}
		foreach ($data as $k => $v) {

			//-----------------------------------------更新总工资
			$body_no = trim($v["F"]);
			$hr = M("oa_hr_manage")->where(array("body_no"=>$body_no))->field("id,per_radio,user_name")->find();
			if($hr){
				//将excel计算的和系统里面的进行对比
				// $total_money  =trim($v["E"]);
				$excel_user_name = trim($v["A"]);
				if($hr["user_name"]==$excel_user_name){
					$data = array();
					$data["basic_pay"]   = trim($v["B"]);//基本工资
					$data["job_salary"]  = trim($v["C"]);//职务工资
					$data["per_pay"]     = trim($v["D"]);//绩效工资
					$data["turn_salary"] = trim($v["G"]);//工资总额

					$row = M("oa_hr_manage")->where(array("id"=>$hr["id"]))->save($data);
					$sql = M("oa_hr_manage")->getLastsql();
					if($row){
						$rel["data"][] = "身份证号1：--".$body_no."更新成功".$sql;
					}else{
					 	$rel["data"][] = "身份证号1：--".$body_no."更新失败".$sql; 
					}
					unset($data);

				}else{
					$rel["data"][] = "身份证号1：--".$body_no."用户名不匹配,检查";
				}
				

			}else{
				$rel["data"][] = "身份证号1：--".$body_no."没找到";
			}

			//----------------------end------------------更新总工资











			//------------------------更新工资
			// $body_no = trim($v["C"]);
			// $hr = M("oa_hr_manage")->where(array("body_no"=>$body_no))->field("id")->find();
			// if($hr){
			// 	$data = array();
			// 	$data["entry_salary"] = trim($v["E"]);//入职薪资
			// 	$data["turn_salary"]  = trim($v["F"]);//工资总额
			// 	$data["basic_pay"]    = trim($v["G"]);//基本工资
			// 	$data["job_salary"]   = trim($v["H"]);//职务工资
			// 	$data["per_pay"]      = trim($v["I"]);//绩效工资

			// 	$row = M("oa_hr_manage")->where(array("id"=>$hr["id"]))->save($data);
			// 	// $sql = M("oa_hr_manage")->getLastsql();
			// 	$sql="";
			// 	unset($data);

			// 	if($row){
			// 		$rel["data"][] = "身份证号：--".$body_no."更新成功".$sql;
			// 	}else{
			// 	 	$rel["data"][] = "身份证号：--".$body_no."更新失败".$sql; 
			// 	}
			// }else{
			// 	$rel["data"][] = "身份证号：--".$body_no."没找到";
			// }
			//---------------------end---更新工资



			// $body_no = trim($v["C"]);
			// $hr = M("oa_hr_manage")->where(array("body_no"=>$body_no))->field("id")->find();

			// $sql = "";
			// if($hr){
			// 	$data = array();
			// 	$organ_frame_id = trim($v["A"]);
			// 	$de = M("user_department")->field("id")->where(array("name"=>$organ_frame_id))->find();
			// 	if($de){
			// 		$data["organ_frame_id"] = $de["id"];
			// 	}

			// 	$cengji = trim($v["B"]);
			// 	$cengji_id = 1;
			// 	if($cengji=="中级管理层"){
			// 		$cengji_id = 2;
			// 	}
			// 	if($cengji=="高级管理层"){
			// 		$cengji_id = 3;
			// 	}
			// 	if($cengji=="战略决策层"){
			// 		$cengji_id = 4;
			// 	}
			// 	$data["level"] = $cengji_id;
			// 	$data["passby"] = trim($v["D"]);
			// 	$data["id_card_address"] = trim($v["E"]);

			// 	$xueli = trim($v["F"]);
			// 	$xueli_id=2;
			// 	if($xueli=="本科"){
			// 		$xueli_id=5;
			// 	}
			// 	if($xueli=="初中"){
			// 		$xueli_id=2;
			// 	}
			// 	if($xueli=="大专"){
			// 		$xueli_id=4;
			// 	}
			// 	if($xueli=="高中"){
			// 		$xueli_id=3;
			// 	}
			// 	if($xueli=="中专"){
			// 		$xueli_id=10;
			// 	}

			// 	$data["educate"] = $xueli_id;

			// 	$ruzhi_date = trim($v["G"]);

			// 	$time = ($ruzhi_date-25569)*24*60*60;
			// 	$data["entry_time"] = date('Y-m-d H:i:s', $time);

			// 	if($entry_time){
			// 		$diff_days  = getDatesDiff($ruzhi_date,date("Y-m-d",time()));
			// 		$diff_year  = 0;
			// 		$diff_month = 0;
			// 		if(!empty($diff_days["y"]) && $diff_days["y"]!="00"){
			// 			$diff_year = preg_replace('/^0*/', '', $diff_days["y"]);
			// 		}
			// 		if(!empty($diff_days["m"]) && $diff_days["m"]!="0"){
			// 			$diff_month = $diff_days["m"];
			// 		}
			// 		$data["company_age"] = $diff_year.".".$diff_month;
			// 	}

			// 	// $jixiao_kaohe = str_replace("%", "", trim($v["H"]));
			// 	// $jixiao_kaohe = $jixiao_kaohe/100;
			// 	$data["per_radio"] =trim($v["H"]);

			// 	$ruzhi_xinzhi = trim($v["I"]);
			// 	$data["entry_salary"] = $ruzhi_xinzhi;


			// 	//绩效 2015-Q1-95;
			// 	$per = array();
			// 	$per[0] = "2014-Q4-".$v["J"];
			// 	$per[1] = "2015-Q1-".$v["K"];
			// 	$per[2] = "2015-Q2-".$v["L"];
			// 	$per[3] = "2015-Q3-".$v["M"];
			// 	$per[4] = "2015-Q4-".$v["N"];
			// 	$per[5] = "2016-Q1-".$v["O"];
			// 	$per[6] = "2016-Q2-".$v["P"];
			// 	$per[7] = "2016-Q3-".$v["Q"];
			// 	$per[8] = "2016-Q4-".$v["R"];
			// 	$per[9] = "2017-Q1-".$v["S"];
			// 	$per[10] = "2017-Q2-".$v["T"];
			// 	$data["performance"] = json_encode($per);

				// //跟新职务
				// $duty_name = trim($v["A"]);
				// //查找是否存在在职务表中，
				// $duty_one = M("oa_position")->where(array("name"=>$duty_name))->find();
				// $duty_id = 0;
				// if($duty_one){
				// 	$duty_id = $duty_one["id"];
				// }else{
				// 	//插入数据
				// 	$d_data["name"] = $duty_name;
				// 	$duty_id = M("oa_position")->add($d_data);
				// }
				// $save["duty"] = $duty_id;

			// 	$row = M("oa_hr_manage")->where(array("id"=>$hr["id"]))->save($data);
			// 	if($row){
			// 		$rel["data"][] = "身份证号：--".$body_no."更新成功".$sql;
			// 	}else{
			// 	 	$rel["data"][] = "身份证号：--".$body_no."更新失败".$sql; 
			// 	}
			// }else{
			// 	$rel["data"][] = "身份证号：--".$body_no."没找到".$sql;
			// }
		}
		return $rel;
	}


	function updateYginfo(){
		import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Reader.Excel5");
		import("Org.Util.PHPExcel.Reader.Excel2007");
		$upload = new \Think\Upload();// 实例化上传类
		//检查客户端上传文件参数设置
		$result = array("msg"=>"上传失败，请联系超级管理员","code"=>500,"data"=>"");
		$upload->rootPath = "./upload/excel/";//保存根路径
		$upload->savePath = "";//保存根路径
		$upload->saveRule = 'uniqid';//是否自动命名
		if (! file_exists ( $upload->savePath )) {
			mkdir ( $upload->savePath );
		}
		$upload->uploadReplace = true;
		$info = $upload->upload ();
		if (! $info) {
			$result["msg"] = $upload->getError ();
			$this->ajaxReturn ($result);
		}else{
			$file = $info["files"];
			$savename ['savename'] = $filePath = $upload->rootPath .$file["savepath"]. $file ['savename'];
			$savename ['name'] = $file ['name'];
			//读取excel数据
			$PHPReader = new \PHPExcel_Reader_Excel2007();
			if(!$PHPReader->canRead($filePath)){
				$PHPReader = new \PHPExcel_Reader_Excel5();
				if(!$PHPReader->canRead($filePath)){
					$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
					return $result;
				}
			}
			
			$PHPExcel     = $PHPReader->load($filePath);
			$currentSheet = $PHPExcel->getSheet(0);
			$allColumn    = $currentSheet->getHighestColumn();
			$allRow       = $currentSheet->getHighestRow();
			// print_r($allRow);exit;
			$excelData = array();
			//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			//从哪列开始，A表示第一列
				for ($currentColumn = 'A'; $currentColumn <= "T"; $currentColumn++) {
					//数据坐标
					$address = $currentColumn.$currentRow;
					$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();

					// if($currentColumn=="D" || $currentColumn=="E" || $currentColumn=="I"){
					// 	//读取公式的值 getCalculatedValue()
					// 	$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getCalculatedValue();
					// }else{
					// 	//读取到的数据，保存到数组$arr中
					// 	$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
					// }

					// //日期转换
					// if($currentColumn=="F" || $currentColumn=="G" || $currentColumn=="H" || $currentColumn=="J" || $currentColumn=="K" || $currentColumn=="L" || $currentColumn=="M" || $currentColumn=="N" || $currentColumn=="O" || $currentColumn=="P" || $currentColumn=="Q"){
					// 	if($excelData[$currentRow][$currentColumn]){
					// 		$date_ = $this->getdate_($excelData[$currentRow][$currentColumn]);
					// 		$excelData[$currentRow][$currentColumn] = $date_;
					// 	}
					// }

					// //四舍五入，保留两位小数
					// if($currentColumn=="I"){
					// 	$sl = $excelData[$currentRow][$currentColumn];
					// 	$excelData[$currentRow][$currentColumn] = $a = number_format($sl, 1, '.', '');
					// }

				}
			}
			if(count($excelData)==0){
				unlink ($filePath);
				$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
				return $result;
			}

			//转换为数组
			// print_r($excelData);exit;
			$result = $this->doExcelToArrayDo($excelData,"updateYginfo");
			//生成导入数据日志
			$logdata = implode(",", $result["data"]);
			if($result["status"]){
				unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logdata"=>$logdata);
			}else{
				// $objReader=\PHPExcel_IOFactory::createReader("Excel5");
				// $objExcel=$objReader->load($filePath);
				// foreach($result["error"] as $v){
				// 	$objExcel->getActiveSheet()->setCellValue('D'.$v[0], $v[1]);
				// }
				// foreach($result["success"] as $v){
				// 	$objExcel->getActiveSheet()->setCellValue('D'.$v[0], '导入成功！');
				// }
				// $objWriter=\PHPExcel_IOFactory::createWriter($objExcel,"Excel5");
				// $objWriter->save($filePath);
				$result = array("msg"=>"导入失败","code"=>500,"data"=>$savename."(".$filePath.")","logdata"=>$logdata);
			}
			$this->ajaxReturn ($result);
		}
	}


	function synUpdateimportxueli($data){
		$rel  = array(
			"status" =>true,
			"data"   =>array(),
		);
		if(!$data){ return false;}
		$xuelist =  array(
						1=>"小学",
						2=>"初中",
						3=>"高中",
						4=>"大专",
						5=>"本科",
						6=>"研究生",
						7=>"硕士",
						8=>"博士",
						9=>"博士后",
						10=>"中专",
		);
		foreach ($data as $k => $v) {
			$body_no = trim($v["A"]);
			$hr = M("oa_hr_manage")->where(array("body_no"=>$body_no))->field("id")->find();
			// $sql = M("oa_hr_manage")->getLastsql();
			$sql = "";
			if($hr){
				$xueli = trim($v["B"]);
				$xueli_id = 4;
				foreach ($xuelist as $k => $v) {
					if($v==$xueli){
						$xueli_id = $k;
					}
				}
				
				$save["educate"] = $xueli_id;

				$row = M("oa_hr_manage")->where(array("id"=>$hr["id"]))->save($save);
				if($row){
					$rel["data"][] = "身份证号：--".$body_no."更新成功".$sql;
				}else{
				 	$rel["data"][] = "身份证号：--".$body_no."更新失败".$sql; 
				}
			}else{
				$rel["data"][] = "身份证号：--".$body_no."没找到".$sql;
			}
		}
		return $rel;
	}

	/**
	 * excel日期格式转换
	 * @param  [type] $t [description]
	 * @return [type]    [description]
	 */
	function getdate_($t){
		$n = intval(($t - 25569) * 3600 * 24);
		$return = gmdate('Y-m-d',$n);
		return $return;
	}

	/**
	 * 同步学历
	 * @return [type] [description]
	 */
	function importxueli(){
		import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Reader.Excel5");
		import("Org.Util.PHPExcel.Reader.Excel2007");
		$upload = new \Think\Upload();// 实例化上传类
		//检查客户端上传文件参数设置
		$result = array("msg"=>"上传失败，请联系超级管理员","code"=>500,"data"=>"");
		$upload->rootPath = "./upload/excel/";//保存根路径
		$upload->savePath = "";//保存根路径
		$upload->saveRule = 'uniqid';//是否自动命名
		if (! file_exists ( $upload->savePath )) {
			mkdir ( $upload->savePath );
		}
		$upload->uploadReplace = true;
		$info = $upload->upload ();
		if (! $info) {
			$result["msg"] = $upload->getError ();
			$this->ajaxReturn ($result);
		}else{
			$file = $info["files"];
			$savename ['savename'] = $filePath = $upload->rootPath .$file["savepath"]. $file ['savename'];
			$savename ['name'] = $file ['name'];
			//读取excel数据
			$PHPReader = new \PHPExcel_Reader_Excel2007();
			if(!$PHPReader->canRead($filePath)){
				$PHPReader = new \PHPExcel_Reader_Excel5();
				if(!$PHPReader->canRead($filePath)){
					$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
					return $result;
				}
			}
			
			$PHPExcel     = $PHPReader->load($filePath);
			$currentSheet = $PHPExcel->getSheet(0);
			$allColumn    = $currentSheet->getHighestColumn();
			$allRow       = $currentSheet->getHighestRow();
			// print_r($allRow);exit;
			$excelData = array();
			//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			//从哪列开始，A表示第一列
				for ($currentColumn = 'A'; $currentColumn <= "T"; $currentColumn++) {
					//数据坐标
					$address = $currentColumn.$currentRow;
					$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();

				}
			}
			if(count($excelData)==0){
				unlink ($filePath);
				$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
				return $result;
			}

			//转换为数组
			// print_r($excelData);exit;
			$result = $this->doExcelToArrayDo($excelData,"importxueli");
			//生成导入数据日志
			$logdata = implode(",", $result["data"]);
			if($result["status"]){
				unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logdata"=>$logdata);
			}else{
				// $objReader=\PHPExcel_IOFactory::createReader("Excel5");
				// $objExcel=$objReader->load($filePath);
				// foreach($result["error"] as $v){
				// 	$objExcel->getActiveSheet()->setCellValue('D'.$v[0], $v[1]);
				// }
				// foreach($result["success"] as $v){
				// 	$objExcel->getActiveSheet()->setCellValue('D'.$v[0], '导入成功！');
				// }
				// $objWriter=\PHPExcel_IOFactory::createWriter($objExcel,"Excel5");
				// $objWriter->save($filePath);
				$result = array("msg"=>"导入失败","code"=>500,"data"=>$savename."(".$filePath.")","logdata"=>$logdata);
			}
			$this->ajaxReturn ($result);
		}
	}



	function show1(){
		$pre_web = "http://".$_SERVER["SERVER_NAME"];
		print_r($pre_web);
		print_r("<br/>");
		$refer = 'http://' . $_SERVER ['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		print_r($refer);
	}


	/**
	 * 上传专用
	 * @return [type] [description]
	 */
	function uploadzy(){
		if(I("upshow")=="upshow023"){
			// print_r($_REQUEST);exit;
			$upload           = new \Think\Upload();// 实例化上传类
			$upload->maxSize  =     10000000000 ;// 设置附件上传大小
			// $upload->exts     =     array('jpg', 'gif', 'png', 'jpeg' , 'xlsx', 'zip' , 'rar', 'xls',"apk");// 设置附件上传类型
			
			$dir              = "./upload/charlog/";
			$upload->rootPath =     $dir; // 设置附件上传根目录
			$upload->savePath =     ''; // 设置附件上传（子）目录

			// 上传文件
			$upload->__set('saveName',time().time());
			$info   =   $upload->upload();
			if(!$info) {// 上传错误提示错误信息
				$this->assign("error",$upload->getError());
			}else{// 上传成功
			
				$file_path = $dir.$info["upfile"]["savepath"].$info["upfile"]["savename"];
				$file_path = ltrim($file_path,".");
				$this->assign("file_path",$file_path);
			}
		}
		$this->display();

	}

	/**
	 * 上传不修改文件名
	 * @return [type] [description]
	 */
	function noUpdateFileName(){
		$dir       = "./upload/charlog/";
		$info      = $this->_uplaodfile_public("files",$dir);
		$file_path = $dir.$info["files"]["savepath"].$info["files"]["savename"];
		$file_path = ltrim($file_path,".");
		$list = array("msg"=>"上传失败","data"=>$file_path,"status"=>0);
		if($info){
			$list["msg"] = "上传成功";
			$list["status"] = 1;
		}
		$this->ajaxReturn($list);
	}


	function _uplaodfile_public($name,$dir){
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     10000000000 ;// 设置附件上传大小
		// $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg' , 'xlsx', 'zip' , 'rar', 'xls',"apk","rar","1");// 设置附件上传类型

		$upload->rootPath  =     $dir; // 设置附件上传根目录
		$upload->savePath  =     ''; // 设置附件上传（子）目录

		// 上传文件
		$upload->__set('saveName',time().time());
		$info   =   $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			return $upload->getError();
		}else{// 上传成功
			return $info;
		}
	}



	function tree(){

		$this->display();
	}


	function getGroupRuleTree(){
		$groupId = I('get.id');
		if (!empty($groupId)) {
			$rules = M('auth_rule')->field('id,pid,title as name')->where('status=1')->select();
			$groupRule = M('auth_group')->where('id='.$groupId)->getField('rules');
			$groupRuleArr = explode(',',$groupRule);
			foreach($rules as $key => $val) {
				if (in_array($val['id'], $groupRuleArr)) {
					$rules[$key]['checked'] = true;
				} else {
					$rules[$key]['checked'] = false;
				}

				//添加名字后面是否去掉
				// $chbox = '';
				// $rules[$key]['name'] = $val["name"].$chbox;
			}
			$this->ajaxReturn($rules);

		}
	}


	function echophpinfo(){
		echo phpinfo();
	}




	function testriqi(){
		print_r(1);exit;
	}

	function getzhoushu($startDate,$endDate){
		//跨越天数
		$n = (strtotime($endDate)-strtotime($startDate))/86400;
		//结束时间加一天(sql语句里用的是小于和大于，如果有等于的话这句可以不要)
		$endDate = date("Y-m-d 00:00:00",strtotime("$endDate +1 day"));
		//判断，跨度小于7天，可能是同一周，也可能是两周
		if($n<7){
			//查开始时间 在 那周 的 位置
			$day            = date("w",strtotime($startDate))-1;
			//查开始时间  那周 的 周一
			$week_start        = date("Y-m-d 00:00:00",strtotime("$startDate -{$day} day"));
			//查开始时间  那周 的 周末
			$day            = 7-$day;
			$week_end        = date("Y-m-d 00:00:00",strtotime("$startDate +{$day} day"));
			//判断周末时间是否大于时间段的结束时间，如果大于，那就是时间段在同一周，否则时间段跨两周
			if($week_end>=$endDate){        
				$weekList[] =array($startDate,$endDate);
			}else{
				$weekList[] =array($startDate,$week_end);        
				$weekList[] =array($week_end,$endDate);    
			}
		}else{
			//如果跨度大于等于7天，可能是刚好1周或跨2周或跨N周，先找出开始时间 在 那周 的 位置和那周的周末时间
			$day         = date("w",strtotime($startDate))-1;
			$week_start  = date("Y-m-d 00:00:00",strtotime("$startDate -{$day} day"));
			$day         = 7-$day;
			$week_end    = date("Y-m-d 00:00:00",strtotime("$startDate +{$day} day"));
			//先把开始时间那周写入数组
			$weekList[]  =array($startDate,$week_end); 
			//判断周末是否大于等于结束时间，不管大于(2周)还是等于(1周)，结束时间都是时间段的结束时间。
			if($week_end >= $endDate){
				$weekList[] = array($week_end,$endDate);
			}else{
				//N周的情况用while循环一下，然后写入数组
				while($week_end <= $endDate){
				    $start         = $week_end;
				    $week_end    = date("Y-m-d 00:00:00",strtotime("$week_end +7 day"));
				    if($week_end <= $endDate){
				        $weekList[]  = array($start,$week_end);
				    }else{
				        $weekList[]  = array($start,$endDate);
				    }
				}
			}
		}
	}

	function test1122(){
		print_r($_SERVER ['SERVER_NAME']);
		print_r("<br/>");
		print_r($_SERVER['REQUEST_URI']);
		print_r("<br/>");
		$refer = 'http://' . $_SERVER ['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		print_r($refer);

		print_r(1212121);exit;
	}



	function test1127(){
		$ys = "loginBj.jpg";
		$index = strrpos($ys,"\\");
		print_r($index);
		if($index){
			$wz = substr($ys, strrpos($ys,"\\")+1);
            print_r($wz);
		}
            // $wz = substr($ys, strrpos($ys,"\\")+1);
            // print_r($wz);
		// print_r($_POST);exit;
	}

	function testmongodb1205(){
		import("Org.Util.Hgmongodb");
		$mongo          = new \Hgmongodb(); 

		//------------查询

		$filter = array(
				"dataid"=>"464170"
			);

		$queryWriteOps = [
			'projection' => ['_id' => 0],
		    "sort"       => ["id" => -1],
		    "limit"      => 10,
		];
		$mongo->connect();//连接mongodb，这是一个触发式的连接
		$params = array(
			"table"=>"bos_daydatalog",
			"limit"=>10,
			"db_table"=>"boss3_www.bos_daydatalog",
			"filter"=>$filter,
			"queryWriteOps"=>$queryWriteOps,
			"showsql"=>I("showsql023")
			);
		$user  = $mongo->newSelect($params);
		// $user = $mongo->select($params);
		var_dump($user);

	}

	function getdataformo(){
		import("Org.Util.Hgmongodb");
        $mongo          = new \Hgmongodb(); 

        //------------查询
        $filter = [
             // "dataid" => I('post.id'),
                "dataid" => $_GET['id'],
             "datatype" => 1,
        ];
        $queryWriteOps = [
            "projection" => ["_id"   => 0,'dataid' =>1 , 'remark'=>1 , 'datatype'=>1,'addtime'=>1,'username'=>1,'olddata'=>1],
            "sort"       => ["id" => -1],
            "limit"      => 200,
        ];
        $mongo->connect();//连接mongodb，这是一个触发式的连接
        $params = array(
            "table"=>"bos_daydatalog",
            "limit"=>200,
            "db_table"=>"boss3_www.bos_daydatalog",
            "filter"=>$filter,
            "queryWriteOps"=>$queryWriteOps
            );
        $user = $mongo->select($params);
    	echo json_encode($user);
	}

	function insertmongodbxq(){
		import("Org.Util.Hgmongodb");
		$mongo          = new \Hgmongodb(); 

		//增加
		$mongo->connect();//连接mongodb，这是一个触发式的连接
		$params = array(
			"db_table"=>"boss3_www.bos_daydatalog",
			"datas" =>array()
		);
		$p=I('get.p',0);
		$list = M("daydata_log")->limit(($p*100).',100')->select();
		if(count($list)<100)exit();
		
			$params["datas"]=$list;

		// print_r($params);
		// for ($i=1; $i < 2; $i++) { 
		// 	$one               = array();
		// 	$one["id"]         =$i;
		// 	$one["dataid"]     =rand(1,100);
		// 	$one["remark"]     ="sjljdld_".rand(8,100);
		// 	$one["datatype"]   =rand(1,2);
		// 	$one["addtime"]    =time();
		// 	$one["username"]   ="zhjangsan_".rand(1,100);
		// 	$one["olddata"]    = rand(0,50);
		// 	$params["datas"][0][] = $one;
		// 	unset($one);
		// }
		
		// print_r($params);exit;

		$row = $mongo->newInsert($params);
		print_r($row);
		print_r("<br/>----------------------");
		if(count($list)<100)exit();
		echo "<script>window.location='?p=".($p+1)."'</script>";

	}




}
?>
