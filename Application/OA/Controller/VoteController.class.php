<?php
/**
* 投票管理
*/
namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
use Org\Util\PHPEXCEL;
class VoteController extends BaseController
{
	
	private $voteSer;
	function _initialize(){
		parent::_initialize();
		$this->voteSer = !$this->voteSer ? new Service\VoteService() : $this->voteSer;
	}

	/**
	 * 投票列表
	 * @return [type] [description]
	 */
	function voteList(){
		
		$vname   = I("vname");
		$where["status"] = array("neq",2);
		if($vname){
			$where["vname"] = $vname;
		}

		$map["vname"] = $vname;

		$user_name   = I("user_name");
		if($user_name){
			$userData = M('user')->field('id')->where("real_name like '%".$user_name."%'")->find();
			$where["uid"] = $userData['id'];
		}
		//查询时间
		$time_diff = I("time_diff");
		$time_diff_list = explode("~", $time_diff);
		$start_time        = trim($time_diff_list[0]);
		$end_time          = trim($time_diff_list[1]);
		if(!$start_time && !$end_time){
			$start_time = date("Y-m-d",strtotime("-1 month"));
			$end_time   = date("Y-m-d",time());
		}
		$start_time        .=" 00:00:00";
		$end_time          .=" 23:59:59";
		$map["start_time"] = $start_time;
		$map["end_time"]   = $end_time;
		$this->assign("map",$map);
		if($start_time && !$end_time){
			$where["dateline"] = array("EGT",$start_time);
		}
		if($end_time && !$start_time){
			$where["dateline"] = array("ELT",$end_time);
		}
		if(!empty($start_time) && !empty($end_time)){
			$where['_string'] = "dateline>='{$start_time}' AND dateline<='{$end_time}'";
		}

		$count    = $this->voteSer->getListCountByWhere($where);
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);
		$fields   = "id,vname,start_time,end_time,dateline,status,uid";
		$list     = $this->voteSer->getListByWhere($where,$fields,"dateline desc",$page->firstRow,$page->listRows,true);

