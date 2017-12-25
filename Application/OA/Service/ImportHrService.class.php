<?php
/**
* 导入hrservice 
*/
namespace OA\Service;
use Think\Model;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class ImportHrService
{
	/**
	 * 导入员工信息
	 * @return [type] [description]
	 */
	function importHrUser(){
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
			// print_r(1);exit;
			$currentSheet = $PHPExcel->getSheet(0);
			$allColumn    = $currentSheet->getHighestColumn();
			$allRow       = $currentSheet->getHighestRow();
			// print_r($allRow);exit;
			$excelData = array();
			//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			//从哪列开始，A表示第一列
				for ($currentColumn = 'A'; $currentColumn <= "Z"; $currentColumn++) {
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
			$result = $this->doExcelToArrayHR($excelData);
			//生成导入数据日志
			$logdata = implode(",", $result["data"]);
			$_SESSION["SHOWLOG_LIST"] = $logdata;
			if($result["status"]){
				unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logdata"=>$logdata);
				return $result;
			}else{
				$result = array("msg"=>"导入失败","code"=>500,"data"=>$savename."(".$filePath.")","logdata"=>$logdata);
				return $result;
			}
		}
	}

	/**
	 * 添加导入操作 20160406
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
    public function doExcelToArrayHR($data){
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
            $doExcelDbResult        = $this->createHrData($toDbArray);
			$returnArray["status"] = $doExcelDbResult["status"];
			$returnArray["data"]    = $doExcelDbResult["data"];
        }else{
            $returnArray["status"] = false;
        }
        return $returnArray;
    }

	/**
	 * [添加时后台重新计算当前员工的工龄补贴 description]
	 * @param  [type] $level  [description]
	 * @param  [type] $siling [description]
	 * @return [type]         [description]
	 */
	private function getUserGongLingMoney1($level,$siling){
		$result = array("gongling_money"=>0,"jt_money"=>0,"zx_money"=>0);
		
		$siling_int     = intval($siling);
		$siling_int_1 = $siling_int-1;
		if($level==1 && $siling>1){
			$result["gongling_money"] = 100+($siling_int_1*50);
		}
		if($level==1){
			$result["jt_money"] = 100;
		}


		if($level==2 && $siling>1){
			$result["gongling_money"] = 200+($siling_int_1*50);
		}
		if($level==2){
			$result["jt_money"] = 200;
			$result["zx_money"] = 100;
		}


		if($level==3 && $siling>1){
			$result["gongling_money"] = 300+($siling_int_1*50);
		}
		if($level==3){
			$result["jt_money"] = 200;
			$result["zx_money"] = 200;
		}
		unset($siling_int);unset($siling_int_1);
		return $result;
	}


	private function getJbGongZi($level){
		$result = array("basic_pay"=>0,"per_radio"=>0);
		if($level==1){
			$result["basic_pay"]=2000;
			$result["per_radio"]=0.1;
		}
		if($level==2){
			$result["basic_pay"]=3000;
			$result["per_radio"]=0.2;
		}
		if($level==3){
			$result["basic_pay"]=4000;
			$result["per_radio"]=0.3;
		}
		if($level==4){
			$result["basic_pay"]=5000;
			$result["per_radio"]=0.4;
		}
		return $result;
	}

	/**
	 * 获取框架id
	 * @return [type] [description]
	 */
	private function getOrganFrameId1($pid){
		$frame_id = 0;
		//organ_frame_id--组织框架id,如 168--业务中心，169--支撑中心，181--职能中心-邓欣，185--职能中心-罗红梅
		if($pid==168 || $pid==169 || $pid==181 || $pid==185){
			$frame_id = $pid;
		}
		unset($pid);
		return $frame_id;
	}

    /**
     * 写入数据库
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    function createHrData($data){
    	// print_r($data);exit;
    	$rel  = array(
			"status" =>true,
			"data"   =>array(),
		);
		if(!$data){ return false;}
		$depSer             = new Service\DepartSettingService();
		$educateTree        = getNameByEducateTree();
		$employeeStatusTree = getNameByStatusTree();
		$companyTree        = $depSer->getCompanyIDByNameTree();
		$userSer            = new Service\UserService();
		$hrSer              = new Service\HrManageService();
		import("Org.Util.Pinyin");
		$py          = new \Pinyin(); 
		$com_Name    = "";
		foreach ($data as $k => $v) {
			if($k==0) continue;
			//检查当前用户是在存在在用户表中user;real_name,status=1,username(拼音)
			$save_["user_name"] = trim($v["E"]);
			$body_no            = trim($v["K"]);

			//检查hr_manage表中数据是否存在:身份证号
			$where_ = array();
			$where_["body_no"]         = $body_no;
			$hrOne                     = $hrSer->getOneHRByWhere($where_,"id");

			//添加
			if(!$hrOne){
				//判断当前用户名是否在user表中存在
				$user_one = $userSer->getOneByWhere(array("real_name"=>$save_["user_name"]),"id");
				if($user_one["id"]){
					$save_["user_name"] .= "01";
				}
			}
			

			if(!$save_["user_name"]) continue;
			if(!$body_no) continue;
			$py->convert($save_["user_name"],"",$allWord,$firstWord);
			$save_["username_pinyin"] = $allWord[0];
			$save_["body_no"] = $body_no;

			//邮箱--系统根据用户名生成
			$save_["post_email"] = $save_["username_pinyin"]."@yandui.com";

			$c_name = trim($v["B"]);
			if(!$c_name){
				$c_name = $com_Name;
			}else{
				$com_Name = $c_name;
			}
			$save_["company_id"]         = $companyTree[$c_name]["id"];


			//一级部门，二级部门
			$first_depart_name       = trim($v["C"]);
			$sec_depart_name         = trim($v["D"]);
			$depInfo                 = $depSer->getOneByWhere(array("name"=>$sec_depart_name),"id,name,pid");
			$sec_depart_id           = $depInfo["id"];
			
			$save_["depart_id"]      = $sec_depart_id;
			$save_["depart_name"]    = $sec_depart_name;
			
			//更新一级部门id
			$leve_id                 = getLeveDepartId($save_["depart_name"],$save_["depart_id"],$depInfo["pid"]);
			$save_["leve_depart_id"] = $leve_id;
			
			//organ_frame_id--组织框架id,如 168--业务中心，169--支撑中心，181--职能中心-邓欣，185--职能中心-罗红梅
			$depInfo                 = $depSer->getOneByWhere(array("id"=>$leve_id),"pid");
			$organ_frame_id          = $this->getOrganFrameId1($depInfo["pid"]);
			$save_["organ_frame_id"] = $organ_frame_id;
			unset($organ_frame_id);

			//
			$dname = trim($v["F"]);
			$done = M("oa_position")->field("id,name")->where(array("name"=>$dname))->find();
			$save_["duty"] = $done["id"];

			$save_["job_rating"] = trim($v["G"]);
			$save_["grade_rank"] = trim($v["H"]);

			//jiceng
			$jc = trim($v["I"]);
			$jc_id = 1;
			switch ($jc) {
				case '基层':
					$jc_id = 1;
					break;
				
				case '中层':
					$jc_id = 2;
					break;
				case '高层':
				$jc_id = 3;
				break;
				case '战略执行层':
				$jc_id = 4;
				break;
			}
			$save_["level"] = $jc_id;

			$save_["sex"] = $v["J"]=="男"?0:1;
			//根据身份证号获取年龄和出生年月日
			$age                     = getAgeByBodyNo($save_["body_no"]);
			$birth                   = substr($save_["body_no"],6,8);
			$save_["age"]            = $age;
			$save_["birth"]          = $birth;
			$save_["start_work"]     = trim($v["L"]);
			$save_["is_marry"]       = trim($v["M"])=="已婚"?1:0;
			$save_["height"]         = trim($v["N"]);
			$save_["weight"]         = trim($v["O"]);
			$save_["account_nature"] = trim($v["P"])=="农村"?0:1;
			$save_["nation"] = trim($v["Q"]);
			$save_["passby"] = trim($v["R"]);
			$save_["adress"] = trim($v["S"]);
			$save_["id_card_address"] = trim($v["T"]);
			$save_["emergen_user"] = trim($v["U"]);
			$save_["relation_me"] = trim($v["V"]);
			$save_["emergen_phone"] = trim($v["W"]);

			$xueli = trim($v["X"]);
			$xueli_id = 3;
			switch ($xueli) {
				case '小学': $xueli_id = 1;break;
				case '初中': $xueli_id = 2;break;
				case '高中': $xueli_id = 3;break;
				case '大专': $xueli_id = 4;break;
				case '本科': $xueli_id = 5;break;
				case '研究生': $xueli_id = 6;break;
				case '硕士': $xueli_id = 7;break;
				case '博士': $xueli_id = 8;break;
				case '博士后': $xueli_id = 9;break;
				case '中专': $xueli_id = 10;break;
			}
			$save_["educate"] = $xueli_id;
			$save_["educate_name"] = $xueli;

			$save_["graduat_time"] = trim($v["Y"]);
			$save_["graduat_school"] = trim($v["Z"]);
			$save_["profession"] = trim($v["AA"]);
			$save_["phone"] = trim($v["AB"]);
			$save_["back_phone"] = trim($v["AC"]);
			$save_["qq"] = trim($v["AD"]);
			$save_["entry_time"] = trim($v["AE"]);

			$entry_time = $save_["entry_time"];
			if($entry_time){
				$diff_days  = getDatesDiff($entry_time,date("Y-m-d",time()));
				$diff_year  = 0;
				$diff_month = 0;
				if(!empty($diff_days["y"]) && $diff_days["y"]!="00"){
					$diff_year = preg_replace('/^0*/', '', $diff_days["y"]);
				}
				if(!empty($diff_days["m"]) && $diff_days["m"]!="0"){
					$diff_month = $diff_days["m"];
				}
				$save_["company_age"] = $diff_year.".".$diff_month;
			}

			$save_["proposed_time"] = trim($v["AF"]);
			$save_["real_proposed"] = trim($v["AG"]);//实转正日期

			//用户信息-user表 ？？
			$user_result              = $this->_checkSystemUserGetUserId($userSer,$save_);
			$user_id                  = $user_result["data"]["user_id"];
			$job_no                   = $user_result["data"]["employee_number"];

			$save_["job_no"]          = $job_no;
			$save_["user_id"]         = $user_id;
			$save_["post_email"] = $save_["username_pinyin"]."@yandui.com";

			//------------------福利信息
			$result_fl                   = $this->getUserGongLingMoney1($save_["level"],$save_["company_age"]);
			
			$save_["age_allowance"]      = $result_fl["gongling_money"];
			$save_["trasport_allowance"] = $result_fl["jt_money"];
			$save_["commun_allowance"]   = $result_fl["zx_money"];
			
			$save_["social_man"]         = trim($v["AH"]);
			$save_["provident_start"]    = trim($v["AI"]);
			$save_["social_card"]        = trim($v["AJ"]);
			
			$save_["entry_salary"]       = trim($v["AK"]);
			$save_["turn_salary"]        = trim($v["AL"]);

			//基本工资
			$jbgongzi                    = $this->getJbGongZi($save_["level"]);
			$save_["per_radio"]          = $jbgongzi["per_radio"];
			$save_["basic_pay"]          = $jbgongzi["basic_pay"];
			$save_["per_pay"]            = $save_["turn_salary"]*$save_["per_radio"];
			$save_["job_salary"]         = $save_["turn_salary"]-$save_["basic_pay"]-$save_["per_pay"];
			
			$save_["job_salary"]         = $save_["turn_salary"]-$save_["basic_pay"]-$save_["per_pay"];

			//第一次签证合同开始时间
			$save_["first_visa_start"] = trim($v["AS"]);
			$save_["fist_visa_end"]    = trim($v["AT"]);
			
			$save_["sec_visa_start"]   = trim($v["AU"]);
			$save_["sec_visa_end"]     = trim($v["AV"]);
			$save_["third_visa_start"] = trim($v["AW"]);
			
			$save_["probihibe"]    = trim($v["AX"]);
			$save_["bank_cardno1"] = trim($v["AY"]);
			$save_["bank_card1"]   = trim($v["AZ"]);
			$save_["bank_cardno2"] = trim($v["BA"]);
			$save_["bank_card2"]   = trim($v["BB"]);

			//其它信息
			$save_["sign_company"] = trim($v["BC"]);
			$yg_status = trim($v["BD"]);
			$yg_zt_id = 0;
			switch ($yg_status) {
				case '入职(试用)':$yg_zt_id = 0;break;
				case '离职':$yg_zt_id     = 1;break;
				case '转新公司':$yg_zt_id   = 2;break;
				case '正式员工':$yg_zt_id   = 3;break;
				case '外聘':$yg_zt_id     = 4;break;
			}
			$save_["status"] = $yg_zt_id;
			$save_["departure_time"] = trim($v["BE"]);
			$save_["rec_channel"] = trim($v["BF"]);
			$save_["job_change"] = trim($v["BG"]);
			//---------------------------分割线


			
			if($hrOne){
				//修改
				$where_ = array();
				$where_["id"] = $hrOne["id"];
				$row = $hrSer->saveHRData($where_,$save_);
				if($row){
					$rel["data"][] = "用户id:".$user_id."-用户姓名：".$save_["user_name"]."-身份证号：".$save_["body_no"]."修改信息成功;";
				}else{
					$rel["data"][] = "用户id:".$user_id."-用户姓名:".$save_["user_name"]."-身份证号：".$save_["body_no"]."修改信息失败或者没修改，请检查;";
				}
			}else{
				//添加
				// $save_["depart_name"]    = "研发组";//部门名称?
				// $save_["depart_id"]      = "180";//部门id？
				$save_["creat_uid"]      = UID;
				$save_["third_visa_end"] = "";
				$save_["post_level"]     = "";//岗位层级
				$save_["start_insure"]   = "";//参保时间?
				$save_["dateline"]       = date("Y-m-d H:i:s",time());
				$row = $hrSer->addHRData($save_);
				if($row){
					$rel["data"][] = "用户id:".$user_id."-用户姓名".$save_["user_name"]."-身份证号：".$save_["body_no"]."添加信息成功;";
				}else{
					$rel["data"][] = "用户id:".$user_id."-用户姓名".$save_["user_name"]."-身份证号：".$save_["body_no"]."添加信息失败，请检查;";
				}
			}
			unset($hrOne);
			unset($save_);
		}
		return $rel;
    }


     /**
     * 检查用户信息,存在=》修改；不存在=》添加；最终返回user_id
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    private function _checkSystemUserGetUserId($userSer_,$params){
		//如果重现重名且在职，那么提示用户重名
		$userSer = !$userSer_ ? new Service\UserService() : $userSer_;
		// $where_["real_name"] = $params["user_name"];
		// $where_["status"]    = 1; 
		$where_["username"]  = $params["username_pinyin"];
		$one                 = $userSer->getOneByWhere($where_);
		$result = array("status"=>0,"msg"=>"创建用户失败,请联系管理员","data"=>array());
		if($one){
			//修改信息
			$data["gender"]       =  $params["sex"];
			$data["mobile"]       =  $params["phone"];
			$data["qq"]           =  $params["qq"];
			$data["education"]    =  $params["educate_name"];
			$data["ethnic_group"] =  $params["nation"];
			$data["major"]        =  $params["profession"];
			$data["address"]      =  $params["adress"];
			$data["ismarried"]    =  $params["is_marry"];
			$data["status"]       =  $params["status"] == 1 ?0:1;
			$where_               = array();
			$where_["id"]         = $one["id"];
			$row                  = $userSer->saveUserData($where_,$user_);
			$result = array("status"=>200,"msg"=>"成功","data"=>array("user_id"=>$one["id"],"employee_number"=>$one["employee_number"]));
		}else{
			//添加信息
			$data["dept_id"]      =  $params["depart_id"];
			$data["position_id"]  =  16;//专员 //角色，权限

			// $data["group_id"]     = 30;//只分配oa管理权限 //角色，权限
			$data["real_name"]    =  $params["user_name"];
			$data["username"]     =  $params["username_pinyin"];
			$data["password"]     =  boss_md5(123456, UC_AUTH_KEY);
			$data["reg_time"]     =  time();
			$data["gender"]       =  $params["sex"];
			$data["mobile"]       =  $params["phone"];
			$data["qq"]           =  $params["qq"];
			$data["education"]    =  $params["educate_name"];
			$data["ethnic_group"] =  $params["nation"];
			$data["major"]        =  $params["profession"];
			$data["address"]      =  $params["adress"];
			$data["ismarried"]    =  $params["is_marry"];
			$data["status"]       =  $params["status"] == 1 ?0:1;

			$user_id                  = $userSer->addData($data);
			//添加成功
			$where["id"]              = $user_id;
			$data_["employee_number"] = $userSer->generalOACode($user_id);
			$row                      = $userSer->saveUserData($where,$data_);
			if($row){
				$result = array("status"=>200,"msg"=>"成功","data"=>array("user_id"=>$user_id,"employee_number"=>$data_["employee_number"]));
				//添加角色
				$data_1["uid"]      = $row;
				$data_1["group_id"] = 30;
				$row = M("auth_group_access")->add($data_1);
			}
		}
		return $result;
	} 
}
?>