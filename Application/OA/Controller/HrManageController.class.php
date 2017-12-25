<?php
/**
* 人事管理
*/
namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class HrManageController extends BaseController
{
	private $hrManageSer;
	private $isFuli_user = false;//是否为福利专员
	function _initialize(){
		parent::_initialize();
		$this->hrManageSer = !$this->hrManageSer ? new Service\HrManageService() : $this->hrManageSer;

		//判断是否为人事专员
		$this->isPer();
	}

	/**
	 * 是否为人事专员可以查看薪酬，现目前只有wenlang账号能查看
	 * @return boolean [description]
	 */
	function isPer(){

		$isHas_check = $_SESSION["sec_/Home/HrManage/isFuli_user"];
		if(!$isHas_check){
			$isHas_check = isHasAuthToQuery("/Home/HrManage/isFuli_user",UID);
			$_SESSION["sec_/Home/HrManage/isFuli_user"]  = $isHas_check;
		}
		
		if($isHas_check==200){
			$this->isFuli_user = true;//是否为福利专员
		}
		$this->assign("isPer",$this->isFuli_user);
	}

	/**
	 * list
	 * @return [type] [description]
	 */
	function hrList(){
		$map["user_name"]        = trim(I("user_name"));
		$map["username_pinyin"]  = trim(I("username_pinyin"));
		$map["entry_time_sdate"] = I("entry_time_sdate");
		$map["entry_time_edate"] = I("entry_time_edate");
		$map["company_id"] = I("company_id");
		$map["dep"] = I("dep");
		$map["sex"] = I("sex");
		$map["status"]           = is_numeric(I("status"))?I("status"):"-1";
		//条件筛选
		$where = "1=1 and h.is_delete=0 and h.body_no<>'admin'";
		if($map["user_name"]){
			$where .= " and h.user_name like '%".$map["user_name"]."%'";
		}
		if($map["username_pinyin"]){
			$where .= " and h.username_pinyin like '%".$map["username_pinyin"]."%'";
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
		if($map["company_id"]){
			$where .= " and h.company_id =".$map["company_id"];
		}
		if($map["dep"]){
			$where .= " and h.depart_id =".$map["dep"];
		}
		if($map["sex"]){
			if($map["sex"] == 2){
				$sex = 0;
			}else{
				$sex = 1;
			}
			$where .= " and h.sex =".$sex;
		}
		if(intval($map["status"])>=0){
			$where .= " and h.status=".$map["status"];
		}
		$this->assign("map",$map);
		$fields   = "h.id,h.company_id,h.start_insure,h.depart_name,h.user_name,p.name as duty,h.job_rating,h.sex,h.body_no,h.entry_time,h.bank_cardno1,h.bank_card1,h.entry_salary,h.turn_salary,h.status,d1.`name` as firstname,h.username_pinyin";
		$order    = "h.dateline desc";
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$count    = $this->hrManageSer->getHRListCountByWhere($where,true);
		$page     = new \Think\Page($count, $listRows);
		$list     = $this->hrManageSer->getHRListByWhere($where,$fields,$order,$page->firstRow,$page->listRows,true);
		$this->assign("page",$page->show());
		$this->assign("list",$list);
		//公司列表
		$this->assign("op_companyList",getCompanyList());
		$this->assign("userAttr",C("OPTION.userAttr"));
		$this->assign('dep',M('user_department')->field('id,name')->where("type=0")->select());
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
			//绩效
			if($one["performance"]){
				$perList = json_decode($one["performance"],true);
				$this->assign("perList",$perList);
			}
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

	//编辑
	function edit(){
		$id = trim($_REQUEST["editeId"]);
		if($id>0){
			//编辑数据
			$one = $this->hrManageSer->getOneHRByWhere(array("id"=>$id));
			//绩效
			if($one["performance"]){
				$perList = json_decode($one["performance"],true);
				$this->assign("perList",$perList);
			}
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

	//查看
	function detail(){

		$user_id = I('get.user_id');//人才储备库传过来的用户id user_id
		$id = trim($_REQUEST["editeId"]);
		if($user_id || $id){
			//查看数据
			if($user_id){
				$one = $this->hrManageSer->getOneHRByWhere(array("user_id"=>$user_id));
			}elseif($id){
				$one = $this->hrManageSer->getOneHRByWhere(array("id"=>$id));
			}

			//绩效
			if($one["performance"]){
				$perList = json_decode($one["performance"],true);
				$this->assign("perList",$perList);
			}
			$this->assign("one",$one);
		}
		$this->assign("is_marry",C('OPTION.is_marry'));
		$this->assign("level",C('OPTION.level'));
		$this->assign("educate",C('OPTION.educate'));
		$this->assign("sexOptions",C('OPTION.sexOptions'));
		$this->assign("accountNature",C('OPTION.accountNature'));
		$this->assign("userAttr",C('OPTION.userAttr'));

		$this->assign('company',M('user_department')->where('type=1')->getField('id,name'));
		$this->assign('dep',M('user_department')->where('type=0')->getField('id,name'));
		$this->assign('position',M("oa_position")->getField('id,name'));
		$this->display();
	}

	//添加到人才储备库
	function add_talent(){
		$user_id = I('get.user_id');
		$ml = M('talent_pool');
		if($user_id){

			//先判断用户是否添加到人才库中
			$res = $ml->field('id')->where("userid=".$user_id)->find();
			if(empty($res['id'])) {
				$data = M('oa_hr_manage')->field("user_name,sex,DATE_FORMAT(start_work,'%Y-%m-%d') as start_work,phone,qq,educate,company_age")->where("user_id=" . $user_id)->find();
				$addData = array();
				$addData['name'] = $data['user_name'];
				$addData['sex'] = $data['sex'];
				if ($data['educate'] == 4) {
					$educate = 2;
				} elseif ($data['educate'] == 5) {
					$educate = 3;
				} elseif ($data['educate'] >= 6) {
					$educate = 4;
				} elseif ($data['educate'] <= 3) {
					$educate = 1;
				}
				$addData['education'] = $educate;
				$addData['start_time'] = $data['start_work'];
				$addData['years'] = $data['company_age'];
				$addData['phone'] = $data['phone'];
				$addData['qq'] = $data['qq'];
				$addData['create_time'] = date('Y-m-d H:i:s', time());
				$addData['uid'] = UID;
				$addData['status'] = 1;
				$addData['userid'] = $user_id;
				$id = $ml->add($addData);
				if ($id === false) {
					$retMsg = $ml->getError();
				} else {
					$retMsg = '添加到人才储备库成功';
				}
				//编码
				$_map['id'] = $id;
				$_map['numbering'] = $this->generalCode($id);
				$ml->save($_map);
			}else{
				$retMsg = '该用户已添加到人才储备库中';
			}
		}
		$this->ajaxReturn(array('status'=>1,'msg'=>$retMsg));
	}

	function generalCode($id) {
		return 'RCK' . str_pad(intval($id), 7, 0, STR_PAD_LEFT);
	}

	/**
	 * 生成系统用户--添加
	 * @return [type] [description]
	 */
	function createSystemUser($params){
		//如果重现重名且在职，那么提示用户重名
		$userSer             = new Service\UserService();
		$where_["real_name"] = $params["user_name"];
		$where_["status"]    = 1;
		$where_["username"]  = $params["username_pinyin"];
		$one                 = $userSer->getOneByWhere($where_);
		$result = array("status"=>0,"msg"=>"创建用户失败,请联系管理员","data"=>array());


		if($one){
			$result = array(
				"status" =>200,
				"msg"    =>"ok",
				"data"   =>array(
						"employee_number" =>$one["employee_number"],
						"user_id"         =>$one["id"],
						"depart_id"       =>$one["dept_id"],
						"sex"             =>$one["sex"],
						"phone"           =>$one["mobile"],
						"qq"              =>$one["qq"],
						"address"         =>$one["address"],
						"nation"          =>$one["ethnic_group"],
						"profession"      =>$one["major"]
						),
				"getInfo"=>true
				);
		}
		
		if(!$one){

			$userModel              = D('user');
			$educateList            = C("OPTION.educate");
			$is_marryList           = C("OPTION.is_marry"); 
			$data["dept_id"]        =  $params["depart_id"];
			//一级部门id
			$data["leve_depart_id"] =  $params["leve_depart_id"];
			$data["position_id"]    =  16;
			$data["real_name"]      =  $params["user_name"];
			$data["username"]       =  $params["username_pinyin"];
			$data["password"]       =  boss_md5(123456, UC_AUTH_KEY);
			$data["reg_time"]       =  time();
			$data["gender"]         =  $params["sex"];
			$data["mobile"]         =  $params["phone"];
			$data["qq"]             =  $params["qq"];
			$data["education"]      =  $educateList[$params["educate"]];
			$data["ethnic_group"]   =  $params["nation"];
			$data["major"]          =  $params["profession"];
			$data["address"]        =  $params["adress"];
			$data["ismarried"]      =  $is_marryList[$params["is_marry"]];
			$data["status"]         =  1;
			$user_id                = $userSer->addData($data);

			//添加角色
			$data_1["uid"]      = $user_id;
			$data_1["group_id"] = 30;
			$row1 = M("auth_group_access")->add($data_1);
			
			//添加成功
			$where["id"]              = $user_id;
			$data_["employee_number"] = $userSer->generalOACode($user_id);
			$row                      = $userSer->saveUserData($where,$data_);
			unset($where);unset($data_);

			//新增时同步 user表中的uid(表示oa对应) --update 2017-07-06 10:35 tgd
			$user_info_      = M("user")->field("uid,id")->where(array("id"=>$user_id))->find();
			$employee_number = 0;
			if($user_info_){
				if(empty($user_info_["uid"])){
					$user_["uid"]    = $user_info_["id"];
					$employee_number = $user_["uid"];
					$userSer->saveUserData(array("id"=>$user_info_["id"]),$user_);
					unset($user_);
				}else{
					$employee_number = $user_info_["uid"];
				}
				unset($user_info_);
			}

			$result = array(
				"status" =>200,
				"msg"    =>"ok",
				"data"   =>array(
							"employee_number" =>$employee_number,
							"user_id"         =>$user_id
						)
				);
		}
		return $result;
	}

	/**
	 * 同步用户信息--修改
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	function _syncUserInfo($params){
		$userSer                = new Service\UserService();
		$educateList            = C("OPTION.educate");
		$is_marryList           = C("OPTION.is_marry"); 
		
		$data["dept_id"]        =  $params["depart_id"];
		$data["leve_depart_id"] =  $params["leve_depart_id"];
		$data["real_name"]      =  $params["user_name"];
		$data["username"]       =  $params["username_pinyin"];
		$data["gender"]         =  $params["sex"];
		$data["mobile"]         =  $params["phone"];
		$data["qq"]             =  $params["qq"];
		$data["education"]      =  $educateList[$params["educate"]];
		$data["ethnic_group"]   =  $params["nation"];
		$data["major"]          =  $params["profession"];
		$data["address"]        =  $params["passby"];
		$data["ismarried"]      =  $is_marryList[$params["is_marry"]];
		if($params["status"]==1){
			$data["status"]   =  0;
		}else{
			$data["status"]   =  1;
		}

		//检查是否修改部门和角色，同时修改用户表的data_updated，fun_updated
		$udata["uid"]       = $params["user_id"];
		$udata["depart_id"] = $params["depart_id"];
		$userSer->isNeedSetUserGNDataUpdateTime($udata);
		unset($udata);


		// print_r($data);exit;
		$row = $userSer->saveUserData(array("id"=>$params["user_id"]),$data);
	}

	/**
	 * 获取框架id
	 * @return [type] [description]
	 */
	private function getOrganFrameId($pid){
		$frame_id = 0;
		//organ_frame_id--组织框架id,如 168--业务中心，169--支撑中心，181--职能中心-邓欣，185--职能中心-罗红梅
		if($pid==168 || $pid==169 || $pid==181 || $pid==185){
			$frame_id = $pid;
		}
		unset($pid);
		return $frame_id;
	}


	/**
	 * 保存
	 * @return [type] [description]
	 */
	function saveEntry(){
		// print_r($_POST);exit;
		$result       = array("msg"=>"保存失败","code"=>500);
		$id           = I("hrid");

		//部门名称
		$depart_id    = trim(I("depart_id"));
		$depSer       = new Service\DepartSettingService();
		$depInfo      = $depSer->getOneByWhere(array("id"=>$depart_id),"name,pid");
		//保存数据
		$saveData = array();
		$saveData["company_id"]         = trim(I("company_id"));
		$saveData["job_rating"]         = trim(I("job_rating"));
		$saveData["adress"]             = trim(I("adress"));
		$saveData["start_insure"]       = !empty(I("start_insure"))?trim(I("start_insure")):'0000-00-00 00:00:00';
		$saveData["grade_rank"]         = trim(I("grade_rank"));
		$saveData["id_card_address"]    = trim(I("id_card_address"));
		$saveData["company_id"]         = trim(I("company_id"));
		$saveData["depart_id"]          = trim(I("depart_id"));
		$saveData["depart_name"]        = trim($depInfo["name"]);
		//更新一级部门id
		$leve_id                     = getLeveDepartId($saveData["depart_name"],$saveData["depart_id"],$depInfo["pid"]);
		$saveData["leve_depart_id"]  = $leve_id;

		//organ_frame_id--组织框架id,如 168--业务中心，169--支撑中心，181--职能中心-邓欣，185--职能中心-罗红梅
		$depInfo                    = $depSer->getOneByWhere(array("id"=>$leve_id),"pid");
		$organ_frame_id             = $this->getOrganFrameId($depInfo["pid"]);
		$saveData["organ_frame_id"] = $organ_frame_id;
		unset($organ_frame_id);

		$saveData["level"]           = trim(I("level"));
		
		$saveData["user_name"]       = trim(I("user_name"));

		//生成拼音
		// import("Org.Util.Pinyin");
		// $py          = new \Pinyin(); 

		// $py->convert($saveData["user_name"],"",$allWord,$firstWord);
		// $saveData["username_pinyin"] = $allWord[0];
		 $saveData["username_pinyin"] = trim(I("username_pinyin"));


		//邮箱--系统根据用户名生成
		$saveData["post_email"] = trim(I("post_email"));

		$saveData["emergen_user"]    = trim(I("emergen_user"));
		$saveData["relation_me"]     = trim(I("relation_me"));
		$saveData["phone"]           = trim(I("phone"));
		$saveData["back_phone"]      = trim(I("back_phone"));
		$saveData["duty"]            = trim(I("duty"));
		
		$saveData["emergen_phone"]   = trim(I("emergen_phone"));
		$saveData["post_level"]      = trim(I("post_level"));
		$saveData["start_work"]      = trim(I("start_work"));
		$saveData["educate"]         = trim(I("educate"));
		$saveData["sex"]             = trim(I("sex"));
		$saveData["is_marry"]        = trim(I("is_marry"));
		$saveData["graduat_time"]    = empty(I("graduat_time"))?"":trim(I("graduat_time"));

		//2017.12.06新增 owq
		$saveData["investigation"]      = trim(I("investigation"));
		$saveData["recent_job"]      = trim(I("recent_job"));
		$saveData["recent_work"]      = trim(I("recent_work"));
		$saveData["recent_operating"]      = trim(I("recent_operating"));
		$saveData["hobbies"]      = trim(I("hobbies"));
		$saveData["flower_name"]      = trim(I("flower_name"));
		$saveData["signature"]      = trim(I("signature"));

		$saveData["body_no"]         = trim(I("body_no"));
		if(empty($saveData["body_no"])){
			$result["msg"] = "身份证号不能为空";
			$this->ajaxReturn($result);
			exit;
		}
		//根据身份证号获取年龄和出生年月日
		$age                        = getAgeByBodyNo($saveData["body_no"]);
		$birth                      = substr($saveData["body_no"],6,8);
		$saveData["age"]            = $age;
		$saveData["birth"]          = $birth;
		
		$saveData["passby"]         = trim(I("passby"));
		$saveData["entry_time"]     = empty(I("entry_time"))?"":trim(I("entry_time"));
		$saveData["proposed_time"]  = empty(I("proposed_time"))?"":trim(I("proposed_time"));
		$saveData["real_proposed"]  = empty(I("real_proposed"))?"":trim(I("real_proposed"));
		$saveData["graduat_school"] = trim(I("graduat_school"));
		$saveData["weight"]         = trim(I("weight"));
		$saveData["height"]         = trim(I("height"));
		$saveData["profession"]     = trim(I("profession"));
		$saveData["account_nature"] = trim(I("account_nature"));
		$saveData["nation"]         = trim(I("nation"));
		$saveData["qq"]             = trim(I("qq"));

		//薪酬福利--只有福利薪酬专员能修改
		if($this->isFuli_user){
			$saveData["age_allowance"]      = trim(I("age_allowance"));
			$saveData["trasport_allowance"] = trim(I("trasport_allowance"));
			$saveData["commun_allowance"]   = trim(I("commun_allowance"));
			$saveData["entry_salary"]       = trim(I("entry_salary"));
			$saveData["turn_salary"]        = trim(I("turn_salary"));
			$saveData["per_radio"]          = trim(I("per_radio"));
			$saveData["basic_pay"]          = trim(I("basic_pay"));

			//绩效工资=工资总额*绩效考核比例
			$per_pay = $saveData["turn_salary"]*$saveData["per_radio"];
			$saveData["per_pay"]          = parseFloat2($per_pay);
			unset($per_pay);
			//工资总额-基本工资-绩效工资
			$job_salary = $saveData["turn_salary"]-$saveData["basic_pay"]-$saveData["per_pay"];
			$saveData["job_salary"]         = parseFloat2($job_salary);
			unset($job_salary);

			$saveData["try_start"]          = empty(I("try_start"))?"0000-00-00 00:00:00":trim(I("try_start"));
			$saveData["try_end"]            = empty(I("try_end"))?"0000-00-00 00:00:00":trim(I("try_end"));
			$saveData["social_card"]        = trim(I("social_card"));
			$saveData["provident_start"]    = trim(I("provident_start"));
			$saveData["social_man"]         = trim(I("social_man"));
			$saveData["first_visa_start"]   = empty(I("first_visa_start"))?"":trim(I("first_visa_start"));
			$saveData["fist_visa_end"]      = empty(I("fist_visa_end"))?"":trim(I("fist_visa_end"));
			$saveData["sec_visa_start"]     = empty(I("sec_visa_start"))?"":trim(I("sec_visa_start"));
			$saveData["sec_visa_end"]       = empty(I("sec_visa_end"))?"":trim(I("sec_visa_end"));
			$saveData["third_visa_start"]   = empty(I("third_visa_start"))?"0000-00-00 00:00:00":trim(I("third_visa_start"));
			// $saveData["third_visa_end"]     = trim(I("third_visa_end"));
			$saveData["bank_cardno1"]       = trim(I("bank_cardno1"));
			$saveData["bank_card1"]         = trim(I("bank_card1"));
			$saveData["bank_cardno2"]       = trim(I("bank_cardno2"));
			$saveData["bank_card2"]         = trim(I("bank_card2"));
		}

		//其他信息
		//司龄--系统计算，根据入职时间
		$entry_time = trim(I("entry_time"));
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
			$saveData["company_age"] = $diff_year.".".$diff_month;
		}

		$saveData["finger_no"]          = trim(I("finger_no"));
		$saveData["rec_channel"]        = trim(I("rec_channel"));
		$saveData["sign_company"]       = trim(I("sign_company"));
		$saveData["probihibe"]          = trim(I("probihibe"));
		$saveData["job_change"]         = trim(I("job_change"));
		$saveData["status"]             = trim(I("status"));

		if($this->isFuli_user){
			//绩效
			$perList = $_POST["jx_per"];
			$perArray = array();
			foreach ($perList as $k => $v) {
				if($v){
					array_push($perArray, $v);
				}
			}
			if($perArray){
				$saveData["performance"] = json_encode($perArray);
			}
		}
		//添加时后台重新计算当前员工的工龄补贴
		$result_fl                      = $this->getUserGongLingMoney1($saveData["level"],$saveData["company_age"]);
		$saveData["age_allowance"]      = $result_fl["gongling_money"];
		$saveData["trasport_allowance"] = $result_fl["jt_money"];
		$saveData["commun_allowance"]   = $result_fl["zx_money"];


		
		if($id>0){ //修改
			$this->writeHRLogs($id,$saveData,"SAVEENTRY");
			$where["id"]         = $id;
			$row                 = $this->hrManageSer->saveHRData($where,$saveData);
			$result["msg"]       = "修改成功";
			$result["code"]      = 200;
			//同步用户user表信息
			$userOne = $this->hrManageSer->getOneHRByWhere(array("id"=>$id),"user_id");
			if($userOne["user_id"]){
				$saveData["user_id"] = $userOne["user_id"];
				$this->_syncUserInfo($saveData);
				//实时同步更新员工oa账号
				$this->_synHrJobNo($userOne["user_id"]);
			}else{
				$result["msg"] .= ";用户同步数据失败，请管理员检查";
			}

			//添加异动信息
			$ctData = I('post.procontacts');
			if($ctData) {
				$cModel = M('transaction_records');
				foreach ($ctData as $key => $val) {
					if ($val['id'] > 0) {
						$data = array();
						$data['id'] = $val['id'];
						$data['hr_id'] = $id;
						$data['oa_number'] = $val['oa_number'];
						$data['type'] = $val['type'];
						$data['date'] = $val['date'];
						$data['remark'] = $val['remark'];
						$data['annex'] = $val['annex'];
						$data['annex_path'] = $val['annex_path'];
						$r = $cModel->save($data);
					} else { //添加
						$data = array();
						$data['hr_id'] = $id;
						$data['oa_number'] = $val['oa_number'];
						$data['type'] = $val['type'];
						$data['date'] = $val['date'];
						$data['remark'] = $val['remark'];
						$data['annex'] = $val['annex'];
						$data['annex_path'] = $val['annex_path'];
						$r = $cModel->add($data);
					}
				}

			}

			//添加奖惩记录
			$rewards = I('post.rewards');
			if($rewards) {
				$rModel = M('rewards_punishments');
				foreach ($rewards as $key => $val) {
					if ($val['id'] > 0) {
						$data = array();
						$data['id'] = $val['id'];
						$data['hr_id'] = $id;
						$data['title'] = $val['title'];
						$data['type'] = $val['type'];
						$data['date'] = $val['date'];
						$data['remark'] = $val['remark'];
						$data['annex'] = $val['annex'];
						$data['annex_path'] = $val['annex_path'];
						$r = $rModel->save($data);
					} else { //添加
						$data = array();
						$data['hr_id'] = $id;
						$data['title'] = $val['title'];
						$data['type'] = $val['type'];
						$data['date'] = $val['date'];
						$data['remark'] = $val['remark'];
						$data['annex'] = $val['annex'];
						$data['annex_path'] = $val['annex_path'];
						$r = $rModel->add($data);
					}
				}

			}

		}else{ 
			//添加新用户
			//判断拼音在user表中是否存在,存在就在拼音中加01
			// $userSer            = new Service\UserService();
			// $where              = array();
			// $where_["username"] = $saveData["username_pinyin"];
			// $one_               = $userSer->getOneByWhere($where_);
			// if($one_){
			// 	$rand = rand();
			// 	$saveData["username_pinyin"] .= "01";
			// }
			// unset($one_);
			// unset($userSer);

			//生成系统用户，返回对应用户id
			$system_user               = $this->createSystemUser($saveData);
			if($system_user["status"]==200){
				$saveData["job_no"]  = $system_user["data"]["employee_number"];
				$saveData["user_id"] = $system_user["data"]["user_id"];

				if($system_user["data"]["depart_id"]){
					$saveData["depart_id"]   = $system_user["data"]["depart_id"];
					$depInfo                 = $depSer->getOneByWhere(array("id"=>$saveData["depart_id"]),"name");
					$saveData["depart_name"] = trim($depInfo["name"]);
				}

				//获取原有user表信息,如果信息被覆盖，以后再确定
				// if($system_user["getInfo"]){
				// 	$saveData["sex"]        = $system_user["data"]["sex"];
				// 	$saveData["phone"]      = $system_user["data"]["phone"];
				// 	$saveData["qq"]         = $system_user["data"]["qq"];
				// 	$saveData["address"]    = $system_user["data"]["address"];
				// 	$saveData["nation"]     = $system_user["data"]["nation"];
				// 	$saveData["profession"] = $system_user["data"]["profession"];
				// }
			}else{
				$result["msg"] = $system_user["msg"];
				$this->ajaxReturn($result);exit;
			}

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
				$row                   = $this->hrManageSer->addHRData($saveData);
				$result["msg"]         = "生成成功";
				$result["code"]        = 200;
			}	
		}
		$this->ajaxReturn($result);
		
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

	/**
	 * 修改时同步 user表中的uid(表示oa对应) --update 2017-07-06 10:35 tgd
	 * @param  [type] $user_id [description]
	 * @param  [type] $hr_id   [description]
	 * @return [type]          [description]
	 */
	private function _synHrJobNo($user_id){
		//修改时同步 user表中的uid(表示oa对应) --update 2017-07-06 10:35 tgd
		$user_info_      = M("user")->field("uid,id")->where(array("id"=>$user_id))->find();
		if($user_info_){
			$data_["job_no"]   = $user_info_["uid"];
			$where_["user_id"] = $user_id;
			$row               = $this->hrManageSer->saveHRData($where_,$data_);
			unset($data_);unset($where_);unset($user_info_);
		}
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
					h.`user_name`,h.company_id,h.`depart_id`,h.age_allowance,h.trasport_allowance,h.commun_allowance,h.level,h.post_level,h.is_reset_cage,h.company_age
				FROM
				  `boss_oa_hr_manage` AS h 
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
			if($this->isFuli_user){
				//薪资变动
				if(($old["entry_salary"]-$post["entry_salary"]!=0)){
					$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."入职薪资：".$old["entry_salary"]."元=>".$post["entry_salary"]."元";
					$str["type_id"] = 1;
					$message[]      = $str;
				}
				if(($old["turn_salary"]-$post["turn_salary"]!=0)){
					$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."转正薪资：".$old["turn_salary"]."元=>".$post["turn_salary"]."元";
					$str["type_id"] = 2;
					$message[]      = $str;
				}
				if(($old["per_pay"]-$post["per_pay"]!=0)){
					$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."绩效工资：".$old["per_pay"]."元=>".$post["per_pay"]."元";
					$str["type_id"] = 3;
					$message[]      = $str;
				}
				if(($old["per_radio"]-$post["per_radio"]!=0)){
					$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."绩效考核比例：".$old["per_radio"]."=>".$post["per_radio"];
					$str["type_id"] = 4;
					$message[]      = $str;
				}
				if(($old["basic_pay"]-$post["basic_pay"]!=0)){
					$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."基本工资：".$old["basic_pay"]."元=>".$post["basic_pay"]."元";
					$str["type_id"] = 5;
					$message[]      = $str;
				}
				if(($old["job_salary"]-$post["job_salary"]!=0)){
					$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."职务工资：".$old["job_salary"]."元=>".$post["job_salary"]."元";
					$str["type_id"] = 6;
					$message[]      = $str;
				}
			}

			//职位变动
			if(($old["company_id"]!=$post["company_id"])){
				$companys = getCompanyList();
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."入职公司：".$companys[$old["company_id"]]."=>".$companys[$post["company_id"]];
				$str["type_id"] = 7;
				$message[]      = $str;
			}
			if(($old["depart_id"]!=$post["depart_id"])){
				//重组数据
				$new_departList = $this->makeNewDepartList($post["depart_id"],$old["depart_id"]);
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."部门：".$new_departList[$old["depart_id"]]["name"]."=>".$new_departList[$post["depart_id"]]["name"];
				$str["type_id"] = 8;
				$message[]      = $str;
			}

			//post_level-岗位层级
			if(($old["post_level"]!=$post["post_level"])){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."岗位层级：".$old["post_level"]."=>".$post["post_level"];
				$str["type_id"] = 9;
				$message[]      = $str;
			}

			//level-层级
			if(($old["level"]!=$post["level"])){
				$level_ops      = C("OPTION.level");
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."层级：".$level_ops[$old["level"]]."=>".$level_ops[$post["level"]];
				$str["type_id"] = 12;
				$message[]      = $str;
			}

			if($this->isFuli_user){
				//工龄津贴-age_allowance
				if(($old["age_allowance"]!=$post["age_allowance"])){
					$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."工龄津贴：".$old["age_allowance"]."=>".$post["age_allowance"];
					$str["type_id"] = 13;
					$message[]      = $str;
				}

				//交通补助-trasport_allowance
				if(($old["trasport_allowance"]!=$post["trasport_allowance"])){
					$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."交通补助：".$old["trasport_allowance"]."=>".$post["trasport_allowance"];
					$str["type_id"] = 14;
					$message[]      = $str;
				}

				//通讯补助-commun_allowance
				if(($old["commun_allowance"]!=$post["commun_allowance"])){
					$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."通讯补助：".$old["commun_allowance"]."=>".$post["commun_allowance"];
					$str["type_id"] = 15;
					$message[]      = $str;
				}
			}

			if(($old["company_age"]!=$post["company_age"])){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."重置".$old["user_name"]."的司龄：".$old["company_age"]."=>".$post["company_age"];
				$str["type_id"] = 503; //重置司龄
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
		$where           = " id in ({$ids})";
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

				//更新系统用户表
				$hrOne           = $this->hrManageSer->getOneHRByWhere(array("id"=>$v),"user_id");
				$data_["status"] = 0;
				$row             = $userSer->saveUserData(array("id"=>$hrOne["user_id"]),$data_);
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
		$where                  = "id in ({$ids})";
		$data["new_company_id"] = I("companyid");
		$data["new_depart"]     = I("depart_id");
		$data["is_reset_cage"]  = I("is_reset_cage");

		if($data["is_reset_cage"]==1) $data["company_age"] = 0;
		$this->turnNewCompanyWriteLog($ids,$data);

		//修改原来的depart_id,company_id
		$data["company_id"] = I("companyid");
		$data["depart_id"]  = I("depart_id");
		$row                = $this->hrManageSer->saveHRData($where,$data);
		$result             = array("msg"=>"办理成功","status"=>200);
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
					h.`user_name`,h.company_id,h.`depart_id`,h.id,h.is_reset_cage
				FROM
				  `boss_oa_hr_manage` AS h 
				  LEFT JOIN `boss_user` AS u 
				    ON h.`creat_uid` = u.`id` 
				WHERE h.id in ({$ids}) ";
		$model       = new \Think\Model();
		$old_list    = $model->query($sql);
		
		$message     = array();
		$update_time = date("Y-m-d H:i:s",time());
		$userSer     = new Service\UserService();
		$userObj     = $userSer->getOneByWhere(array("id"=>UID),"real_name");
		foreach ($old_list as $k => $v) {
			$old      = $v;
			$message  = array();
			//职位变动
			if(($old["company_id"] != $post["new_company_id"])){
				$companys       = getCompanyList();
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."入职新公司：".$companys[$old["company_id"]]."=>".$companys[$post["new_company_id"]];
				$str["type_id"] = 10;
				$message[]      = $str;
			}
			if(($old["depart_id"] != $post["new_depart"])){
				$new_departList = $this->makeNewDepartList($post["new_depart"],$old["depart_id"]);
				$str["msg"]     = $userObj["real_name"]."在".$update_time."调整".$old["user_name"]."入职新部门：".$new_departList[$old["depart_id"]]["name"]."=>".$new_departList[$post["new_depart"]]["name"];
				$str["type_id"] = 11;
				$message[]      = $str;
			}
			if(($old["is_reset_cage"] != $post["is_reset_cage"])){
				$str["msg"]     = $userObj["real_name"]."在".$update_time."重置".$old["user_name"]."的司龄";
				$str["type_id"] = 503; //重置司龄
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

			//更新系统用户表，部门id 
			$hrOne            = $this->hrManageSer->getOneHRByWhere(array("id"=>$v["id"]),"user_id");
			$data_["dept_id"] = $post["new_depart"];
			$row              = $userSer->saveUserData(array("id"=>$hrOne["user_id"]),$data_);
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
					  `boss_oa_hr_manage` 
					WHERE entry_time >= CURRENT_TIMESTAMP - INTERVAL 1 MONTH ";
		$rz_count = $model->query($rz_count);
		$rz_count = $rz_count[0]["num"];

		//前一个月离职
		$lz_count = "SELECT 
					  COUNT(1) as num
					FROM
					  `boss_oa_hr_manage` 
					WHERE departure_time >= CURRENT_TIMESTAMP - INTERVAL 1 MONTH ";
		$lz_count = $model->query($lz_count);
		$lz_count = $lz_count[0]["num"];

		//前一个月转公司
		$zgs_count = "SELECT 
					  COUNT(1) as num
					FROM
					  `boss_oa_hr_manage` 
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
		$where_  = " h.status <>1 and h.is_delete=0 and h.body_no<>'admin'";
		if($type=="out"){
			$where_  = " h.status = 1 and h.is_delete=0  and h.body_no<>'admin'";
		}
		$fields_ = "h.*,d1.name AS first_depart_name,d.name as sec_depart_name,d.heads,d.pid,p.name as duty_name";
		$order_  = "h.`company_id` desc";
		$sql = "SELECT 
				  {$fields_}
				FROM
				  `boss_oa_hr_manage` AS h 
				  LEFT JOIN `boss_user_department` AS d ON h.`depart_id` = d.`id`
				  LEFT JOIN `boss_user_department` AS d1 ON d1.id = d.pid 
			      left join boss_oa_position as p on p.id=h.duty 
				WHERE {$where_} 
				order by {$order_}";
		$model = new \Think\Model();
		$list = $model->query($sql);
		// print_r($sql);exit;
		if(!$list){$this->success("暂无数据");exit;}
		$companys                  = getCompanyList();
		$first_depart_id           = 0;
		$depart_count              = 0;
		$departSer                 = new Service\DepartSettingService();
		$depart_parent_header_name = "";
		$depart_first_name         = "";
		$depart_header             = "";
		$xueli_list                = C('OPTION.educate');
		$level_list = C('OPTION.level');
		foreach ($list as $k => $v) {
			$list[$k]["company_id_name"] = $companys[$v['company_id']];


			//各部门人数
			if($first_depart_id!=$v["depart_id"]){
				if($v["depart_id"]>0){
					$where["depart_id"] = $v["depart_id"];
					$depart_count = $this->hrManageSer->getHRListCountByWhere($where);
				}else{
					$depart_count = 0;
				}
				$first_depart_id = $v["depart_id"];

			}else{
				$first_depart_id = $v["depart_id"];
			}

			//判断一级和二级部门名称
			$depart_                     = $this->getFirstDepartName($v["first_depart_name"],$v["sec_depart_name"]);
			$list[$k]["sec_depart_name"] = $depart_["sec_depart_name"];
			$depart_parent_header_name   = $depart_["first_depart_name"]."/".$v["heads"];
			unset($depart_);

			//学历
			$list[$k]["educate"]              = $xueli_list[$v['educate']];

			//层级
			$list[$k]["level"] = $level_list[$v["level"]];

			$list[$k]["depart_id_count"]           = $depart_count;
			$list[$k]["depart_parent_name_header"] = $depart_parent_header_name;
			$list[$k]["sex"]                       = $v["sex"] == 0?"男":"女";
			$list[$k]["is_marry"]                  = $v["is_marry"]==0?"未婚":"已婚";
			$list[$k]["body_no"]                   = $v["body_no"]."\t";
			$list[$k]["emergen_phone"]             = $v["emergen_phone"]."\t";
			$list[$k]["phone"]                     = $v["phone"]."\t";
			$list[$k]["back_phone"]                = $v["back_phone"]."\t";
			$list[$k]["bank_cardno1"]              = $v["bank_cardno1"]."\t";
			$list[$k]["bank_cardno2"]              = $v["bank_cardno2"]."\t";

			//个税 ??
			$list[$k]["ge_shui"] = 0;
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
		$file_name = '在职员工档案导出-';
		//离职
		if($type=="out") $file_name = '离职员工档案导出-';
		$fname = $file_name.date('YmdHis',time());
		$exSer = new \OA\Service\ExcelLogicService();
		$exSer->explortHrEmployee($list,$fname,$fname,$type);
	}

	/**
	 * [getFirstDepartName description]
	 * @return [type] [description]
	 */
	private function getFirstDepartName($first_depart_name_,$sec_depart_name_){
		$deaprt_list = array("first_depart_name"=>"","sec_depart_name"=>"");
		if($sec_depart_name_=="事业发展部" || $sec_depart_name_=="财务部" || $sec_depart_name_=="风控部" || $sec_depart_name_=="人力行政部" || $sec_depart_name_=="品牌公关部"|| $sec_depart_name_=="总裁办"){
			$deaprt_list = array("first_depart_name"=>$sec_depart_name_,"sec_depart_name"=>"/");
		}else{
			$deaprt_list = array("first_depart_name"=>$first_depart_name_,"sec_depart_name"=>$sec_depart_name_);
		}
		return $deaprt_list;
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
				unlink ($filePath);
				$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
				$this->ajaxReturn($result);
			}


			//转换为数组
			$result = $this->hrManageSer->doExcelToArray($excelData);
			// print_r($result);exit;
			//生成导入数据日志
			// $url = $this->hrManageSer->writeLogs($result["data"],"/entry_".time().".html","html");
			// $logurl = ltrim($url,".");
			$logdata = implode(",", $result["data"]);
			$_SESSION["SHOWLOG_LIST"] = $logdata;
			if($result["status"]){
				unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logdata"=>$logdata);
				$this->ajaxReturn($result);
			}else{
				$result = array("msg"=>"导入失败","code"=>500,"data"=>$savename."(".$filePath.")","logdata"=>$logdata);
				$this->ajaxReturn($result);
			}
		}
	}

	/**
	 * 获取所有用户当月的考勤记录
	 * @param  [type] $list [description]
	 * @param  [type] $date [description]
	 * @return [type]       [description]
	 */
	function getUserAndDateTreeList($list,$date){
		if(!$list){ return false;}
		$user_ids = "";
		foreach ($list as $k => $v) {
			$user_ids .=$v["user_id"].",";
		}
		if($user_ids){
			$user_ids = substr($user_ids,0,strlen($user_ids)-1);
		}
		$where["date"]    = $date;
		$where["user_id"] = array("in",$user_ids);

		$attendList       = $this->hrManageSer->getUserAttendListByWhere($where,"user_id,date,full_att,attendance_cha,actual_att");
		$attendTreeList   = $this->hrManageSer->makeAttendTree($attendList);
		return $attendTreeList;
	}

	/**
	 * 导出当月工资表
	 * @return [type] [description]
	 */
	function exportCurrentMonthSalaryData(){
		//默认获取当月
		$month = date("Y-m",time());
		$month = empty(I("month"))?$month:trim(I("month"));
		$sql = "select 
				  h.company_id,
				  d.name as depart_name,
				  h.`user_name`,
				  p.name as duty,
				  h.level,
				  h.entry_time,
				  h.start_work,
				  h.proposed_time,
				  h.real_proposed,
				  h.departure_time,
				  h.status,h.bank_cardno1,h.bank_card1,h.user_id,h.id,h.entry_time,
				  h.commun_allowance as hr_commun_allowance,
				  h.trasport_allowance as hr_trasport_allowance,
				  h.age_allowance as hr_age_allowance,
				  m.* 
				from
				  `boss_oa_hr_monthsalary` as m 
				  left join `boss_oa_hr_manage` as h 
				    on m.`hrid` = h.id 
			      LEFT JOIN `boss_user_department` AS d ON h.`depart_id` = d.`id`
			      left join boss_oa_position as p on p.id=h.duty 
				where m.`salary_date` = '{$month}'";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){
			$this->success("暂时没有".$month."月的数据");exit;
		}
		//组装数据只获取date=>user_id的数组
		$attendList = $this->getUserAndDateTreeList($list,$month);

		//筛选数据
		$companys         = getCompanyList();
		$levels           = C("OPTION.level");
		$employee_statuss = C("OPTION.employee_status");
		$now_month_total  = 0;//本月付薪总计
		$nextrowIndex = 1;
		foreach ($list as $k => $v) {
			$list[$k]["company_id_name"]  = $companys[$v["company_id"]];
			$list[$k]["level"]            = $companys[$v["level"]];
			$list[$k]["work_age"]         = getYearDiff(strtotime($v["entry_time"]),time());
			//转正日期
			$zhuanzheng_date              = $v["proposed_time"];
			if(strtotime($v["real_proposed"])>strtotime($v["proposed_time"])){
				$zhuanzheng_date          = $v["real_proposed"];
			}
			$list[$k]["zhuanzheng_date"]  = $zhuanzheng_date;
			$list[$k]["dangyue_status"]   = $employee_statuss[$v["status"]];
			$list[$k]["gongzi_biaozhuan"] = $v["basic_pay"]+$v["job_salary"]+$v["per_pay"];//工资标准=基本工资+职务工资+绩效工资

			
			//传入一个日期例如：2017-05 ，对应人id:user_id（user表中）
			$attendInfo                   = $attendList[$month."=>".$v["user_id"]];
			$list[$k]["fact_chuqin_days"] = $attendInfo["actual_att"];//实际出勤天数 --接口调取？？
			$list[$k]["attend_less"]      = $attendInfo["attendance_cha"];//考勤扣款 --接口调取？？
			$list[$k]["perfect_award"]    = $attendInfo["full_att"];//全勤奖 --接口调取？？

			//交通补助根据考勤算   实际交通补助            =交通补助/应出勤天数（21.75）*实际出勤天数；
			$trasport_allowance             = $v["hr_trasport_allowance"]/21.75*$list[$k]["fact_chuqin_days"];
			// print_r($trasport_allowance);
			$list[$k]["trasport_allowance"] = parseFloat2($trasport_allowance);

			//如果当月是离职状态，工龄津贴需要考勤天数计算
			$age_allowance = $v["hr_age_allowance"];
			if($v["status"]==1){
			 	$age_allowance = $age_allowance/21.75*$fact_chuqin_days;
			}
			$age_allowance =  parseFloat2($age_allowance);
			$commun_allowance =  parseFloat2($commun_allowance);

			//通讯补助按实际出勤天数计算--判断是不是试用期的第一个月，只有试用期第一个月才这么计算
			$commun_allowance = $v["hr_commun_allowance"];
			$entry_time = date("Y-m",strtotime($v["entry_time"]));
			if($entry_time==$month){
				$commun_allowance = $commun_allowance/21.75*$fact_chuqin_days;
			}
			$list[$k]["commun_allowance"] = $commun_allowance;
			$list[$k]["age_allowance"] = $age_allowance;
			
			$list[$k]["total_benefy"]     = $age_allowance+$list[$k]["trasport_allowance"]+$commun_allowance+$list[$k]["perfect_award"]+$v["other_benefy"];//福利合计=工龄津贴+交通补助+通讯补助+全勤奖+其他福利；
			
			//应发工资合计                      =工资标准 + 季度奖金+季度提成+其他加款-其他扣款-考勤扣款+工龄津贴+交通补助+通讯补助+全勤奖+其他福利；
			$list[$k]["factpay_wages"]    = $list[$k]["gongzi_biaozhuan"]+$list[$k]["total_benefy"]+$v["quarte_bonus"]+$v["quarte_commi"]+$v["other_add"]-$v["other_less"]-$list[$k]["attend_less"];
			
			//而应纳税所得额                     =应发工资合计-代扣社保-下月社保代扣-代扣公积金-个税起征点（重庆趣玩的个税起征点为3900元）
			$tax_income_amount            = $list[$k]["factpay_wages"]-$v["widthlold_social"]-$v["withhold_next"]-$v["withhold_profund"];//应纳税金额=应发工资合计-代扣社保-下月社保代扣-代扣公积金
			$person_tax                   = getPersonalIncomeTax($tax_income_amount);//获取个税
			$list[$k]["tax_incom"]        = $tax_income_amount-3900;
			
			//个税扣款 ??怎么计算
			$list[$k]["tax_deduc"]        = $person_tax;
			//实发工资总额                      =应发工资合计-代扣社保-下月社保代扣-代扣公积金-个税扣款-罚款代扣+其他项。
			$list[$k]["total_wages"]      = $list[$k]["factpay_wages"]-$v["widthlold_social"]-$v["withhold_next"]-$v["withhold_profund"]-$person_tax-$v["fine_widhhold"]+$v["other_item"];
			
			$now_month_total              = $now_month_total + $list[$k]["total_wages"];//本月付薪总计
			
			$list[$k]["bank_cardno1"]     = is_numeric($v["bank_cardno1"]) ? $v["bank_cardno1"]."\t":$v["bank_cardno1"];

			$nextrowIndex++;

			//更新当月数据
			$saveData_["act_attendays"] =  $list[$k]["fact_chuqin_days"];//实际出勤天数
			$saveData_["attend_less"]   =  $list[$k]["attend_less"];//考勤扣款
			$saveData_["stand_salary"]  =  $list[$k]["gongzi_biaozhuan"];//工资标准=基本工资+职务工资+绩效工资
			$saveData_["perfect_award"] =  $list[$k]["perfect_award"];//全勤奖
			$saveData_["total_benefy"]  =  $list[$k]["total_benefy"];//福利合计
			$saveData_["factpay_wages"] =  $list[$k]["factpay_wages"];//应发工资合计
			$saveData_["tax_income"]    =  $tax_income_amount;//应纳税金额
			$saveData_["tax_threshold"] =  3900;//起征金额 默认值为3900
			$saveData_["tax_deduc"]     =  $person_tax;//个税
			$saveData_["total_wages"]   =  $list[$k]["total_wages"];//实发工资总额
			$row = $this->hrManageSer->saveHRMonthsalaryData(array("id"=>$v["id"]),$saveData_);

		}

		$list[$nextrowIndex]["other_item"] = "总计：";
		$list[$nextrowIndex]["total_wages"] = $now_month_total;

		$file_name = $month.'月员工工资导出-';
		// $fname = $file_name.date('YmdHis',time());
		$exSer = new \OA\Service\ExcelLogicService();
		$exSer->excelCurrentMonthSalaryData($list,$file_name,$file_name);

	}

	/**
	 * 检查是否重复user_name,username_pinyin,bodyno
	 * @return [type] [description]
	 */
	function checkIsRepeat(){
		$result             = array("status"=>200);//200可以写入，500存在重复
		//检查是否在已经在user表中添加数据
		$where["username_pinyin"] = trim(I("username_pinyin"));
		$one_              = $this->hrManageSer->getOneHRByWhere($where);
		if(!$one_ || empty($where["username"])){
			$result = array("status"=>503,"msg"=>"您需要先添加用户基本信息");
			$this->ajaxReturn($result);
		}
		//检查用户名
		$where = array();
		$user_name          = trim(I("user_name"));
		$where["user_name"] = $user_name;
		$one                = $this->hrManageSer->getOneHRByWhere($where,"id");
		
		if($one){
			$result = array("status"=>501,"msg"=>"系统已存在{$user_name},请在后面加数字");
			$this->ajaxReturn($result);
		}
		unset($one);
		$body_no          = trim(I("body_no"));
		$where            = array();
		$where["body_no"] = $body_no;
		$one              = $this->hrManageSer->getOneHRByWhere($where,"id");
		if($one){
			$result = array("status"=>502,"msg"=>"系统已存在{$user_name},请在后面加数字");
			$this->ajaxReturn($result);
		}

		$this->ajaxReturn($result);
	}

	/**
	 * 导入员工信息,仅支持csv文件
	 * @return [type] [description]
	 */
	function importHrInfo1(){
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
			$file                  = $info["files"];
			$savename ['savename'] = $filePath = $upload->rootPath .$file["savepath"]. $file ['savename'];
			$file_last             = substr(strrchr($filePath, '.'), 1);
			if(strtolower($file_last)==="csv"){
				$hrSer                 = new Service\HrManageService();
				$csvSer                = new Service\CsvService($filePath);
				$data                  = $csvSer->get_data();
				//将csv数据插入hrmanage表中
				$result = $hrSer->createHrData($data);
				if(count($result["data"])>0){
					$logdata = implode(",", $result["data"]);
					$result = array("msg"=>"导入成功","code"=>200,"logdata"=>$logdata);
				}else{
					$result = array("msg"=>"没有任何数据导入，请联系管理员检查");
				}
			}else{
				$result = array("msg"=>"上传文件仅支持csv格式，您需要将excel格式转换为csv格式");
			}
			//删除本地文件
			unlink($filePath);
			$this->ajaxReturn ($result);
		}
	}

	/**
	 * 导入员工信息
	 * @return [type] [description]
	 */
	function importHrInfo(){
		$imSer = new  \OA\Service\ImportHrService();
		$result = $imSer->importHrUser();
		$this->ajaxReturn($result);
	}

	/**
	 * 删除员工信息
	 * @return [type] [description]
	 */
	function deletehr(){
		$ids = I("ids");
		$where["id"] = array("in",$ids);
		$row = $this->officeCateSer->saveAppData($where,array("is_delete"=>1));
		$ret = array("code"=>500,"msg"=>"没有数据被更新，请检查");
		if($row) $ret = array("code"=>200,"msg"=>"删除成功");
		$this->ajaxReturn($ret);
	}

	/**
	 * 导出excel工资条]--只导出在职的员工，离职的人事单独截图给员工，不发邮件
	 * @return [type] [description]
	 */
	function exportMonthSalaryLine(){
		//默认获取当月
		$month = date("Y-m",time());
		$month = empty(I("month"))?$month:trim(I("month"));
		$sql = "select 
				  h.company_id,
				  d.name as depart_name,
				  h.`user_name`,
				  p.name as duty,
				  h.level,
				  h.entry_time,
				  h.start_work,
				  h.proposed_time,
				  h.real_proposed,
				  h.departure_time,
				  h.status,h.bank_cardno1,h.bank_card1,h.user_id,h.post_email,h.id,h.entry_time,
				  h.commun_allowance as hr_commun_allowance,
				  h.trasport_allowance as hr_trasport_allowance,
				  h.age_allowance as hr_age_allowance,
				  m.* 
				from
				  `boss_oa_hr_monthsalary` as m 
				  left join `boss_oa_hr_manage` as h 
				    on m.`hr_uid` = h.user_id 
			      LEFT JOIN `boss_user_department` AS d ON h.`depart_id` = d.`id`
			      left join boss_oa_position as p on p.id=h.duty 
				where m.`salary_date` = '{$month}'";
		// print_r($sql);exit;
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){
			$this->success("暂时没有".$month."月的数据");exit;
		}
		//组装数据只获取date=>user_id的数组
		$attendList = $this->getUserAndDateTreeList($list,$month);

		//筛选数据
		$companys         = getCompanyList();
		$levels           = C("OPTION.level");
		$employee_statuss = C("OPTION.employee_status");
		$now_month_total  = 0;//本月付薪总计
		$nextrowIndex = 1;
		foreach ($list as $k => $v) {
	
			// //传入一个日期例如：2017-05 ，对应人id:user_id（user表中）
			$attendInfo                   = $attendList[$month."=>".$v["user_id"]];
			$list[$k]["fact_chuqin_days"] = parseFloat2($attendInfo["actual_att"]);//实际出勤天数 --接口调取？？
			$fact_chuqin_days             =  parseFloat2($attendInfo["actual_att"]);//实际出勤天数 --接口调取？？
			$list[$k]["attend_less"]      = parseFloat2($attendInfo["attendance_cha"]);//考勤扣款 --接口调取？？
			$list[$k]["perfect_award"]    = parseFloat2($attendInfo["full_att"]);//全勤奖 --接口调取？？
			
			//交通补助根据考勤算   实际交通补助            =交通补助/应出勤天数（21.75）*实际出勤天数；
			$trasport_allowance             = $v["hr_trasport_allowance"]/21.75*$fact_chuqin_days;
			// print_r($trasport_allowance);
			$list[$k]["trasport_allowance"] = parseFloat2($trasport_allowance);

			//如果当月是离职状态，工龄津贴需要考勤天数计算
			$age_allowance = $v["hr_age_allowance"];
			if($v["status"]==1){
			 	$age_allowance = $age_allowance/21.75*$fact_chuqin_days;
			}
			$age_allowance = parseFloat2($age_allowance);
			$list[$k]["age_allowance"] = $age_allowance;

			//通讯补助按实际出勤天数计算--判断是不是试用期的第一个月，只有试用期第一个月才这么计算
			$commun_allowance = $v["hr_commun_allowance"];
			$entry_time = date("Y-m",strtotime($v["entry_time"]));
			if($entry_time==$month){
				$commun_allowance = $commun_allowance/21.75*$fact_chuqin_days;
			}
			$commun_allowance = parseFloat2($commun_allowance);
			$list[$k]["commun_allowance"] = $commun_allowance;

			$list[$k]["dangyue_status"]   = $employee_statuss[$v["status"]];



			
			$list[$k]["gongzi_biaozhuan"] = $v["basic_pay"]+$v["job_salary"]+$v["per_pay"];//工资标准=基本工资+职务工资+绩效工资
			$list[$k]["total_benefy"]     = $age_allowance+$list[$k]["trasport_allowance"]+$commun_allowance+$list[$k]["perfect_award"]+$v["other_benefy"];//福利合计=工龄津贴+交通补助+通讯补助+全勤奖+其他福利；
			
			$list[$k]["total_benefy"]     = parseFloat2($list[$k]["total_benefy"]);
			
			//应发工资合计                      =工资标准 + 季度奖金+季度提成+其他加款-其他扣款-考勤扣款+工龄津贴+交通补助+通讯补助+全勤奖+其他福利；
			$list[$k]["factpay_wages"]    = $list[$k]["gongzi_biaozhuan"]+$list[$k]["total_benefy"]+$v["quarte_bonus"]+$v["quarte_commi"]+$v["other_add"]-$v["other_less"]-$list[$k]["attend_less"];
			$list[$k]["factpay_wages"]    = parseFloat2($list[$k]["factpay_wages"]);
			
			//而应纳税所得额                     =应发工资合计-代扣社保-下月社保代扣-代扣公积金-个税起征点（重庆趣玩的个税起征点为3900元）
			$tax_income_amount            = $list[$k]["factpay_wages"]-$v["widthlold_social"]-$v["withhold_next"]-$v["withhold_profund"];//应纳税金额=应发工资合计-代扣社保-下月社保代扣-代扣公积金
			$person_tax                   = getPersonalIncomeTax($tax_income_amount);//获取个税
			$list[$k]["tax_incom"]        = $tax_income_amount-3900;
			$list[$k]["tax_incom"]        = parseFloat2($list[$k]["tax_incom"]);
			
			//个税扣款 ??怎么计算
			$list[$k]["tax_deduc"]        = $person_tax;
			$list[$k]["tax_deduc"]        = parseFloat2($list[$k]["tax_deduc"]);

			//实发工资总额                      =应发工资合计-代扣社保-下月社保代扣-代扣公积金-个税扣款-罚款代扣+其他项。
			$list[$k]["total_wages"]      = $list[$k]["factpay_wages"]-$v["widthlold_social"]-$v["withhold_next"]-$v["withhold_profund"]-$person_tax-$v["fine_widhhold"]+$v["other_item"];
			$list[$k]["total_wages"]        = parseFloat2($list[$k]["total_wages"]);
			
			$now_month_total              = $now_month_total + $list[$k]["total_wages"];//本月付薪总计
			
			// $list[$k]["bank_cardno1"]     = is_numeric($v["bank_cardno1"]) ? $v["bank_cardno1"]."\t":$v["bank_cardno1"];
			
			//工资条备注
			$salary_remark                = "1、实习生等临时用工发放实习补贴，不享受除了全勤奖以外的其他补贴和福利；顾问、兼职人员不享受公司所有福利和补贴。2、本月转正员工，转正薪资算法参照《试用期员工管理制度》（2016年5月1日版）进行核算。3、本月考勤扣款按照《考勤管理制度》（2017年3月1日版）中“第二章第二点”核算。4、本月工龄工资、交通补贴、通讯补贴按照《薪酬福利管理制度》（2017年6月1日版）核算。";
			$list[$k]["remark"]           = $salary_remark;

			$nextrowIndex++;
			$saveData_ = array();
			// //更新当月数据
			$saveData_["act_attendays"] =  $list[$k]["fact_chuqin_days"];//实际出勤天数

			//需要重新计算的
			$saveData_["trasport_allowance"] =  $list[$k]["trasport_allowance"];
			$saveData_["commun_allowance"]   =  $commun_allowance;
			$saveData_["age_allowance"]      =  $age_allowance;

			$saveData_["attend_less"]   =  $list[$k]["attend_less"];//考勤扣款
			$saveData_["stand_salary"]  =  $list[$k]["gongzi_biaozhuan"];//工资标准=基本工资+职务工资+绩效工资
			$saveData_["perfect_award"] =  $list[$k]["perfect_award"];//全勤奖
			$saveData_["total_benefy"]  =  $list[$k]["total_benefy"];//福利合计
			$saveData_["factpay_wages"] =  $list[$k]["factpay_wages"];//应发工资合计
			$saveData_["tax_income"]    =  $tax_income_amount;//应纳税金额
			$saveData_["tax_threshold"] =  3900;//起征金额 默认值为3900
			$saveData_["tax_deduc"]     =  $person_tax;//个税
			$saveData_["total_wages"]   =  $list[$k]["total_wages"];//实发工资总额
			$saveData_["remark"]        =  $list[$k]["remark"];//工资条备注
			$row = $this->hrManageSer->saveHRMonthsalaryData(array("id"=>$v["id"]),$saveData_);

		}

		$list[$nextrowIndex]["other_item"]  = "总计：";
		$list[$nextrowIndex]["total_wages"] = parseFloat2($now_month_total);

		
		$file_name = $month.'月在职员工工资条导出-';
		// $fname     = $file_name.date('YmdHis',time());
		$exSer     = new \OA\Service\ExcelLogicService();
		//导出工资条
		$exSer->excelCurrentMonthSalaryDataLine($list,$file_name,$file_name);
	}

	//查询异动记录
	function transaction_records_sel(){
		$id = I('get.id');
		$stepData = M('transaction_records')->field("*")->where("hr_id=".$id)->order('id')->select();
		$this->ajaxReturn(array('step_list'=>$stepData));
	}

	//查询奖惩记录
	function rewards_punishments(){
		$id = I('get.id');
		$stepData = M('rewards_punishments')->field("*")->where("hr_id=".$id)->order('id')->select();
		$this->ajaxReturn(array('step_list'=>$stepData));
	}

	//步骤附件上传
	public function hr_upload(){
		$dir = UPLOAD_OA_FILE_PATH;
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}
		$info = $this->uplaodfile("files",$dir);
		$file_path = $dir.$info["files"]["savepath"].$info["files"]["savename"];
		$file_path = ltrim($file_path,".");
		$list = array("msg"=>"上传失败","data"=>$file_path,"status"=>0,'name'=>$info["files"]["name"]);
		if($info){
			$list["msg"] = "上传成功";
			$list["status"] = 1;
		}
		$this->ajaxReturn($list);
	}

	public function uplaodfile($name,$dir){

		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize = 10000000000;// 设置附件上传大小
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg', 'xlsx', 'zip', 'rar', 'xls', 'pdf', 'txt', 'doc', 'docx');// 设置附件上传类型

		$upload->rootPath = $dir; // 设置附件上传根目录
		$upload->savePath = ''; // 设置附件上传（子）目录

		// 上传文件
		$upload->__set('saveName', time() . rand(100, 999));
		$info = $upload->upload();
		if (!$info) {// 上传错误提示错误信息
			return $upload->getError();
		} else {// 上传成功
			return $info;
		}

	}
}
?>