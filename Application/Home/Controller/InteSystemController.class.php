<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
* 情报体系
*/
class InteSystemController extends BaseController
{
	private $extendSer;
	function _initialize(){
		parent::_initialize();
		$this->extendSer = !$this->extendSer ? new Service\ExtendAdvService() : $this->extendSer;
	}


	/**
	 * 待拓展客户表--针对广告主
	 * @return [type] [description]
	 */
	function extendCustomer(){
		//初始化显示
		$showtablestr    ='11111110111111110001';
		$str             = "Home/InteSystem";
		$sess_cookie_str = $_SESSION['showtablestr'][$str];
		if($sess_cookie_str){
			$showtablestr = $sess_cookie_str["str"];
		}else{
			$_SESSION['showtablestr'][$str] = $showtablestr;
		}
		$this->assign("showtablestr",$showtablestr);

		$map["company_name"]  = trim(I("company_name"));
		// $map["bil_method"] = trim(I("bil_method"));
		$map["partner"]       = trim(I("partner"));
		$map["line_id"]       = trim(I("line_id"));
		$map["status"]        = trim(I("status"));
		$map["sjz_name"]      = trim(I("sjz_name"));
		$map["xq_type"] = trim(I("xq_type"));
 		$this->assign("map",$map);

		$this->isFaBuUser();//是否为发布者

		$where = " where 1=1 and e.is_del=0 and (ef.status <> 3 OR ef.status IS NULL)";
		if($map["company_name"]){
			$where .= " and e.company_name like '%".$map["company_name"]."%'";
		}
		if($map["partner"]){
			$where .= " and e.partner like '%".$map["partner"]."%'";
		}
		// if($map["line_id"]){
		// 	$where .= " and e.line_id=".$map["line_id"];
		// }
		if($map["status"]){
			$where .= " and ef.status=".$map["status"];
		}
		//收集者
		if($map["sjz_name"]){
			$where .= " and us.`real_name` like '%".$map["sjz_name"]."%'";
		}

		//需求类型
		if($map["xq_type"]){
			$where .= " and e.`demand_type`=".$map["xq_type"];
		}

		//判断当前用户是否为发布者，不是只读取发布者给当前用户派发的记录
		$has_auth = $_SESSION["sec_/Home/InteSystem/addExtendCustomer"];
		if($has_auth!=200){
			$user_depart_id = $_SESSION["userinfo"]["depart_id"];//一级部门id
			$user_depart_id = empty($user_depart_id)?"0":$user_depart_id;
			$where .= " and e.depart_id like '%,{$user_depart_id},%'";
		}

		$count    = $this->extendSer->getListCountByWhere($where,true);
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);
		$fields_  = "e.*, us.`real_name`,ef.status as f_status,ef.id as ef_id,ef.result as ef_result,ef.visit_time";
		$list     = $this->extendSer->getListByWhere($where,$fields_," order by e.dateline desc",$page->firstRow,$page->listRows,true);

		//第二次筛选
		$user_depart_id = $_SESSION["userinfo"]["sec_depart_id"];
		foreach ($list as $k => $v) {
			$depart_ids = explode(",", $v["depart_id"]);
			if(in_array($user_depart_id, $depart_ids)){
				$list[$k]["can_follow"] = true;
			}
		}
		$this->assign("list",$list);
		$this->assign("extendOptions",C('OPTION.extend_status'));
		$this->assign("charging_mode_options",C('OPTION.charging_mode'));
		$this->assign("demand_type_options",C('OPTION.demandType'));
		$this->assign("page",$page->show());
		$this->assign("cur_uid",UID);
		
