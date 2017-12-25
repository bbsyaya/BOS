<?php
/**
* 人事管理
*/
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class HrManageController extends BaseController
{
	private $hrManageSer;
	function _initialize(){
		parent::_initialize();
		$this->hrManageSer = !$this->hrManageSer ? new Service\HrManageService() : $this->hrManageSer;
	}

	/**
	 * list
	 * @return [type] [description]
	 */
	function hrList(){
		$map["user_name"]        = I("user_name");
		$map["entry_time_sdate"] = I("entry_time_sdate");
		$map["entry_time_edate"] = I("entry_time_edate");
		//条件筛选
		$where = "1=1";
		if($map["user_name"]){
			$where .= " and h.user_name like '%".$map["user_name"]."%'";
		}
		if(!empty($map["entry_time_sdate"]) &&  empty($map["entry_time_edate"])){
			$where .= " and h.entry_time >='".$map["entry_time_sdate"]."'";
		}
		if(empty($map["entry_time_sdate"]) &&  !empty($map["entry_time_edate"])){
			$where .= " and h.entry_time <='".$map["entry_time_edate"]."'";
		}
		if(!empty($map["entry_time_sdate"]) &&  !empty($map["entry_time_edate"])){
			$where .= " and h.entry_time >='".$map["entry_time_sdate"]."' and h.entry_time<='".$map["entry_time_edate"]."'";
		}
		$this->assign("map",$map);

		$fields   = "h.id,h.company_id,h.start_insure,d.name as depart_name,h.user_name,h.duty,h.post_level,h.sex,h.body_no,h.entry_time,h.bank_cardno1,h.bank_card1,h.entry_salary,h.turn_salary";
		$order    = "h.dateline desc";
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$count    = $this->hrManageSer->getHRListCountByWhere($where,true);
		$page     = new \Think\Page($count, $listRows);
		$list     = $this->hrManageSer->getHRListByWhere($where,$fields,$order,$page->firstRow,$page->listRows,true);
		$this->assign("page",$page->show());
		$this->assign("list",$list);

		//公司列表
		$this->assign("op_companyList",getCompanyList());
		$this->display();
	}

	/**
	 * 入职
	 * @return [type] [description]
	 */
	function entry(){
		$id = trim($_REQUEST["editeId"]);
		if($id>0){
			//编辑数据
			$one = $this->hrManageSer->getOneHRByWhere(array("id"=>$id));
			$this->assign("one",$one);
		}
		$this->assign("is_marry",C('OPTION.is_marry'));
		$this->assign("level",C('OPTION.level'));
		$this->assign("educate",C('OPTION.educate'));
		$this->assign("sexOptions",C('OPTION.sexOptions'));
		$this->assign("accountNature",C('OPTION.accountNature'));
		$this->assign("userAttr",C('OPTION.userAttr'));
		$this->display();
	}

	function test(){
		$post = array(
			"entry_salary"=>"4000",
			"turn_salary"=>"2000",
			"per_radio"=>"0.2",
			"per_pay"=>"10001",
			"basic_pay"=>"1000",
			"job_salary"=>"1000",
			);
		// $post = array(
		// 	"new_company_id"=>"2",
		// 	"new_depart"=>"39",
		// 	);
		$this->writeHRLogs(20,$post,"SAVEENTRY");
		print_r("over");
		exit;
		//end test	
	}

	/**
	 * 保存
	 * @return [type] [description]
	 */
	function saveEntry(){
		// print_r($_POST);exit;
		$result       = array("msg"=>"保存失败");
		$id           = I("hrid");
		//部门名称
		$depart_id    = trim(I("depart_id"));
		$depSer       = new Service\DepartSettingService();
		$depInfo      = $depSer->getOneByWhere(array("id"=>$depart_id),"name");
		//保存数据
		$saveData["company_id"]         = trim(I("company_id"));
		$saveData["job_rating"]         = trim(I("job_rating"));
		$saveData["adress"]             = trim(I("adress"));
		$saveData["start_insure"]       = trim(I("start_insure"));
		$saveData["grade_rank"]         = trim(I("grade_rank"));
		$saveData["id_card_address"]    = trim(I("id_card_address"));
		$saveData["company_id"]         = trim(I("company_id"));
		$saveData["depart_id"]          = trim(I("depart_id"));
		$saveData["depart_name"]        = trim($depInfo["name"]);
		$saveData["level"]              = trim(I("level"));
		$saveData["birth"]              = trim(I("birth"));
		$saveData["user_name"]          = trim(I("user_name"));
		$saveData["emergen_user"]       = trim(I("emergen_user"));
		$saveData["relation_me"]        = trim(I("relation_me"));
		$saveData["phone"]              = trim(I("phone"));
		$saveData["back_phone"]         = trim(I("back_phone"));
		$saveData["job_no"]             = trim(I("job_no"));
		$saveData["duty"]               = trim(I("duty"));
		$saveData["age"]                = trim(I("age"));
		$saveData["emergen_phone"]      = trim(I("emergen_phone"));
		$saveData["post_level"]         = trim(I("post_level"));
		$saveData["start_work"]         = trim(I("start_work"));
		$saveData["educate"]            = trim(I("educate"));
		$saveData["sex"]                = trim(I("sex"));
		$saveData["is_marry"]           = trim(I("is_marry"));
		$saveData["graduat_time"]       = trim(I("graduat_time"));
		$saveData["body_no"]            = trim(I("body_no"));
		$saveData["passby"]             = trim(I("passby"));
		$saveData["entry_time"]         = trim(I("entry_time"));
		$saveData["proposed_time"]      = trim(I("proposed_time"));
		$saveData["real_proposed"]      = trim(I("real_proposed"));
		$saveData["graduat_school"]     = trim(I("graduat_school"));
		$saveData["weight"]             = trim(I("weight"));
		$saveData["height"]             = trim(I("height"));
		$saveData["profession"]         = trim(I("profession"));
		$saveData["account_nature"]     = trim(I("account_nature"));
		$saveData["nation"]             = trim(I("nation"));
		$saveData["qq"]                 = trim(I("qq"));
		$saveData["age_allowance"]      = trim(I("age_allowance"));
		$saveData["trasport_allowance"] = trim(I("trasport_allowance"));
		$saveData["commun_allowance"]   = trim(I("commun_allowance"));
		$saveData["entry_salary"]       = trim(I("entry_salary"));
		$saveData["turn_salary"]        = trim(I("turn_salary"));
		$saveData["per_radio"]          = trim(I("per_radio"));
		$saveData["per_pay"]            = trim(I("per_pay"));
		$saveData["basic_pay"]          = trim(I("basic_pay"));
		$saveData["job_salary"]         = trim(I("job_salary"));
		$saveData["try_start"]          = trim(I("try_start"));
		$saveData["try_end"]            = trim(I("try_end"));
		$saveData["social_card"]        = trim(I("social_card"));
		$saveData["provident_start"]    = trim(I("provident_start"));
		$saveData["social_man"]         = trim(I("social_man"));
		$saveData["first_visa_start"]   = trim(I("first_visa_start"));
		$saveData["fist_visa_end"]      = trim(I("fist_visa_end"));
		$saveData["sec_visa_start"]     = trim(I("sec_visa_start"));
		$saveData["sec_visa_end"]       = trim(I("sec_visa_end"));
		$saveData["third_visa_start"]   = trim(I("third_visa_start"));
		$saveData["third_visa_end"]     = trim(I("third_visa_end"));
		$saveData["bank_cardno1"]       = trim(I("bank_cardno1"));
		$saveData["bank_card1"]         = trim(I("bank_card1"));
		$saveData["bank_cardno2"]       = trim(I("bank_cardno2"));
		$saveData["bank_card2"]         = trim(I("bank_card2"));
		$saveData["company_age"]        = trim(I("company_age"));
		$saveData["finger_no"]          = trim(I("finger_no"));
		$saveData["rec_channel"]        = trim(I("rec_channel"));
		$saveData["sign_company"]       = trim(I("sign_company"));
		$saveData["probihibe"]          = trim(I("probihibe"));
		$saveData["job_change"]         = trim(I("job_change"));
		$saveData["status"]             = trim(I("status"));

		if($id>0){ //修改
			$this->writeHRLogs($id,$saveData,"SAVEENTRY");
			$where["id"]   = $id;
			$row           = $this->hrManageSer->saveHRData($where,$saveData);
			$result["msg"] = "修改成功";
		}else{ //添加
			//判断身份证号是否唯一
			$body_no = trim(I("body_no"));
			$row     = $this->hrManageSer->getOneHRByWhere(array("body_no"=>$body_no),"id");
			if($row){
				$result["msg"] = "身份证号重复了";
			}else{
				//默认参数
				$saveData["creat_uid"] = UID;
				$saveData["status"]    = 0;
				$saveData["dateline"]  = date("Y-m-d H:i:s",time());
				$row  = $this->hrManageSer->addHRData($saveData);
				$result["msg"] = "生成成功";
			}	
		}
		$this->ajaxReturn($result);
		
	}


	/**
	 * 调薪记录，职位变动记录;
	 * @param  [type] $id   [description]
	 * @param  [type] $post [description]
	 * @return [type]       [description]
	 */
	function writeHRLogs($id,$post,$type){
		$sql = "SELECT 
					h.`entry_salary`,
					h.turn_salary,
					h.per_radio,
					h.per_pay,
					h.basic_pay,
					h.job_salary,
					u.real_name,
					h.`user_name`,h.company_id,h.`depart_id`,h.age_allowance,h.trasport_allowance,h.commun_allowance,h.level,h.post_level
				FROM
				  `boss_hr_manage` AS h 
				  LEFT JOIN `boss_user` AS u 
				    ON h.`creat_uid` = u.`id` 
				WHERE h.id = {$id} ";
		$model       = new \Think\Model();
		$old         = $model->query($sql);
		$old         = $old[0];
		$message     = array();
		$update_time = date("Y-m-d H:i:s",time());
		$userSer     = new Service\UserService();
		$userObj     = $userSer->getOneByWhere(array("id"=>UID),"real_name");

		if($type == "SAVEENTRY"){
			//薪资变动
			$post_entry_salary = is_numeric($post["entry_salary"])>=0?$post["entry_salary"]:"";
			if(($post_entry_salary!="") && ($old["entry_salary"]-$post["entry_salary"]!=0)){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."入职薪资：".$old["entry_salary"]."元=>".$post["entry_salary"]."元";
				$str["type_id"] = 1;
				$message[]      = $str;
			}

			$post_turn_salary = is_numeric($post["turn_salary"])>=0?$post["turn_salary"]:"";
			if(($post_turn_salary!="") && ($old["turn_salary"]-$post["turn_salary"]!=0)){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."转正薪资：".$old["turn_salary"]."元=>".$post["turn_salary"]."元";
				$str["type_id"] = 2;
				$message[]      = $str;
			}

			$post_per_pay = is_numeric($post["per_pay"])>=0?$post["per_pay"]:"";
			if(($post_per_pay!="") && ($old["per_pay"]-$post["per_pay"]!=0)){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."绩效工资：".$old["per_pay"]."元=>".$post["per_pay"]."元";
				$str["type_id"] = 3;
				$message[]      = $str;
			}

			$post_per_radio = is_numeric($post["per_radio"])>=0?$post["per_radio"]:"";
			if(($post_per_radio!="") && ($old["per_radio"]-$post["per_radio"]!=0)){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."绩效考核比例：".$old["per_radio"]."=>".$post["per_radio"];
				$str["type_id"] = 4;
				$message[]      = $str;
			}

			$post_basic_pay = is_numeric($post["basic_pay"])>=0?$post["basic_pay"]:"";
			if(($post_basic_pay!="") && ($old["basic_pay"]-$post["basic_pay"]!=0)){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."基本工资：".$old["basic_pay"]."元=>".$post["basic_pay"]."元";
				$str["type_id"] = 5;
				$message[]      = $str;
			}

			$post_job_salary = is_numeric($post["job_salary"])>=0?$post["job_salary"]:"";
			if(($post_job_salary!="") && ($old["job_salary"]-$post["job_salary"]!=0)){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."职务工资：".$old["job_salary"]."元=>".$post["job_salary"]."元";
				$str["type_id"] = 6;
				$message[]      = $str;
			}

			//职位变动
			$companys = C('OPTION.company_list');
			if((!empty($post["company_id"])) && ($old["company_id"]!=$post["company_id"])){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."入职公司：".$companys[$old["company_id"]]."=>".$companys[$post["company_id"]];
				$str["type_id"] = 7;
				$message[]      = $str;
			}
			if((!empty($post["depart_id"])) && ($old["depart_id"]!=$post["depart_id"])){
				//重组数据
				$new_departList = $this->makeNewDepartList($post["depart_id"],$old["depart_id"]);
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."部门：".$new_departList[$old["depart_id"]]["name"]."=>".$new_departList[$post["depart_id"]]["name"];
				$str["type_id"] = 8;
				$message[]      = $str;
			}

			//post_level-岗位层级
			if((!empty($post["post_level"])) && ($old["post_level"]!=$post["post_level"])){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."岗位层级：".$old["post_level"]."=>".$post["post_level"];
				$str["type_id"] = 9;
				$message[]      = $str;
			}

			//level-层级
			if((!empty($post["level"])) && ($old["level"]!=$post["level"])){
				$level_ops      = C("OPTION.level");
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."层级：".$level_ops[$old["level"]]."=>".$level_ops[$post["level"]];
				$str["type_id"] = 12;
				$message[]      = $str;
			}

			//工龄津贴-age_allowance
			$post_age_allowance = is_numeric($post["age_allowance"])>=0?$post["age_allowance"]:"";
			if(($post_age_allowance!="") && ($old["age_allowance"]!=$post["age_allowance"])){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."工龄津贴：".$old["age_allowance"]."=>".$post["age_allowance"];
				$str["type_id"] = 13;
				$message[]      = $str;
			}

			//交通补助-trasport_allowance
			$post_trasport_allowance = is_numeric($post["trasport_allowance"])>=0?$post["trasport_allowance"]:"";
			if(($post_trasport_allowance!="") && ($old["trasport_allowance"]!=$post["trasport_allowance"])){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."交通补助：".$old["trasport_allowance"]."=>".$post["trasport_allowance"];
				$str["type_id"] = 14;
				$message[]      = $str;
			}

			//通讯补助-commun_allowance
			$post_commun_allowance = is_numeric($post["commun_allowance"])>=0?$post["commun_allowance"]:"";
			if(($post_commun_allowance!="") && ($old["commun_allowance"]!=$post["commun_allowance"])){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."通讯补助：".$old["commun_allowance"]."=>".$post["commun_allowance"];
				$str["type_id"] = 15;
				$message[]      = $str;
			}
		}
		//保存日志
		if($message){
			foreach ($message as $k => $v) {
				$one            = array();
				$one["data"]    = $v["msg"];
				$one["hrid"]    = $id;
				$one["uid"]     = UID;
				$one["type_id"] = $v["type_id"];
				$row            = $this->hrManageSer->addHRLogData($one);
			}
		}

	}

	/**
	 * 重组数组
	 * @param  [type] $post_depart_id [description]
	 * @param  [type] $old_depart_id  [description]
	 * @return [type]                 [description]
	 */
	function makeNewDepartList($post_depart_id,$old_depart_id){
		$dids = $post_depart_id.",".$old_depart_id;
		$where["id"] = array("in",$dids);
		$departList = M("user_department")->field("id,name")->where($where)->select();
		//重组数据
		$new_departList = array();
		foreach ($departList as $k => $v) {
			$new_departList[$v['id']]["name"] = $v["name"];
		}
		return $new_departList;
	}

	/**
	 * 办理离职
	 * @return [type] [description]
	 */
	function resignationDo(){
		$result                     = array("msg"=>"办理出错，请联系管理员","status"=>500);
		$ids                        = trim(I("ids"));
		$where["_string"]           = " id in ('{$ids}')";
		$data["departure_time"]     = I("departure_time");
		$data["social_close_time"]  = I("social_close_time");
		$data["prident_close_time"] = I("prident_close_time");
		$data["status"]             = 1;//状态。0--入职(试用)，1--离职，2-转新公司，3-正式员工
		$row             = $this->hrManageSer->saveHRData($where,$data);
		if($row) {
			$result  = array("msg"=>"办理成功","status"=>200);
			//记录日志
			$userSer = new Service\UserService();
			$userObj = $userSer->getOneByWhere(array("id"=>UID),"real_name");
			$time    = date("Y-m-d H:i:s",time());
			$list    = explode(",", $ids);
			foreach ($list as $k => $v) {
				$one            = array();
				$one["data"]    = $userObj["real_name"]."在".$time."办理员工id为(".$v.")的离职操作(departure_time:".$data["departure_time"].";social_close_time".$data["social_close_time"].";prident_close_time:".$data["prident_close_time"].")";
				$one["hrid"]    = $v;
				$one["uid"]     = UID;
				$one["type_id"] = 500;//办理离职操作
				$row            = $this->hrManageSer->addHRLogData($one);
			}
		}

		$this->ajaxReturn($result);
	}

	/**
	 * 转公司
	 * @return [type] [description]
	 */
	function turnNewCompany(){
		$ids                    = trim(I("ids"));
		$where["_string"]       = " id in ('{$ids}')";
		$data["new_company_id"] = I("new_company_id");
		$data["new_depart"]     = I("new_depart");
		$data["is_reset_cage"]  = I("is_reset_cage");
		$this->turnNewCompanyWriteLog($ids,$_POST);
		$row                    = $this->hrManageSer->saveHRData($where,$data);
		$result                 = array("msg"=>"办理成功","status"=>200);
		$this->ajaxReturn($result);
	}

	/**
	 * 转新公司记录日志
	 * @return [type] [description]
	 */
	function turnNewCompanyWriteLog($ids,$post){
		$sql = "SELECT 
					h.`entry_salary`,
					h.turn_salary,
					h.per_radio,
					h.per_pay,
					h.basic_pay,
					h.job_salary,
					u.real_name,
					h.`user_name`,h.company_id,h.`depart_id`,h.id
				FROM
				  `boss_hr_manage` AS h 
				  LEFT JOIN `boss_user` AS u 
				    ON h.`creat_uid` = u.`id` 
				WHERE h.id in ({$ids}) ";
		$model = new \Think\Model();
		$old_list   = $model->query($sql);
		
		$message     = array();
		$update_time = date("Y-m-d H:i:s",time());
		$userSer     = new Service\UserService();
		$userObj     = $userSer->getOneByWhere(array("id"=>UID),"real_name");
		foreach ($old_list as $k => $v) {
			$old      = $v;
			$message  = array();
			//职位变动
			if((!empty($post["new_company_id"])) && ($old["new_company_id"]!=$post["new_company_id"])){
				$companys       = C('OPTION.company_list');
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."入职新公司：".$companys[$old["new_company_id"]]."=>".$companys[$post["new_company_id"]];
				$str["type_id"] = 10;
				$message[]      = $str;
			}
			if((!empty($post["new_depart"])) && ($old["new_depart"]!=$post["new_depart"])){
				$new_departList = $this->makeNewDepartList($post["new_depart"],$old["new_depart"]);
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."入职新部门：".$new_departList[$old["new_depart"]]["name"]."=>".$new_departList[$post["new_depart"]]["name"];
				$str["type_id"] = 11;
				$message[]      = $str;
			}

			$str["msg"]     = $userObj["real_name"]."在".$update_time."办理员工id为(".$v["id"].")的".$old["user_name"]."转公司操作";;
			$str["type_id"] = 501;//办理离职操作
			$message[]      = $str;

			//保存日志
			if($message){
				foreach ($message as $km => $vm) {
					$one            = array();
					$one["data"]    = $vm["msg"];
					$one["hrid"]    = $v["id"];
					$one["uid"]     = UID;
					$one["type_id"] = $vm["type_id"];
					$row = $this->hrManageSer->addHRLogData($one);
				}
			}
		}
	}

	/**
	 * HR 提醒
	 * @return [type] [description]
	 */
	function remindHR(){
		$model = \Think\Model();
		//前一个月入职
		$rz_count = "SELECT 
					  COUNT(1) as num
					FROM
					  `boss_hr_manage` 
					WHERE entry_time >= CURRENT_TIMESTAMP - INTERVAL 1 MONTH ";
		$rz_count = $model->query($rz_count);
		$rz_count = $rz_count[0]["num"];

		//前一个月离职
		$lz_count = "SELECT 
					  COUNT(1) as num
					FROM
					  `boss_hr_manage` 
					WHERE departure_time >= CURRENT_TIMESTAMP - INTERVAL 1 MONTH ";
		$lz_count = $model->query($lz_count);
		$lz_count = $lz_count[0]["num"];

		//前一个月转公司
		$zgs_count = "SELECT 
					  COUNT(1) as num
					FROM
					  `boss_hr_manage` 
					WHERE update_com_time >= CURRENT_TIMESTAMP - INTERVAL 1 MONTH ";
		$zgs_count = $model->query($zgs_count);
		$zgs_count = $zgs_count[0]["num"];
	}

	/**
	 * 导出在职人员
	 * @return [type] [description]
	 */
	function exportIncumbent(){
		$type = I("type"); //on-在职，out-离职
		$where_  = " h.status <>1 and h.is_delete=0";
		if($type=="out"){
			$where_  = " h.status = 1 and h.is_delete=0";
		}
		$fields_ = "h.*,d.name as sec_depart_name,d.heads,d.pid";
		$order_  = "h.`dateline` desc";
		$sql = "SELECT 
				  {$fields_}
				FROM
				  `boss_hr_manage` AS h 
				  LEFT JOIN `boss_user_department` AS d 
				    ON h.`depart_id` = d.`id` 
				WHERE {$where_} 
				order by {$order_}";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){$this->success("暂无数据");exit;}
		$companys           = C('OPTION.company_list');
		$first_depart_id    = 0;
		$depart_count       = 0;
		$departSer          = new Service\DepartSettingService();
		$depart_parent_name = "";
		foreach ($list as $k => $v) {
			$list[$k]["company_id_name"] = $companys[$v['company_id']];

			//各部门人数
			if($first_depart_id!=$v["depart_id"]){
				$where["depart_id"] = $v["depart_id"];
				$depart_count = $this->hrManageSer->getHRListCountByWhere($where);
				$first_depart_id = $v["depart_id"];

				//部门及部门负责人
				$where_depart["id"] = $v["pid"];
				$fields_ = "name,heads";
				$departOne = $departSer->getOneByWhere($where_depart,$fields_);
				$depart_parent_name = $departOne["name"]."/".$departOne["heads"];
			}else{
				$first_depart_id = $v["depart_id"];
			}
			$list[$k]["depart_id_count"]           = $depart_count;
			$list[$k]["depart_parent_name_header"] = $depart_parent_name;
			$list[$k]["sex"]                       = $v["sex"] == 0?"男":"女";
			$list[$k]["is_marry"]                  = $v["is_marry"]==0?"未婚":"已婚";
			$list[$k]["body_no"]                   = "	".$v["body_no"];

			//个税 ??
			$list[$k]["ge_shui"] = 2000;
			//工资总额 ??
			$list[$k]["total_salary"] = $v["basic_pay"]+$v["job_salary"];
			//绩效工资 ??
			$list[$k]["jixiao_salary"] = $list[$k]["total_salary"]*$v["per_radio"];

			//薪水调整日志
			$where_tz["hrid"] = $v["id"];
			$where_tz["type_id"] = array("in","1,2,3,4,5,6,13,14,15");
			$logs = $this->hrManageSer->getHrLogListByWhere($where_tz,"data","id desc");
			$logs_str = "";
			if($logs){
				foreach ($logs as $kl => $vl) {
					$logs_str .=$vl["data"]."\n";
				}
			}
			$list[$k]["salary_log"] = $logs_str;
		}

		//导出标题
		$title = array(
				'id'                        =>'序号',
				'company_id_name'           =>'归属公司',
				'start_insure'              =>'社保参保时间',
				"depart_id_count"           =>"各部门人数",
				"depart_parent_name_header" =>"部门及部门负责人",
				'sec_depart_name'           =>'二级部门',
				'user_name'                 =>'姓名',
				'duty'                      =>'职务',
				'job_rating'                =>'岗位评级',
				'grade_rank'                =>'职等职级',
				'level'                     =>'层级',
				'job_no'                    =>'工号',
				'sex'                       =>'性别',
				"body_no"                   =>"身份证号",
				"birth"                     =>"出生年月",
				'age'                       =>'年龄',
				'start_work'                =>'首次工作时间（全职）',
				'is_marry'                  =>'婚姻',
				'height'                    =>'身高(cm)',
				'weight'                    =>'体重(kg)',
				'account_nature'            =>'户口性质',
				
				'nation'                    =>'民族',
				'passby'                    =>'籍贯',
				'adress'                    =>'联系地址',
				"emergen_user"              =>"紧急联系人",
				"relation_me"               =>"与本人关系",
				'emergen_phone'             =>'紧急联系人电话',
				'educate'                   =>'学历',
				'graduat_time'              =>'毕业时间',
				'graduat_school'            =>'毕业院校',
				'profession'                =>'专业',
				'phone'                     =>'联系电话',
				
				'back_phone'                =>'备用电话',
				'qq'                        =>'QQ',
				'entry_time'                =>'入职日期',
				"company_age"               =>"司龄",
				"proposed_time"             =>"拟转正日期",
				'real_proposed'             =>'实转正日期',
				'social_man'                =>'社保办理',
				'provident_start'           =>'公积金办理',
				'social_card'               =>'社保卡办理',
				'finger_no'                 =>'指纹编号',
				'per_radio'                 =>'绩效考核',
				
				'ge_shui'                   =>'个税',
				'total_salary'              =>'工资总额',
				'basic_pay'                 =>'基本工资',
				"job_salary"                =>"职务工资",
				"jixiao_salary"             =>"绩效工资",
				'salary_log'                =>'薪水调整',
				'first_visa_start'          =>'第一次签订合同开始时间',
				'fist_visa_end'             =>'第一次签订合同结束时间',
				'sec_visa_start'            =>'第二次签订合同开始时间',
				'sec_visa_end'              =>'第二次签订合同结束时间',
				'third_visa_start'          =>'第三次签订合同开始时间',
				
				'probihibe'                 =>'保密/竟业禁止',
				'bank_cardno1'              =>'银行卡号1',
				'bank_card1'                =>'银行卡1开户行',
				"bank_cardno2"              =>"银行卡号2",
				"bank_card2"                =>"银行卡2开户行",
				'sign_company'              =>'合同签订公司',
				'user_attr'                 =>'属性',
				'rec_channel'               =>'招聘渠道',
				'job_change'                =>'职务异动',
		);
		
		$file_name = '在职员工档案导出-';
		//离职
		if($type=="out"){
			$title["departure_time"] = "离职日期";
			$file_name = '离职员工档案导出-';
		}

		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, $file_name.date('YmdHis',time()));
	}

	/**
	 * 导入特殊工资
	 * @return [type] [description]
	 */
	function importEntrydo(){
		import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Reader.Excel5");
		import("Org.Util.PHPExcel.Reader.Excel2007");
		$upload = new \Think\Upload();// 实例化上传类
		//检查客户端上传文件参数设置
		$result = array("msg"=>"上传失败，请联系超级管理员","code"=>500,"data"=>"");
		//待删除的目录 除过当前天数
		//要保存的目录 今天
		//删除除过今天的所有目录
		for($t=1;$t<32;$t++){
			$day=$t;
			if($t<10)$day="0".$t;
			if($day!=date("d")){
				@deldir("./upload/excel/".$day."/");
			}
		}
		$upload->rootPath = "./upload/excel/".date("d")."/";//保存根路径
		$upload->savePath = $upload->rootPath;//保存根路径
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
					$this->ajaxReturn ($result);
					return ;
				}
			}
			
			$PHPExcel     = $PHPReader->load($filePath);
			$currentSheet = $PHPExcel->getSheet(0);
			// $allColumn    = $currentSheet->getHighestColumn();
			$allRow       = $currentSheet->getHighestRow();

			$excelData = array();
			//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			//从哪列开始，A表示第一列
				for ($currentColumn = 'A'; $currentColumn <= "Z"; $currentColumn++) {
					//数据坐标
					$address = $currentColumn . $currentRow;
					//读取到的数据，保存到数组$arr中
					$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
				}
			}
			if(count($excelData)==0){
				@unlink ($filePath);
				$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
				$this->ajaxReturn($result);
			}

			//转换为数组
			$result = $this->hrManageSer->doExcelToArray($excelData);
			//生成导入数据日志
			$url = $this->hrManageSer->writeLogs($result["data"],"/entry_".time().".txt");
			$logurl = ltrim($url,".");

			if($result["status"]){
				@unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logurl"=>$logurl);
				$this->ajaxReturn($result);
			}else{
				$objReader=PHPExcel_IOFactory::createReader("Excel5");
				$objExcel=$objReader->load($filePath);
				foreach($result["error"] as $v){
					$objExcel->getActiveSheet()->setCellValue('D'.$v[0], $v[1]);
				}
				foreach($result["success"] as $v){
					$objExcel->getActiveSheet()->setCellValue('D'.$v[0], '导入成功！');
				}
				$objWriter=PHPExcel_IOFactory::createWriter($objExcel,"Excel5");
				$objWriter->save($filePath);
				$result = array("msg"=>"导入失败","code"=>500,"data"=>$savename."(".$filePath.")","logurl"=>$logurl);
				$this->ajaxReturn($result);
			}
		}
	}

	/**
	 * 导出当月工资表
	 * @return [type] [description]
	 */
	function exportCurrentMonthSalaryData(){
		$month = date("Y年n",time());
		$month = !I("month")?$month:I("month");
		$sql = "select 
				  h.company_id,
				  d.name as depart_name,
				  h.`user_name`,
				  h.duty,
				  h.level,
				  h.entry_time,
				  h.start_work,
				  h.proposed_time,
				  h.real_proposed,
				  h.departure_time,
				  h.status,h.bank_cardno1,h.bank_card1
				  m.* 
				from
				  `boss_hr_monthsalary` as m 
				  left join `boss_hr_manage` as h 
				    on m.`hrid` = h.id 
			      LEFT JOIN `boss_user_department` AS d ON h.`depart_id` = d.`id` 
				where m.`salary_date` = {$month} ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){
			$this->success("暂时没有".$month."月的数据");exit;
		}
		//筛选数据
		$companys         = C('OPTION.company_list');
		$levels           = C("OPTION.level");
		$employee_statuss = C("OPTION.employee_status");
		foreach ($list as $k => $v) {
			$list[$k]["company_id_name"] = $companys[$v["company_id"]];
			$list[$k]["level"]           = $companys[$v["level"]];
			$list[$k]["gongling"]        = getYearDiff(strtotime($v["entry_time"]),time());
			//转正日期
			$zhuanzheng_date = $v["proposed_time"];
			if(strtotime($v["real_proposed"])>strtotime($v["proposed_time"])){
				$zhuanzheng_date = $v["real_proposed"];
			}
			$list[$k]["zhuanzheng_date"]  = $zhuanzheng_date;
			$list[$k]["dangyue_status"]   = $employee_statuss[$v["status"]];
			$list[$k]["gongzi_biaozhuan"] = $v["basic_pay"]+$v["job_salary"]+$v["per_pay"];//工资标准=基本工资+职务工资+绩效工资

			$list[$k]["fact_chuqin_days"] = 0;//实际出勤天数 --接口调取？？
			$list[$k]["attend_less"]      = 0;//考勤扣款 --接口调取？？
			$list[$k]["perfect_award"]    = 0;//全勤奖 --接口调取？？
			
			$list[$k]["total_benefy"]     = $v["age_allowance"]+$v["trasport_allowance"]+$v["commun_allowance"]+$list[$k]["perfect_award"]+$v["other_benefy"];//福利合计=工龄津贴+交通补助+通讯补助+全勤奖+其他福利；
			
			$list[$k]["factpay_wages"]    = $v["basic_pay"]+$v["job_salary"]+$v["per_pay"]+$v["quarte_bonus"]+$v["quarte_commi"]+$v["other_add"]+$list[$k]["total_benefy"];//应发工资合计=基本工资+职务工资+绩效工资+季度奖金+季度提成+其他(加款)+福利合计。

			//个税扣款 ??怎么计算
			$list[$k]["tax_deduc"]       = 0;
			//实发工资总额 ？？怎么计算
			$list[$k]["total_wages"]     = 0;
			
			$list[$k]["now_month_total"] = "";//本月付薪总计
		}

		$title = array(
			"id"                  =>"序号",
			"company_id_name"     =>"归属公司",
			"depart_name"         =>"部门",
			"user_name"           =>"姓名",
			"duty"                =>"职位",
			"level"               =>"职层",
			"entry_time"          =>"入职日期",
			"gongling"            =>"工龄",
			"depart_name"         =>"转正日期",
			"departure_time"      =>"离职日期",
			"dangyue_status"      =>"当月状态",
			"fact_chuqin_days"    =>"实际出勤天数",
			
			"gongzi_biaozhuan"    =>"工资标准",
			"basic_pay"           =>"基本工资",
			"job_salary"          =>"职务工资",
			"per_pay"             =>"绩效工资",
			"quarte_bonus"        =>"季度奖金",
			"quarte_commi"        =>"季度提成",
			"other_add"           =>"其他(加款)",
			"other_less"          =>"其他扣款",
			"attend_less"         =>"考勤扣款",
			"age_allowance"       =>"工龄津贴",
			"trasport_allowance"  =>"交通补助",
			"commun_allowance"    =>"通讯补助",
			
			"perfect_award"       =>"全勤奖",
			"other_benefy"        =>"其他福利",
			"total_benefy"        =>"福利合计",
			"factpay_wages"       =>"应发工资合计",
			"widthlold_social"    =>"代扣社保",
			"withhold_next"       =>"代扣下月社保",
			"withhold_profund"    =>"代扣公积金",
			"compay_ass_socsec"   =>"公司承担社保",
			"company_ass_profund" =>"公司承担公积金",
			"tax_incom"           =>"应纳税所得额",
			"tax_deduc"           =>"个税扣款",
			"fine_widhhold"       =>"罚款代扣",
			
			"other_item"          =>"其他项",
			"total_wages"         =>"实发工资总额",
			"now_month_total"     =>"本月付薪总计",
			"bank_cardno1"        =>"银行卡号",
			"bank_card1"          =>"银行卡开户行",
			"remark"              =>"备注",

		);
		$file_name = date("Y年m",time()).'月员工工资导出-';
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, $file_name.date('YmdHis',time()));

	}
}
?>