		//第二次筛选
		$newList = $this->makeUserTreeList($list);
		foreach ($list as $k => $v) {
			//检查该投票是否过期
			if(strtotime($v["end_time"])<time()) {$list[$k]["status"] = 10;}
			$list[$k]["real_name"] = $newList[$v["uid"]];
		}
		$this->assign("cur_uid",UID);
		$this->assign("list",$list);
		$this->assign("page",$page->show());
		$this->display();
	}

	/**
	 * 组成user树形
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function makeUserTreeList($list){
		if(!$list) return false;
		$uids = "";
		foreach ($list as $k => $v) {
			$uids .=$v["uid"].",";
		}
		if($uids) $uids = substr($uids, 0,strlen($uids)-1);
		$userSer = new Service\UserService();
		$where   = "id in ({$uids})";
		$list    = $userSer->getUserListByWhere($where,"id,real_name");
		$newList = array();
		foreach ($list as $k => $v) {
			$newList[$v["id"]] = $v["real_name"];
		}
		return $newList;
	}

	/**
	 * 我参与的投票
	 * @return [type] [description]
	 */
	function myParticList(){

		$user = M("user")->field("dept_id")->where(array("id"=>UID))->find();
		if($user){
			$where        = " and v.end_time>='".date("Y-m-d H:i:s",time())."'";
			$vname        = I("vname");
			$map          = array();
			$map["vname"] = $vname;
			if($vname){
				$where .=" and v.vname like '%{$vname}%'";
			}
			$time_diff = I("time_diff");
			$time_diff_list = explode("~", $time_diff);
			$start_time        = trim($time_diff_list[0]);
			$end_time          = trim($time_diff_list[1]);
			if(!$start_time && !$end_time){
				$start_time = date("Y-m-d",strtotime("-1 month"));
				$end_time   = date("Y-m-d",time());
			}
			$start_time        .=" 00:00:00";
			$end_time          .=" 23:59:59";
			$map["start_time"] = $start_time;
			$map["end_time"]   = $end_time;
			if($start_time && !$end_time){
				$where .=" and v.dateline>='{$start_time}'";
			}
			if($end_time && !$start_time){
				$where .=" and v.dateline<='{$end_time}'";
			}
			if(!empty($start_time) && !empty($end_time)){
				$where .=" and v.dateline>='{$start_time}' and v.dateline<='{$end_time}'";
			}
			$this->assign("map",$map);

			$model = new \Think\Model();
			$sql_count  =  "SELECT 
				 count(1) as no
				FROM
				  `boss_oa_vote` AS v 
				  LEFT JOIN `boss_user` AS u 
				    ON u.id = v.uid 
				WHERE v.departs_ids LIKE '%,".$user["dept_id"].",%' and v.status=1 ".$where; 
			$count    = $model->query($sql_count);
			$count    = $count[0]["no"];
			$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
			$page     = new \Think\Page($count, $listRows);
			$limit    = " limit ".$page->firstRow.",".$page->listRows;
			$sql  =  "SELECT 
				  v.start_time,
				  v.end_time,
				  v.vname,
				  u.real_name,
				  v.id,
				  v.vote_uids,
				  v.dateline 
				FROM
				  `boss_oa_vote` AS v 
				  LEFT JOIN `boss_user` AS u 
				    ON u.id = v.uid 
				WHERE v.departs_ids LIKE '%,".$user["dept_id"].",%' and v.status=1 ".$where.$limit;
				// print_r($sql);exit;
			$list = $model->query($sql);
			if($list){
				//检查投票状态
				foreach ($list as $k => $v) {
					$canyu_list = explode(",", $v["vote_uids"]);
					if(in_array(UID, $canyu_list)){
						$list[$k]["is_canyu"] = "1";
					}
				}
			}
			$this->assign("list",$list);
			$this->assign("page",$page->show());
		}else{
			$this->assign("error","没查询到您的用户信息，请联系管理员");
		}
		$this->display();
	}

	/**
	 * 生成投票
	 * @return [type] [description]
	 */
	function createVote(){
		$this->display();
	}

	/**
	 * 保存投票
	 * @return [type] [description]
	 */
	function saveVotes(){
		//添加投票信息
		$data["uid"]         = UID;
		$data["start_time"]  = trim($_POST["start_time"]);
		$data["end_time"]    = trim($_POST["end_time"]);
		$data["vname"]       = trim($_POST["vname"]);
		$data["instruct"]    = trim($_POST["instruct"]);
		$data["attention"]   = trim($_POST["attention"]);
		$data["departs_ids"] = trim($_POST["departs_ids"]);
		$data["departs"]     = trim($_POST["departs"]);
		$data["dateline"]     = date("Y-m-d H:i:s",time());
		$row                = $this->voteSer->addData($data);
		$result             = array("msg"=>"出错了，请立马联系超级管理员！","status"=>500);

		if($row){
			//添加投票问题
			unset($data);
			$problems_title_list = $_POST["problems_title"];
			foreach ($problems_title_list as $k => $v) {
				if(!$v["titlename"]) continue;
				$data = array();
				$data["vid"]       = $row;
				$data["title"]     = trim($v["titlename"]);
				$data["type"]      = $_POST["problems_type_".$k][0];//投票类型，0-单选，1-多选
				$data["is_custom"] = $_POST["problems_is_custom_".$k][0];
				$data["vtype"]     = $_POST["item_type_".$k];//投票类型，0-文字，1-图片
				$data["dateline"]     = date("Y-m-d H:i:s",time());
				$row_problems_id = $this->voteSer->addVoteProblemsData($data);
				if($row_problems_id){
					//添加投票问题详细
					$item_list = $_POST["hidden_item_img_url_".$k];
					if($data["vtype"]==0){
						$item_list = $_POST["item_title_".$k];
					}
					if($data["is_custom"]==1){
						array_push($item_list,"其它");
					}
					foreach ($item_list as $ki => $vi) {
						if(!$vi) continue;
						$data_ = array();
						$data_["pro_id"] = $row_problems_id;
						if($data["vtype"]==0){
							$data_["title"] = trim($vi);
						}else{
							$data_["img_url"] = trim($vi);
						}
						$data_["dateline"]     = date("Y-m-d H:i:s",time());
						$row_ = $this->voteSer->addVoteProblemsDetailData($data_);
						if($row_){
							$result             = array("msg"=>"添加成功！","status"=>200);
						}else{
							$result["msg"] = "添加投票问题详细出错，请联系管理员";
						}
					}
				}else{
					$result["msg"] = "添加投票问题出错，请联系管理员";
				}
			}
		}else{
			$result["msg"] = "添加投票信息出错，请联系管理员";
		}
		$this->ajaxReturn($result);
	}


	/**
	 * 编辑
	 * @return [type] [description]
	 */
	function editVote(){
		$vid  = I("vid");
		$vote = $this->voteSer->getOneByWhere(array("id"=>$vid),"id,vname,start_time,end_time,instruct,attention,departs,departs_ids");
		//问题集合
		$where = array();
		$where["vid"] = $vid;
		$pro_list = $this->voteSer->getVoteProblemsListByWhere($where,"id,title,type,is_custom,vtype","id asc");
		//获取问题项
		$items   = $this->makeItems($pro_list);
		foreach ($pro_list as $k => $v) {
			$pro_list[$k]["items"] = $items[$v["id"]];
		}
		$this->assign("prolist",$pro_list);
		$this->assign("vote",$vote);
		$this->display();
	}

	/**
	 * 保存修改
	 * @return [type] [description]
	 */
	function saveEditeData(){
		$vid = $_POST["vid"];
		if(!$vid){
			$result["msg"] = "修改投票信息出错，请联系管理员01";
			$this->ajaxReturn($result);
			exit;
		}
		//修改投票信息
		$data["uid"]         = UID;
		$data["start_time"]  = trim($_POST["start_time"]);
		$data["end_time"]    = trim($_POST["end_time"]);
		$data["vname"]       = trim($_POST["vname"]);
		$data["instruct"]    = trim($_POST["instruct"]);
		$data["attention"]   = trim($_POST["attention"]);
		$data["departs_ids"] = trim($_POST["departs_ids"]);
		$data["departs"]     = trim($_POST["departs"]);
		$where["id"] = $vid;
		$row         = $this->voteSer->saveData($where,$data);
		$result      = array("msg"=>"出错了，请立马联系超级管理员！","status"=>500);
		//对问题和问题选项先删除之后再重新添加--修改
		$this->deleteOldVoteProblemsItems($vid);
		//添加投票问题
		$problems_title_list = $_POST["problems_title"];
		unset($data);
		foreach ($problems_title_list as $k => $v) {
			// print_r($v["titlename"]);
			// print_r($k);exit;
			if(!$v["titlename"]) continue;
			$data = array();
			$data["vid"]       = $vid;
			$data["title"]     = trim($v["titlename"]);
			$data["type"]      = $_POST["problems_type_".$k][0];//投票类型，0-单选，1-多选
			$data["is_custom"] = $_POST["problems_is_custom_".$k][0];
			$data["vtype"]     = $_POST["item_type_".$k];//投票类型，0-文字，1-图片
			$row_problems_id = $this->voteSer->addVoteProblemsData($data);
			if($row_problems_id){
				//添加投票问题详细
				$item_list = $_POST["hidden_item_img_url_".$k];
				if($data["vtype"]==0){
					$item_list = $_POST["item_title_".$k];
				}
				if($data["is_custom"]==1){
					array_push($item_list,"其它");
				}
				foreach ($item_list as $ki => $vi) {
					if(!$vi) continue;
					$data_           = array();
					$data_["pro_id"] = $row_problems_id;
					if($data["vtype"]==0){
						$data_["title"] = trim($vi);
					}else{
						$data_["img_url"] = $vi;
					}
					$row_ = $this->voteSer->addVoteProblemsDetailData($data_);
					if($row_){
						$result             = array("msg"=>"修改成功！","status"=>200);
					}else{
						$result["msg"] = "修改投票问题详细出错，请联系管理员";
					}
				}
			}else{
				$result["msg"] = "修改投票问题出错，请联系管理员";
			}
		}
		$this->ajaxReturn($result);
	}

	/**
	 * 删除原有的
	 * @param  [type] $vid [description]
	 * @return [type]      [description]
	 */
	function deleteOldVoteProblemsItems($vid){
		$list = $this->voteSer->getVoteProblemsListByWhere(array("vid"=>$vid),"id");
		if(!$list) return false;
		foreach ($list as $k => $v) {
			$row = $this->voteSer->deleteVoteProblemsData(array("id"=>$v["id"]));
			$row = $this->voteSer->deleteVoteProblemsDetailData(array("pro_id"=>$v["id"]));
		}
	}


	function test(){
			$_POST = array(
			"vname"=>6,
			"start_time" => "2017-05-02",
			"end_time" => "2017-05-11",
			"instruct" => 66,
			"attention" => 6,
			"departs" => 6,
			"problems_title"=>array(
				0 => "problems_title1",
		    	1 => "problems_title2",
		    	2 => "problems_title2",
		    	3 => "problems_title2",
				),
			"problems_type_1" => array(
				0=>0
				),
			"problems_type_3" => array(
				0=>2
				),
			"problems_type_4" => array(
				0=>2
				),
			"item_type_1"=>0,
			"item_title_1" =>array(
				0 => "item_title_1",
		    	1 => "item_title_1",
		    	2 => "item_title_1",
		    	3 => "item_title_1",
				),
			"hidden_item_img_url_1"=>array(),
			"problems_is_custom_1"=>array(
				0=>0
				),
			"problems_type_2"=>array(
				0=>0
				),
			"item_type_2"=>1,
			"item_title_2"=>array(
				0=>""
				),
			"hidden_item_img_url_2"=>array(
				0=>"/upload/charlog/2017-05-03/1493779134935.png",
				1=>"/upload/charlog/2017-05-03/1493779134935.png",
				2=>"/upload/charlog/2017-05-03/1493779134935.png"
				),
			"problems_is_custom_2"=>array(
				0=>0
				)
			);
	}

	/**
	 * 获取json
	 * @return [type] [description]
	 */
	function getJsonTrees(){
		$departSer = new Service\DepartSettingService();
		$list = $departSer->getListByWhere("1=1 and type=0","id,name,pid","sort desc");

		//编辑筛选哪些是选中的
		$eid = trim(I("eid"));
		if($eid){
			//获取当前已经选中的部门id
			$choseId = $this->voteSer->getOneByWhere(array("id"=>$eid),"departs_ids");
			$choseId = explode(",", $choseId["departs_ids"]);
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
	 * [去投票]
	 * @return [type] [description]
	 */
	function voteCon(){
		$voteId                = I("vid");
		//非法输入，自动跳转
		if(!$voteId){
			$this->redirect("/OA/Vote/myParticList");
		}
		//检查是否可以投票，当前人所在的部门是否在投票里面
		if($this->checkCanVote($voteId)==false){
			$this->success("你无法查看该投票结果信息！");exit;
		}

		//当前用户是否已经投票
		if($this->userHasVoted($voteId)){
			$this->success("你已经投过票了！");exit;
		}

		$vote                  = array();
		$vote_one              = $this->voteSer->getOneByWhere(array("id"=>$voteId),"id,vname,instruct,attention");
		$vote["vote"]          = $vote_one;
		$vote["problems_list"] = array();
		$where["vid"]          = $vote_one["id"];
		$list                  = $this->voteSer->getVoteProblemsListByWhere($where,"id,title,type,is_custom,vtype");
	
		//获取问题详细
		foreach ($list as $k => $v) {
			$item_list = $this->voteSer->getVoteProblemsDetailListByWhere(array("pro_id"=>$v["id"]),"title,img_url,id");
			$list[$k]["item_list"] = $item_list;
		}
		$vote["problems_list"] = $list;
		$this->assign("vote",$vote);
		$this->display();
	}

	/**
	 * 是否已经投票
	 * @return [type] [description]
	 */
	function userHasVoted($vid){
		$can  = false;
		$where["_string"] = "id={$vid} and vote_uids like '%,".UID.",%'";
		$one = $this->voteSer->getOneByWhere($where,"id");
		if($one) return true;
		return $can;
	}

	/**
	 * 检查是否可以投票
	 * @return [type] [description]
	 */
	function checkCanVote($vid){
		$user = M("user")->field("dept_id")->where(array("id"=>UID))->find();
		$can  = false;
		if($user["dept_id"]){
			$where["_string"] = "id={$vid} and departs_ids like '%,".$user["dept_id"].",%'";
 			$one = $this->voteSer->getOneByWhere($where,"id");
 			if($one) return true;
		}
		return $can;
	}

	/**
	 * 保存投票结果
	 * @return [type] [description]
	 */
	function saveVoteCon(){
		$problems_ids      = explode(",", $_POST["problems_ids"]);
		$vote_id           = I("vote_id");
		$v_one             = $this->voteSer->getOneByWhere(array("id"=>$vote_id),"total_no,vote_uids");
		//投票+1 ;将当前用户id加入已投名单中
		$data["total_no"]  = $v_one["total_no"]+1;
		if($v_one["vote_uids"]){
			$data["vote_uids"] = $v_one["vote_uids"].UID.",";
		}else{
			$data["vote_uids"] = ",".UID.",";
		}
		$data["vote_uids"] = $v_one["vote_uids"].",".UID.",";
		$vrow = $this->voteSer->saveData(array("id"=>$vote_id),$data);
		//为各个items统计加1
		foreach ($problems_ids as $k => $v) {
			$list = $_POST["problems_item_".$v];
			M("oa_vote_problems")->where(array("id"=>$v))->setInc("total_no",1);
			foreach ($list as $ka => $va) {
				M("oa_vote_problems_detail")->where(array("id"=>$va))->setInc("total_no",1);
			}
		}
		//检查是否文本框输入
		if($_POST["problems_textarea_"]){
			$problems_textarea_ = $_POST["problems_textarea_"];
			foreach ($problems_textarea_ as $k => $v) {
				$data_["pro_id"] = $v;
				$data_["title"] = trim($_POST["problems_textarea_".$v]);
				$row_ = $this->voteSer->addVoteProblemsDetailData($data_);
			}
		}
		$this->redirect("/OA/Vote/voteResult",array("vid"=>$vote_id));//查看统计结果
	}

	/**
	 * 投票结果
	 * @return [type] [description]
	 */
	function voteResult(){
		$vote_id = I("vid");
		//不是超级管理员无法直接查看结果
		$is_root = isSurperAdmin(UID);
		if($is_root != 1){
			if($this->checkVoteIsCurrentUser($vote_id)==false){
				$this->success("你无法查看该投票结果信息！");exit;
			}
		}
		
		$vote = $this->voteSer->getOneByWhere(array("id"=>$vote_id),"id,vname,total_no");
		if(!$vote){
			$this->success("暂无当前投票信息！");exit;
		}
		$list    = $this->voteSer->getVoteProblemsListByWhere(array("vid"=>$vote_id),"id,title,total_no,vtype,type");
		$items   = $this->makeItems($list);
		foreach ($list as $k => $v) {
			$list[$k]["items"] = $items[$v["id"]];
		}
		$this->assign("list",$list);
		$this->assign("vote",$vote);
		$this->assign("is_root",$is_root);
		$this->display();
	}

	/**
	 * 重组数组
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function makeItems($list){
		$problems_id  = "";
		foreach ($list as $k => $v) {
			if($v["id"]) $problems_id .= $v["id"].",";
		}
		if($problems_id) $problems_id = substr($problems_id, 0,strlen($problems_id)-1);
		if(empty($problems_id)) return false;
		$v_items = $this->voteSer->getVoteProblemsDetailListByWhere(array("pro_id"=>array("in",$problems_id))," pro_id,title,total_no,id,img_url","id asc");
		$items   = array();
		foreach ($v_items as $k => $v) {
			$items[$v["pro_id"]][] = $v;
		}
		return $items;
	}

	/**
	 * 删除投票
	 * @return [type] [description]
	 */
	function delVote(){
		$vid            = I("vid");
		$result         = array("msg"=>"删除失败","status"=>500);
		$data["status"] = "2";
		$row            = $this->voteSer->saveData(array("id"=>$vid),$data);
		if($row) $result = array("msg"=>"删除成功","status"=>200);
		$this->ajaxReturn($result);
	}

	/**
	 * 发布投票
	 * @return [type] [description]
	 */
	function releaseVote(){
		$vid            = I("vid");
		$result         = array("msg"=>"发布失败","status"=>500);
		$data["status"] = 1;
		$row = $this->voteSer->saveData(array("id"=>$vid),$data);
		if($row) $result = array("msg"=>"发布成功","status"=>200);
		$this->ajaxReturn($result);
	}
	/**
	 * 检查投票是否当前用户
	 * @return [type] [description]
	 */
	function checkVoteIsCurrentUser($vid){
		$uid              = UID;
		$where["_string"] = "vote_uids like '%,{$uid},%' and id={$vid}";
		$one              = $this->voteSer->getOneByWhere($where,"id");
		$is_find          = false;
		if($one) $is_find=true;
		return $is_find;
	}

	/**
	 * 导出统计结果excel
	 * @return [type] [description]
	 */
	function exportResults(){
		$vid = trim(I("vid"));
		if(!$vid){ $this->success("无效链接");exit; }
		$one          = $this->voteSer->getOneByWhere(array("id"=>$vid),"vname");
		$problemsList = $this->voteSer->getVoteProblemsListByWhere(array("vid"=>$vid));
		//获取问题项
		$items_list   = $this->makeItems($problemsList);
		$exportList   = array();
		$index = 0;
		foreach ($problemsList as $k => $v) {
			$explain_str                    = $v["type"]==2?"      【简答题，回答如下】":"";
			$exportList[$index]["title"]    = "问题".($k+1)."：".$v["title"].$explain_str;
			$exportList[$index]["total_no"] =  $v["type"]==2?"--":$v["total_no"];
			$index++;
			//添加其他项
			$itemList = $items_list[$v["id"]];
			foreach ($itemList as $ki => $vi) {
				$exportList[$index]["title"] = ($ki+1)."：".$vi["title"];
				$exportList[$index]["total_no"] =  $v["type"]==2?"--":$vi["total_no"];
				$index++;
			}
		}
		$this->put_excel($exportList,$one["vname"],$one["vname"].'的统计结果-'.date('YmdHis',time()));
	}


	function put_excel($list,$sheetTitle,$excelFileName){ 
	    // Create new PHPExcel object    
	    $objPHPExcel = new \Org\Util\PHPExcel(); 
	    $objPHPExcel->getProperties()->setCreator("ctos")  
	            ->setLastModifiedBy("ctos")  
	            ->setTitle("Office 2007 XLSX Test Document")  
	            ->setSubject("Office 2007 XLSX Test Document")  
	            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")  
	            ->setKeywords("office 2007 openxml php")  
	            ->setCategory("Test result file");

        // set width    
	    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(100);  
	    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);  
	  
	    // 设置行高度    
	    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);  
	  
	    // 字体和样式  
	    $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);  
	    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);  
	  
	    $objPHPExcel->getActiveSheet()->getStyle('A1:B1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
	    $objPHPExcel->getActiveSheet()->getStyle('A1:B1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
	    $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);    
		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '问题')  
					->setCellValue('B1', '投票结果');  
		// 内容  
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['title']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['total_no']);  
			$objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 2) . ':B' . ($i +2))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);  
			$objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 2) . ':B' . ($i +2))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);  
		}  

		// Rename sheet    
		$objPHPExcel->getActiveSheet()->setTitle($sheetTitle);  

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet    
		$objPHPExcel->setActiveSheetIndex(0);  
		
		// 输出  
		ob_end_clean();  //清空缓存             
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition: attachment;filename="'.$excelFileName.'.xls"'); 
		header("Content-Transfer-Encoding:binary"); 
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  
		$objWriter->save('php://output'); 
	}
	
}
?>