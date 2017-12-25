<?php
/**
* 公司部门
*/
namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class OrganizSettingController extends BaseController
{
	private $departSer;
	function _initialize(){
		parent::_initialize();
		$this->departSer = !$this->departSer ? new Service\DepartSettingService() : $this->departSer;
	}

	/**
	 * 显示
	 * @return [type] [description]
	 */
	function setting(){
		$pid = trim(I("pid"));
		$this->assign("pid",$pid);
		
		//编辑
		$id = trim(I("id"));
		if($id){
			$where = array();
			$where["id"] = $id;
			$one = $this->departSer->getOneByWhere($where);
			$this->assign("one",$one);
		}
		$this->display();
	}

	/**
	 * 获取用户
	 * @return [type] [description]
	 */
	function getUserList(){
		$userSer         = new Service\UserService();
		$where           = array();
		$where["status"] = 1;
		$userList        = $userSer->getUserListByWhere($where,"id,real_name");
		$this->ajaxReturn($userList);
	}

	/**
	 * 获取json_tree
	 * @return [type] [description]
	 */
	function getJsonTree(){
		$pid = I("id");
		$where["pid"] = $pid;
		$list = $this->departSer->getListByWhere($where);
		$newList = array();
		foreach ($list as $k => $v) {
			$ch = M("user_department")->field("id")->where(array("pid"=>$v["id"]))->count();
			$one["children"] = false;
			if($ch){
				$one["children"] = true;
			}
			$one["id"] = $v["id"];
			$one["text"] = $v["name"];
			$one["data"] = $v;
			$newList[] = $one;
		}
		$this->ajaxReturn($newList);
	}

	/**
	 * 获取所有的list
	 * @return [type] [description]
	 */
	function getAllList(){
		$list = $this->departSer->getAllTreeList(array("type"=>0));
		$this->ajaxReturn($list);
	}

	/**
	 * 保存数据
	 * @return [type] [description]
	 */
	function saveDepart(){
		// print_r($_POST);exit;
		$id                   = I("depart_id");//编辑传入
		$data["name"]         = trim(I("name"));
		$data["pid"]          = I("pid");
		$data["sort"]         = trim(I("sort"));
		$data["heads_id"]     = I("heads");
		$data["functions"]    = trim(I("functions"));
		$data["companyid"]    = I("companyid");
		$deparOne             = $this->departSer->getOneByWhere(array("id"=>$data["companyid"]),"name");
		$data["company_name"] = $deparOne["name"];
		$userSer              = new Service\UserService();
		$userOne              = $userSer->getOneByWhere(array("id"=>$data["heads_id"]),"real_name");
		$data["heads"]        = $userOne["real_name"];
		$result = array("msg"=>"保存失败","status"=>500);
		if($id>0){
			//修改
			$where["id"] = $id;
			$row = $this->departSer->saveData($where,$data);
			$result = array("msg"=>"保存成功","status"=>200);
		}else{
			//添加
			$data["dateline"]        = date("Y-m-d H:i:s",time());
			$row = $this->departSer->addData($data);
			$result = array("msg"=>"保存成功","status"=>200);
		}
		$this->ajaxReturn($result);
	}

	/**
	 * 生成图
	 * @return [type] [description]
	 */
	function crateImg(){
		$this->assign("year",date("Y",time()));
		$this->display();
	}

	/**
	 * 获取json
	 * @return [type] [description]
	 */
	function getJsonTrees(){
		$list = $this->departSer->getAllTreeListNoOptionsLine("type=0","id,name,pid,heads");
		$this->ajaxReturn($list);
	}

	/**
	 * 创建树形图
	 * @return [type] [description]
	 */
	function getJsonTrees_createimg(){
		$list = $this->departSer->getAllTreeListNoOptionsLine_img();
		$this->ajaxReturn($list);
	}

	/**
	 * 初始化sort
	 * @return [type] [description]
	 */
	function initSort(){
		$token = I("token");
		if($token){
			$list = $this->departSer->getListByWhere("1=1","id","pid asc");
			$i = 1000;
			foreach ($list as $k => $v) {
				$data["sort"] = $i-$k;
				$where_["id"] = $v["id"];
				$row = $this->departSer->saveData($where_,$data);
			}
		}
		echo "over";
	}


	/**
	 * 组织机构列表
	 * @return [type] [description]
	 */
	function settingList(){
		$where       = "id>0 and type in (0,1)";
		$name        = trim(I("name"));
		$map["name"] = $name;
		$this->assign("map",$map);
		$list = array();
		if($name){
			$where .= " and name like '%".$name."%'";

			$list = $this->departSer->getListByWhere($where,"id,name,pid,heads,functions,company_name");
		}else{
			$list = $this->departSer->getAllTreeListNoOptions($where,"id,name,pid,heads,functions,company_name");

		}
		
		$this->assign("list",$list);
		$this->display();
	}

	/**
	 * 获取公司
	 * @return [type] [description]
	 */
	function getCompnanys(){
		$result = S("getCompnanys_List");
		if(!$result){
			$result =  $this->departSer->getAllTreeList(array("type"=>1));;
			S("getCompnanys_List",$result,300);
		}
		$this->ajaxReturn($result);
	}

	/**
	 * 获取职位list
	 * @return [type] [description]
	 */
	function getPositonList(){
		$posSer = new Service\OAPositionService();
		$list = $posSer->getListByWhere();
		$this->ajaxReturn($list);
	}

	/**
	 * 部门详细
	 * @return [type] [description]
	 */
	function departDetail(){
		$did          = trim(I("id"));
		if(!$did){
			$this->success("无法访问！");exit;
		}
		$type_id      = trim(I("typeid"));//类型，是否读取子集部门所有人
		$where_["id"] = $did;
		$pids         = "";
		if($type_id==1){
			$pids = $this->departSer->getDepartChildsIdsBypid($did);
			$did  =$did.",".$pids;
		}
		$fields_      = "miss,obj_vision,responsibilities,project,sec_arch,id,name,heads,functions,pid";
		$done = $this->departSer->getOneByWhere($where_,$fields_);
		unset($where_);
		$where_["id"] = $done["pid"];
		$done_p = $this->departSer->getOneByWhere($where_,"name");
		$done["p_name"] = $done_p["name"];

		//获取部门人数

		$hrSer = new Service\HrManageService();
		$userList = $hrSer->getHRListByWhere(array("depart_id"=>array("in",$did),"status"=>array("neq",1)),"user_name,sex");
		$this->assign("userList",$userList);
		$this->assign("done",$done);
		$this->display();
	}

	/**
	 * 保存部门信息
	 * @return [type] [description]
	 */
	function saveDepartDesc(){
		$did    = trim(I("id"));
		$txt    = trim(I("txt"));
		$type   = trim(I("type"));
		$result = array("msg"=>"","status"=>"500");
		$data   = array();
		switch ($type) {
			case 'miss':
			//保存使命
				$data["miss"] = htmlspecialchars($txt);
			break;
			case 'functions':
			// 部门职责
				$data["functions"] = htmlspecialchars($txt);
			break;
			case 'obj_vision':
			// 愿景目标
				$data["obj_vision"] = htmlspecialchars($txt);
			break;
		}
		$where_["id"] = $did;
		$row = $this->departSer->saveData($where_,$data);
		if($row) $result = array("msg"=>"保存成功","status"=>"200");
		$this->ajaxReturn($result);
	}

	/**
	 * 删除组织
	 * @return [type] [description]
	 */
	function delsetting(){
		$id = trim(I("id"));
		//检查是否含有子类
		$childList = $this->departSer->getListCountByWhere(array("pid"=>$id),"id");
		$result = array("code"=>500,"msg"=>"操作失败");
		if($childList>0){
			$result = array("code"=>501,"msg"=>"该部门下含有子部门，您确定要删除吗？");
		}else{
			//删除子部门
			$row = $this->departSer->deleteDataByWhere(array("id"=>$id));
			if($row) $result = array("code"=>200,"msg"=>"删除成功");
		}
		$this->ajaxReturn($result);
	}

	function delsettingsur(){
		$id = trim(I("id"));
		$result = array("code"=>500,"msg"=>"操作失败");
		$row = $this->departSer->deleteDataByWhere(array("id"=>$id));
		if($row) $result = array("code"=>200,"msg"=>"删除成功");
		$this->ajaxReturn($result);
	}

	/**
	 * 修改密码
	 * @return [type] [description]
	 */
	function updatePwd(){
		$this->display();
	}
	
}
?>	