		$this->display();
	}

	/**
	 * 是否为发布人
	 * @return boolean [description]
	 */
	function isFaBuUser(){
		//检查当前用户有关账权限 udpate 0707 tgd
		$url = "/Home/InteSystem/addExtendCustomer";
		$isHas_check = $_SESSION["sec_".$url];
		if(!$isHas_check){
			$isHas_check = isHasAuthToQuery($url,UID);
			$_SESSION["sec_".$url]  = $isHas_check;
		}
		$this->assign('isHas_auth',$isHas_check);
	}

	/**
	 * 添加拓展客户
	 */
	function addExtendCustomer(){
		$id = trim(I("id"));
		if($id){
			$exdInfo = $this->extendSer->getOneByWhere(array("id"=>$id));
			if($exdInfo["create_uid"]!=UID){
				$this->assign("query",200);
			}
			$this->assign("one",$exdInfo);
		}
		$this->assign("charging_mode_options",C('OPTION.charging_mode'));
		$this->assign("extend_status",C('OPTION.extend_status'));
		$this->assign("demand_type_options",C('OPTION.demandType'));
		$this->display();
	}

	

	/**
	 * 保存信息
	 * @return [type] [description]
	 */
	function saveInfo(){
		$extid                = I("extid");
		$result               = array("msg"=>"请稍后操作...","code"=>500);
		$data["company_name"] = trim(I("company_name"));
		$data["partner"]      = trim(I("partner"));
		$data["bil_method"]   = trim(I("bil_method"));
		$data["line_id"]      = trim(I("line_id"));
		$data["area"]         = trim(I("area"));
		$data["target"]       = trim(I("target"));
		$data["adver_advan"]  = trim(I("adver_advan"));
		$data["volume"]       = trim(I("volume"));
		$data["relevance"]    = trim(I("relevance"));
		$data["history_case"] = trim(I("history_case"));
		$data["depart_names"] = trim(I("depart_names"));
		$data["depart_id"]    = trim(I("depart_id"));
		$data["need_user"]    = trim(I("need_user"));
		$data["status"]       = 0;//默认待跟进
		$data["result"]       = trim(I("result"));
		$data["contact_way"]  = trim(I("contact_way"));
		$data["contact_user"] = trim(I("contact_user"));
		$data["remark"]       = trim(I("remark"));
		$data["demand_type"]  = trim(I("demand_type"));//需求类型
		
		if($extid){
			//修改信息
			$where["id"] = $extid;
			$row         = $this->extendSer->saveData($where,$data);
			unset($where);
			$result      = array("msg"=>"修改成功","code"=>200);
		}else{
			//添加信息
			$data["create_uid"]   = UID;
			$data["dateline"]     = date("Y-m-d H:i:s",time());
			$row = $this->extendSer->addData($data);
			if($row){
				$result = array("msg"=>"添加成功","code"=>200);
			}
		}
		unset($data);
		$this->ajaxReturn($result);
	}

	/**
	 * 保存跟进记录
	 * @return [type] [description]
	 */
	function saveFollow(){
		$result             = array("msg"=>"请稍后操作...","code"=>500);
		$data["type_id"]    = trim(I("type_id"));
		$data["visit_way"]  = trim(I("visit_way"));
		$data["result"]     = trim(I("result"));
		$data["status"]     = trim(I("status"));
		$data["remark"]     = trim(I("remark"));
		$data["expand_id"]  = trim(I("extid"));
		$vtime              = trim(I("visit_time"));
		$data["visit_time"] = empty($vtime)?date("Y-m-d H:i:s",time()):$vtime;
		$data["follow_uid"] = UID;
		$row                = $this->extendSer->addFollowData($data);
		if($row){
			$result = array("msg"=>"添加成功","code"=>200);

			//保存情报日志
			$log_               = array();
			$log_["uid"]        = UID;
			$log_["ctime"]      = date("Y-m-d H:i:s",time());
			$log_["content"]    = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"]."添加了任务跟进";

			//将当前子任务的父级id主任务Id赋值给日志中
			$log_["custome_id"] = $data["expand_id"];
	        $row = M("intel_log")->add($log_);
	        unset($log_);
		}
		unset($data);
		$this->ajaxReturn($result);
	}

	/**
	 * 记载业务线
	 * @return [type] [description]
	 */
	function loadBusinessLine(){
		$buSer = new Service\BusinessLineService();
		$list = $buSer->getListByWhere("1=1","id,name");
		$this->ajaxReturn($list);
	}

	/**
	 * 获取json
	 * @return [type] [description]
	 */
	function getJsonTrees(){
		$departSer = new Service\DepartSettingService();

		//指派部门：是业务中心的，如事业发展部、营销部、采购部,只需要一级部门 168,171,175,189,191,204,176,202,172,173,174,192,193
		//没有研发和后勤 、运营，技术部
		$where_    = "1=1 and type=0 and id in (168,171,172,173,174,175)";
		$list      = $departSer->getListByWhere($where_,"id,name,pid","sort desc");

		//编辑筛选哪些是选中的
		$eid = trim(I("eid"));
		if($eid){
			//获取当前已经选中的部门id
			$choseId = $this->extendSer->getOneByWhere(array("id"=>$eid),"depart_id");
			$choseId = explode(",", $choseId["depart_id"]);
			//筛选哪些是选中的
			foreach ($list as $k => $v) {
				if(in_array($v["id"], $choseId)){
					$list[$k]["checked"] = true;
				}
			}
		}
		
		$this->ajaxReturn($list);
	}

	/**
	 * 读取跟进记录
	 * @return [type] [description]
	 */
	function expand(){
		$extid = trim(I("extid"));
		$this->assign("extid",$extid);
		$this->assign("extendOptions",C('OPTION.extend_status'));

		//读取跟进记录
		if($extid){
			$type_id = trim(I("type_id"));
			$where    = " where e.expand_id={$extid} and e.type_id={$type_id}";
			$count    = $this->extendSer->getFollowListCountByWhere($where,true);
			$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
			$page     = new \Think\Page($count, $listRows);
			$fields_  = "e.*, us.`real_name`";
			$list     = $this->extendSer->getFollowListByWhere($where,$fields_," order by e.id desc",$page->firstRow,$page->listRows,true);
			
			$this->assign("type_id",$type_id);
			$this->assign("list",$list);
			$this->assign("page",$page->show());
		}
		$this->display();
	}	

	/**
	 * 已合作广告主列表
	 * @return [type] [description]
	 */
	function cooperAdver(){
		//初始化显示
		$showtablestr    ='11111111111';
		$str             = "Home/InteSystem";
		$sess_cookie_str = $_SESSION['showtablestr'][$str];
		if($sess_cookie_str){
			$showtablestr = $sess_cookie_str["str"];
		}else{
			$_SESSION['showtablestr'][$str] = $showtablestr;
		}
		$this->assign("showtablestr",$showtablestr);


		$map["ggz_name"]  = trim(I("ggz_name"));
		$map["diqu_name"] = trim(I("diqu_name"));
		// $map["ad_grade"]  = trim(I("ad_grade"));
		$map["hz_status"] = trim(I("hz_status"));
		$this->assign("map",$map);

		$where = " where 1=1";
		$where_count = " where 1=1";
		if($map["ggz_name"]){
			$where .= " and ad.name like '%".$map["ggz_name"]."%'";
			$where_count .= " and ad.name like '%".$map["ggz_name"]."%'";
		}
		//地域
		if($map["diqu_name"]){
			$where .= " and re.name like '%".$map["diqu_name"]."%'";
			$where_count .= " and re.name like '%".$map["diqu_name"]."%'";
		}

		// if($map["ad_grade"]){
		// 	$where .= " and ad.ad_grade='".$map["ad_grade"]."'";
		// }
		//合作状态
		if($map["hz_status"]){
			if($map["hz_status"]==1){
				//合作中
				$where .= " and p.`cooperate_state`<3";
			}else{
				//暂停中
				$where .= " and p.`cooperate_state`=3";
			}
		}


		//是否需要跟进
		$need_follow_adverids = trim(I("need_follow_adverids"));
		if($need_follow_adverids==200){
			$adver_ids = $_SESSION["need_follow_adverids"];
			if(I("adver_ids")){
				$adver_ids = trim(I("adver_ids"));
			}
			if($adver_ids){
				$where .= " and ad.id in ({$adver_ids})";
				$where_count .= " and ad.id in ({$adver_ids})";
				$_SESSION["need_follow_adverids"] = $adver_ids;
			}
		}
		$this->assign("need_follow_adverids",$need_follow_adverids);

		$intSer = new  \Home\Service\InteSystemService();
		$data = $intSer->getAllCooperAdver($where,"ORDER BY ad.add_time DESC",$where_count);
		// print_r($data);exit;
		$this->assign("list",$data["list"]);
		$this->assign("hz_status_list",C('OPTION.hz_status'));
		$this->assign("page",$data["page"]);

		//计算当前年份的年
		$prev_year = date("Y",time());
		$this->assign("prev_year",$prev_year);

		//判断是总监，经理，销售
		$type  = $this->getUserType();
		$this->display();
	}

	/**
	 * [判断是总监，经理，销售 description]
	 * @return [type] [description]
	 */
	private function getUserType(){
		$duty_name = $_SESSION["userinfo"]["duty_name"];
		$role_type = 0;
		if(strstr($duty_name, "总监")){
			$role_type = 1;
		}
		if(strstr($duty_name, "经理")){
			$role_type = 2;
		}
		if(strstr($duty_name, "销售")){
			$role_type = 3;
		}
		$this->assign("role_type",$role_type);
	}

	/**
	 * 若流水比前一天数据存在正负30%的波动则需要系统发起提醒功能--广告主
	 * @return [type] [description]
	 */
	function checkYestodayCompareToday(){
		$intSer = new  \Home\Service\InteSystemService();
		$data = $intSer->checkYestodayCompareTodaySer();
		$_SESSION["need_follow_adverids"] = $data["adver_ids"];
		$this->ajaxReturn($data);
	}

	/**
	 * [若流水比前一天数据存在正负30%的波动则需要系统发起提醒功能--供应商 description]
	 * @return [type] [description]
	 */
	function checkYestodayCompareTodaySupply(){
		$intSer = new  \Home\Service\InteSystemService();
		$data = $intSer->checkYestodayCompareTodaySupplySer();
		$_SESSION["need_follow_gys_ids"] = $data["gys_ids"];
		$this->ajaxReturn($data);
	}
	
	/**
	 * 已合作供应商
	 * @return [type] [description]
	 */
	function cooperSupply(){
		//初始化显示
		$showtablestr    ='1111111111111';
		$str             = "Home/InteSystem";
		$sess_cookie_str = $_SESSION['showtablestr'][$str];
		if($sess_cookie_str){
			$showtablestr = $sess_cookie_str["str"];
		}else{
			$_SESSION['showtablestr'][$str] = $showtablestr;
		}
		$this->assign("showtablestr",$showtablestr);


		$map["ggz_name"]      = trim(I("ggz_name"));
		$map["diqu_name"]     = trim(I("diqu_name"));
		$map["ad_grade"]      = trim(I("ad_grade"));
		$this->assign("map",$map);

		$where = " where 1=1";
		//供应商名称
		if($map["ggz_name"]){
			$where .= " and s.name like '%".$map["ggz_name"]."%'";
		}
		if($map["diqu_name"]){
			$where .= " and re.`name` like '%".$map["diqu_name"]."%'";
		}
		
		if($map["ad_grade"]){
			$where .= " and s.grade='".$map["ad_grade"]."'";
		}

		//是否需要跟进
		$need_follow_gys_ids = trim(I("need_follow_gys_ids"));
		if($need_follow_gys_ids==200){
			$gys_ids = $_SESSION["need_follow_gys_ids"];
			if(I("gys_ids")){
				$gys_ids = trim(I("gys_ids"));
			}
			if($gys_ids){
				$where .= " and s.id in ({$gys_ids})";
				$_SESSION["need_follow_gys_ids"] = $gys_ids;
			}
		}
		$this->assign("need_follow_gys_ids",$need_follow_gys_ids);

		$intSer = new  \Home\Service\InteSystemService();
		$data = $intSer->getAllCooperSupply($where);
		$this->assign("list",$data["list"]);
		$this->assign("page",$data["page"]);

		//计算当前年份前一年的年
		$prev_year = date("Y",time());
		$this->assign("prev_year",$prev_year);

		//判断是总监，经理，销售
		$type  = $this->getUserType();
		$this->display();
	}

	/**
	 * 加载公司人员
	 * @return [type] [description]
	 */
	function lazyUser(){
		$userSer = new Service\UserService();
		$list    = $userSer->getUserListByWhere(array("status"=>1,"id"=>array("neq",1)),"id,real_name");
		$this->ajaxReturn($list);
		unset($list);
	}

	/**
	 * 广告主详细--下方是明细，点击详情可查看一个广告主下的多个产品明细、流水、计费方式等等
	 * @return [type] [description]
	 */
	function cooperAdverDetail(){
		$adverid = trim(I("adverid"));

		$where = " where 1=1";
		if($adverid){
			$where .= " and p.ad_id={$adverid}";
		}
		$intSer = new  \Home\Service\InteSystemService();
		$data = $intSer->cooperAdverDetailSer($where);
		$this->assign("list",$data["list"]);
		$this->assign("extendOptions",C('OPTION.extend_status'));
		$this->assign("charging_mode_options",C('OPTION.charging_mode'));
		$this->assign("page",$data["page"]);


		//计算当前年份的年
		$prev_year = date("Y",time());
		$this->assign("prev_year",$prev_year);
		$this->display();
	}


	/**
	 * 供应商详细--下方是明细，点击详情可查看一个广告主下的多个产品明细、流水、计费方式等等
	 * @return [type] [description]
	 */
	function cooperSupplyDetail(){
		$supid = trim(I("supid"));

		$where = " where 1=1";
		if($supid){
			$where .= " and s.id={$supid}";
		}
		$intSer = new  \Home\Service\InteSystemService();
		$data = $intSer->cooperSupplyDetailSer($where);
		$this->assign("list",$data["list"]);
		$this->assign("extendOptions",C('OPTION.extend_status'));
		$this->assign("charging_mode_options",C('OPTION.charging_mode'));
		$this->assign("page",$data["page"]);

		//计算当前年份的年
		$prev_year = date("Y",time());
		$this->assign("prev_year",$prev_year);
		$this->display();
	}

	/**
	 * 广告主分析
	 * @return [type] [description]
	 */
	function adverIncome(){
		$adverid = trim(I("adverid"));
		$this->assign("adverid",$adverid);
		// $map["strtime"] = date("Y",time());
		// $map["strtime"] = $map["strtime"]."-01-01";
		$map["strtime_"] = date("Y-m-d",strtotime("-7 days"));
		$map["endtime_"] = date("Y-m-d",time());
		$this->assign("map",$map);

		$dates = array();
		//上月
		$pre_start = date("Y-m",strtotime("-1 month"));
        $days      = getMonthDays_com($pre_start);
        $dates["pre_end"]   = $pre_start."-".$days;
        $dates["pre_start"] = $pre_start."-01";
		
		//近30天
		$dates["pre_start_30"] = date('Y-m-d',time()-3600*24*30);//30天前
        $dates["pre_end_30"] = date('Y-m-d',time());

        //近7天
        $dates["pre_start_7"] = date("Y-m-d",strtotime("-7 days"));
        $dates["pre_end_7"] = date("Y-m-d",time());

		$this->assign("dates",$dates);
		
		$this->display();
	}
	/**
	 * 获取广告主收益
	 * @return [type] [description]
	 */
	function getAdverIncome(){
		// $list = array(
		// 	"date"=>array(
		// 		0=>'2017-08-04',
		// 		1=>'2017-08-05',
		// 		2=>'2017-08-06'
		// 		),
		// 	"fit"=>array(
		// 		0=>'10',
		// 		1=>'20',
		// 		2=>'30'
		// 		),
		// 	"in"=>array(
		// 		0=>'30',
		// 		1=>'60',
		// 		2=>'70'
		// 		),
		// 	"out"=>array(
		// 		0=>'20',
		// 		1=>'40',
		// 		2=>'40'
		// 		)
		// 	);
		$map['ggz_name'] = trim(I("ggz_name"));
		$map['strtime'] = trim(I("strtime"));
		$map['endtime'] = trim(I("endtime"));
		$map['adverid'] = trim(I("adverid"));
		$intSer = new  \Home\Service\InteSystemService();
		$list = $intSer->getAdverIncomeSer($map);
		$this->ajaxReturn($list);
	}

	/**
	 * 获取所有广告主
	 * @return [type] [description]
	 */
	function getAdverList(){
		$advSer = new Service\AdvertiserService();
		$list = $advSer->getListByWhere(array("status"=>1),"id,name");
		$this->ajaxReturn($list);
	}

	/**
	 * 供应商成本分析
	 * @return [type] [description]
	 */
	function supplyOut(){
		$gys_id = trim(I("gys_id"));
		$this->assign("gys_id",$gys_id);
		// $map["strtime"] = date("Y",time());
		// $map["strtime"] = $map["strtime"]."-01-01";
		$map["strtime"] = date("Y-m-d",strtotime("-7 days"));
		$map["endtime"] = date("Y-m-d",time());



		$this->assign("map",$map);

		$dates = array();
		//上月
		$pre_start = date("Y-m",strtotime("-1 month"));
        $days      = getMonthDays_com($pre_start);
        $dates["pre_end"]   = $pre_start."-".$days;
        $dates["pre_start"] = $pre_start."-01";
		
		//近30天
		$dates["pre_start_30"] = date('Y-m-d',time()-3600*24*30);//30天前
        $dates["pre_end_30"] = date('Y-m-d',time());

        //近7天
        $dates["pre_start_7"] = date("Y-m-d",strtotime("-7 days"));
        $dates["pre_end_7"] = date("Y-m-d",time());

		$this->assign("dates",$dates);
		
		$this->display();
	}

	/**
	 * 获取所有供应商
	 * @return [type] [description]
	 */
	function getSupplyList(){
		$suSer = new Service\SupplyService();
		$list = $suSer->getListByWhere(array("status"=>1),"id,name");
		$this->ajaxReturn($list);
	}

	/**
	 * 供应商分析
	 * @return [type] [description]
	 */
	function getSupplyOut(){
		// $list = array(
		// 	"date"=>array(
		// 		0=>'2017-08-04',
		// 		1=>'2017-08-05',
		// 		2=>'2017-08-06'
		// 		),
		// 	"fit"=>array(
		// 		0=>'10',
		// 		1=>'20',
		// 		2=>'30'
		// 		),
		// 	"in"=>array(
		// 		0=>'30',
		// 		1=>'60',
		// 		2=>'70'
		// 		),
		// 	"out"=>array(
		// 		0=>'20',
		// 		1=>'40',
		// 		2=>'40'
		// 		)
		// 	);
		$map['ggz_name'] = trim(I("ggz_name"));
		$map['strtime'] = trim(I("strtime"));
		$map['endtime'] = trim(I("endtime"));
		$map['gys_id'] = trim(I("gys_id"));
		$intSer = new  \Home\Service\InteSystemService();
		$list = $intSer->getSupplyOutSer($map);
		$this->ajaxReturn($list);
	}

	/**
	 * 导出广告主信息
	 * @return [type] [description]
	 */
	function exportAdverList(){
		$map["ggz_name"]  = trim(I("ggz_name"));
		$map["diqu_name"] = trim(I("diqu_name"));
		// $map["ad_grade"]  = trim(I("ad_grade"));
		$map["hz_status"] = trim(I("hz_status"));

		$where = " where 1=1";
		if($map["ggz_name"]){
			$where .= " and ad.name like '%".$map["ggz_name"]."%'";
		}
		//地域
		if($map["diqu_name"]){
			$where .= " and re.name like '%".$map["diqu_name"]."%'";
		}

		if($map["hz_status"]){
			if($map["hz_status"]==1){
				//合作中
				$where .= " and p.`cooperate_state`<3";
			}else{
				//暂停中
				$where .= " and p.`cooperate_state`=3";
			}
		}

		//是否需要跟进
		$need_follow_adverids = trim(I("need_follow_adverids"));
		if($need_follow_adverids==200){
			$adver_ids = $_SESSION["need_follow_adverids"];
			if(I("adver_ids")){
				$adver_ids = trim(I("adver_ids"));
			}
			if($adver_ids){
				$where .= " and ad.id in ({$adver_ids})";
				$_SESSION["need_follow_adverids"] = $adver_ids;
			}
		}

		$intSer = new  \Home\Service\InteSystemService();
		$data = $intSer->exportAdverListSer($where,"ORDER BY ad.add_time DESC");

		if(!$data["list"]){ $this->success("暂无已扩展广告主数据！"); }
		//导出excel
		$exSer = new \Home\Service\ExcelLogicService();
		$exSer->exportAdverListExcel($data["list"]);
		unset($data["list"]);
	}

	/**
	 * 导出已拓展供应商信息
	 * @return [type] [description]
	 */
	function exportSupplyList(){
		$map["ggz_name"]      = trim(I("ggz_name"));
		$map["diqu_name"]     = trim(I("diqu_name"));

		$where = " where 1=1";
		//供应商名称
		if($map["ggz_name"]){
			$where .= " and s.name like '%".$map["ggz_name"]."%'";
		}
		if($map["diqu_name"]){
			$where .= " and re.`name` like '%".$map["diqu_name"]."%'";
		}

		//是否需要跟进
		$need_follow_gys_ids = trim(I("need_follow_gys_ids"));
		if($need_follow_gys_ids==200){
			$gys_ids = $_SESSION["need_follow_gys_ids"];
			if(I("gys_ids")){
				$gys_ids = trim(I("gys_ids"));
			}
			if($gys_ids){
				$where .= " and s.id in ({$gys_ids})";
				$_SESSION["need_follow_gys_ids"] = $gys_ids;
			}
		}

		$intSer = new  \Home\Service\InteSystemService();
		$list = $intSer->exportSupplyListSer($where,"order by s.id desc");

		if(!$list){ $this->success("暂无已扩展供应商数据！"); }
		//导出excel
		$exSer = new \Home\Service\ExcelLogicService();
		$exSer->exportSupplyListExcel($list);
		unset($list);
	}

	/**
	 * 导出带拓展广告主信息
	 * @return [type] [description]
	 */
	function exportExtendAdverList(){
		$map["company_name"]  = trim(I("company_name"));
		$map["partner"]       = trim(I("partner"));
		$map["line_id"]       = trim(I("line_id"));
		$map["status"]        = trim(I("status"));
		$map["sjz_name"]      = trim(I("sjz_name"));
		$map["xq_type"] = trim(I("xq_type"));



		$where = " where 1=1 and e.is_del=0 and (ef.status <> 3 OR ef.status IS NULL)";
		if($map["company_name"]){
			$where .= " and e.company_name like '%".$map["company_name"]."%'";
		}
		if($map["partner"]){
			$where .= " and e.partner like '%".$map["partner"]."%'";
		}
		// if($map["line_id"]){
		// 	$where .= " and e.line_id=".$map["line_id"];
		// }
		if($map["status"]){
			$where .= " and e.status=".$map["status"];
		}
		//收集者
		if($map["sjz_name"]){
			$where .= " and us.`real_name` like '%".$map["sjz_name"]."%'";
		}

		//需求类型
		if($map["xq_type"]){
			$where .= " and e.`demand_type`=".$map["xq_type"];
		}

		$fields_  = "e.*, us.`real_name`,ef.status as f_status,ef.id as ef_id,ef.result as ef_result";
		$list     = $this->extendSer->getListByWhere($where,$fields_," order by e.dateline desc","0","999999999",true);

		if(!$list){ $this->success("暂无待扩展广告主数据！"); }
		//导出excel
		$exSer = new \Home\Service\ExcelLogicService();
		$exSer->exportExtendAdverListToExcel($list);
		unset($list);
	}

	/**
	 * 导入带拓展广告主信息
	 * @return [type] [description]
	 */
	function importAdverInfo(){
		$imSer = new  \Home\Service\ImportDataService();
		$result = $imSer->importAdverInfoSer();
		$this->ajaxReturn($result);
	}

	/**
	 * 修改广告主优势
	 * @return [type] [description]
	 */
	function updateAdver(){
		$result               = array("msg"=>"请稍后操作...","code"=>500);
		$extid                = I("id");
		$data["adver_advan"]  = trim(I("val"));

		//修改信息
		$where["id"] = $extid;
		$row         = $this->extendSer->saveData($where,$data);
		unset($where);
		$result      = array("msg"=>"修改成功","code"=>200);
	
		unset($data);
		$this->ajaxReturn($result);
	}

	function updateRemark(){
		$result               = array("msg"=>"请稍后操作...","code"=>500);
		$extid                = I("id");
		$data["remark"]  = trim(I("val"));

		//修改信息
		$where["id"] = $extid;
		$row         = $this->extendSer->saveData($where,$data);
		unset($where);
		$result      = array("msg"=>"修改成功","code"=>200);
	
		unset($data);
		$this->ajaxReturn($result);
	}

	/**
	 * 加载产品收入成本，利润，量级，日期
	 * @return [type] [description]
	 */
	function lazyProData(){
		$map['ggz_name'] = trim(I("ggz_name"));
		$map['strtime'] = trim(I("strtime"));
		$map['endtime'] = trim(I("endtime"));
		$map['adverid'] = trim(I("adverid"));
		$intSer = new  \Home\Service\InteSystemService();
		$list = $intSer->lazyProDataSer($map);
		$this->ajaxReturn($list);
	}

	/**
	 * 加载供应商 产品收入成本，利润，量级，日期
	 * @return [type] [description]
	 */
	function lazyGysProData(){
		$map['ggz_name'] = trim(I("ggz_name"));
		$map['strtime']  = trim(I("strtime"));
		$map['endtime']  = trim(I("endtime"));
		$map['gys_id']   = trim(I("gys_id"));
		$intSer = new  \Home\Service\InteSystemService();
		$list = $intSer->lazyGysProData($map);
		$this->ajaxReturn($list);
	}
	
}
?>