<?php
/**
 * 权限
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
 class AuthController extends BaseController
 {
	/**
	 * [authList description]
	 * @return [type] [description]
	 */
 	function authList(){
		$userid   = I("userid");
		$type_id  = I("type_id");//1-用户，2-角色,3--部门
		$datatype = I("datatype");//1-功能权限，2-数据权限
 		$this->assign("userid",$userid);
 		$this->assign("type_id",$type_id);
 		$this->assign("datatype",$datatype);
 		$this->display();
 	}

 	/**
 	 * [获取功能权限 description]
 	 * @return [type] [description]
 	 */
 	function getAuthList(){
		$type_id  = trim(I("type_id"));
		$paramsid = trim(I("paramsid"));
		$fun_per  = array();//功能权限
		$fun_forbidden = array();//功能权限--禁止
		$has_ = false;
		$is_user = false;//是否为用户
 		switch ($type_id) {
 			case 1:
 				//用户
 				//获取用户已经的配置的，拒绝的功能权限
				$user          = new Service\UserService();
				$userone       = $user->getOneByWhere(array("id"=>$paramsid),"fun_per,fun_forbidden");
				$fun_per       = empty($userone["fun_per"])?array():explode(",",$userone["fun_per"]);
				// $fun_forbidden = empty($userone["fun_forbidden"])?array():explode(",",$userone["fun_forbidden"]);
				$has_          = true;
				$is_user       = true;
				unset($userone);
 				break;
 			case 2:
 				//角色
				$user    = new Service\AuthAccessService();
				$userone = $user->getAuth_groupOneByWhere(array("id"=>$paramsid),"rules");
				$fun_per = empty($userone["rules"])?array():explode(",",$userone["rules"]);
 				break;
			case 3:
 				//部门
				$user    = new Service\DepartSettingService();
				$userone = $user->getOneByWhere(array("id"=>$paramsid),"fun_per");
				$fun_per = empty($userone["fun_per"])?array():explode(",",$userone["fun_per"]);
 				break;
 			case 4:
 				//功能可授权范围
 				$data_user_rule=M('user_rule')->where("uid=".$paramsid)->find();
				$fun_per = explode(',', $data_user_rule['kesouquan']);
 				break;
 			case 6:
 				//功能授权
 				$data_user_rule=M('user_rule')->where("uid=".$_SESSION['userinfo']['uid'])->find();
				$fun_per = array();
				if($data_user_rule['kesouquan']!='')$where="status=1 && type_id=0 && id in (".$data_user_rule['kesouquan'].")";
				else $where="1=0";
 				break;
 		}
 		if(!$where)$where=array("status"=>1,"type_id"=>0);
 		$auser = new Service\AuthAccessService();
 		$authList = $auser->getAuthByWhere($where);
 		foreach ($authList as $k => $v) {
			if(in_array($v["id"], $fun_per)){
				$authList[$k]["checked"] = true;
			}
 		}
 		$this->ajaxReturn($authList);
 	}


 	/**
 	 * 获取数据权限
 	 * @return [type] [description]
 	 */
 	function getDataAuthList(){
		$data["type_id"] = trim(I("type_id"));
		$data["paramsid"]  = trim(I("paramsid"));
		//读取所有用户和所有业务线数据
 		$ser = new \Home\Service\AuthService();
 		$res = $ser->getDataAuthListSer($data);
 		$this->ajaxReturn($res);
 	}

 	/**
 	 * 设置功能权限
 	 * @return [type] [description]
 	 */
 	function saveGNAuth(){
 		$type_id_gn = trim(I("type_id_gn"));
 		switch ($type_id_gn) {
 			case 1:
 				$this->saveUserGNAutn();
 				break;
 			
 			case 2:
 				$this->saveRoleGNAutn();
 				break;
			case 3:
 				$this->saveDepartGNAuth();
 				break;
 			case 4:
 				$this->saveGrant();
 				break;
 			case 6:
 				$this->Grantrule_fun();
 				break;
 		}
 	}

 	/**
 	 * 设置数据权限
 	 * @return [type] [description]
 	 */
 	function saveDataAuth(){
 		$type_id_data = trim(I("type_id_data"));
 		switch ($type_id_data) {
 			case 1:
 				$this->saveUserDataAuth();
 				break;
 			
 			case 2:
 				$this->saveRoleDataAuth();
 				break;
			case 3:
 				$this->saveDepartDataAuth();
 				break;
 			case 5:
 				$this->saveGrant_data();
 				break;
 			case 7:
 				$this->Grantrule_data();
 				break;
 		}
 	}

 	/**
 	 * 保存用户功能权限
 	 * @return [type] [description]
 	 */
 	function saveUserGNAutn(){
 		$data = array();
 		if(I("chkIds_true")){
			$data["fun_per"]  = implode(",", I("chkIds_true"));//功能权限
 		}
		$data["fun_updated"] = date("Y-m-d H:i:s",time());
		$userId              = I("userId");
		$user                = new Service\UserService();
		$row                 = $user->saveUserData(array("id"=>$userId),$data);
		$ret                 = array("code"=>500);
		if($row){
			$ret["code"] = 200;
		}
		$this->ajaxReturn($ret);
 	}

 	/**
 	 * 保存用户的数据权限
 	 * @return [type] [description]
 	 */
 	function saveUserDataAuth(){
		$data["all_line"]     = implode(",",I("all_line"));
		$data["all_user"]     = implode(",",I("all_user"));
		$data["userid"]       = trim(I("userid"));
		$authSer              = new \Home\Service\AuthService();
		$res                  = $authSer->saveUserDataAuthSer($data);
		$this->ajaxReturn($res);
 	}

 	/**
 	 * 保存角色数据权限
 	 * @return [type] [description]
 	 */
 	function saveRoleDataAuth(){
			$data                 = array();
			$all_line             = implode(",",I("all_line"));
			$all_user             = implode(",",I("all_user"));
			$data["data_per"]     = $all_line.",".$all_user;
			$data["data_updated"] = date("Y-m-d H:i:s",time());
			
			$roleid               = I("userid");
			$user                 = new Service\AuthAccessService();
			$row                  = $user->saveAuth_groupData(array("id"=>$roleid),$data);
			$ret                  = array("code"=>500);
		if($row){
			$ret["code"] = 200;
		}
		$this->ajaxReturn($ret);
 	}

 	/**
 	 * 保存角色工功能权限
 	 * @return [type] [description]
 	 */
 	function saveRoleGNAutn(){
 		$data = array();
 		if(I("chkIds_true")){
			$data["rules"]  = implode(",", I("chkIds_true"));
 		}
		$data["fun_updated"] = date("Y-m-d H:i:s",time());
		$roleid              = trim(I("userId"));
		$user                = new Service\AuthAccessService();
		$row                 = $user->saveAuth_groupData(array("id"=>$roleid),$data);
		$ret                 = array("code"=>500);
		if($row){
			$ret["code"] = 200;
		}
		$this->ajaxReturn($ret);
 	}

 	/**
 	 * 保存部门功能权限
 	 * @return [type] [description]
 	 */
 	function saveDepartGNAuth(){
 		$data = array();
 		if(I("chkIds_true")){
			$data["fun_per"]  = implode(",", I("chkIds_true"));
 		}
		$data["fun_updated"] = date("Y-m-d H:i:s",time());
		$departid            = I("userId");
		$user                = new Service\DepartSettingService();
		$row                 = $user->saveData(array("id"=>$departid),$data);
		$ret                 = array("code"=>500);
		if($row){
			$ret["code"] = 200;
		}
		$this->ajaxReturn($ret);
 	}


 	/**
 	 * 保存部门数据权限功能
 	 * @return [type] [description]
 	 */
 	function saveDepartDataAuth(){
 		$data = array();
 		$all_line             = implode(",",I("all_line"));
		$all_user             = implode(",",I("all_user"));
		$data["data_per"]     = $all_line.",".$all_user;
		$data["data_updated"] = date("Y-m-d H:i:s",time());
		$departid             = I("userid");
		$user                 = new Service\DepartSettingService();
		$row                  = $user->saveData(array("id"=>$departid),$data);
		$ret                  = array("code"=>500);
		if($row){
			$ret["code"] = 200;
		}
		$this->ajaxReturn($ret);
 	}


 	//保存功能授权权限
 	public function saveGrant(){
 		$data = array();
 		if(I("chkIds_true")){
			$data["kesouquan"]  = implode(",", I("chkIds_true"));//功能权限
 		}
		$data_u_r=M('user_rule')->where("uid=".I('userId'))->find();
		if($data_u_r){
			$row                 = M('user_rule')->where("uid=".I('userId'))->save($data);
		}else{
			$data['uid']=I('userId');
			$row                 = M('user_rule')->add($data);

		}
		
		$ret                 = array("code"=>500);
		if($row){
			$ret["code"] = 200;
		}
		$this->ajaxReturn($ret);
 	}

 	//保存数据授权权限
 	public function saveGrant_data(){
 		$data                 = array();
		$all_line             = implode(",",I("all_line"));
		$all_user             = implode(",",I("all_user"));
		$data["kesouquan_data"]     = $all_line.",".$all_user;
		$data_u_r=M('user_rule')->where("uid=".I('userid'))->find();
		if($data_u_r){
			$row                 = M('user_rule')->where("uid=".I('userid'))->save($data);
		}else{
			$data['uid']=I('userId');
			$row                 = M('user_rule')->add($data);

		}
		
		$ret                 = array("code"=>500);
		if($row){
			$ret["code"] = 200;
		}
		$this->ajaxReturn($ret);
 	}
 	public function Grantrule_data(){
 		//数据授权
 		$data['addtime']=date('Y-m-d H:i:s');
 		$data['adduid']=$_SESSION['userinfo']['uid'];
 		$data['uid']=I('post.userid');
 		if(I('post.linshi')==''){
 			$data['type']=1;
 			$data['endtime']=date('Y-m-d H:i:s',time()+I('post.chongfu')*3600*24*7);
 			$data['htime']=substr(I('post.htime'), 0,-1); 
 			$all_line             = implode(",",I("all_line"));
			$all_user             = implode(",",I("all_user"));
			$data["rulelist_data"]     = $all_line.",".$all_user;
			$data['is_worktime'] = (I('post.is_worktime')=="true")?1:0;
 		}else{
 			$data['type']=2;
 			$data['endtime']=date('Y-m-d H:i:s',time()+I('post.linshi')*3600);
 			$all_line             = implode(",",I("all_line"));
			$all_user             = implode(",",I("all_user"));
			$data["rulelist_data"]     = $all_line.",".$all_user;
 		}
 		$row=M('rule_grant')->add($data);
 		$ret                 = array("code"=>500);
		if($row){
			$ret["code"] = 200;
		}
		$this->ajaxReturn($ret);
 	}
 	public function Grantrule_fun(){
 		//功能授权
 		$data['addtime']=date('Y-m-d H:i:s');
 		$data['adduid']=$_SESSION['userinfo']['uid'];
 		$data['uid']=I('post.userId');
 		if(I('post.linshi')==''){
 			$data['type']=1;
 			$data['endtime']=date('Y-m-d H:i:s',time()+I('post.chongfu')*3600*24*7);
 			$data['htime']=substr(I('post.htime'), 0,-1); 
			$data["rulelist_fun"]  = implode(",", I("chkIds_true"));
			$data['is_worktime'] = (I('post.is_worktime')=="true")?1:0;
 		}else{
 			$data['type']=2;
 			$data['endtime']=date('Y-m-d H:i:s',time()+I('post.linshi')*3600);
 			$data["rulelist_fun"]  = implode(",", I("chkIds_true"));
 		}
 		$row=M('rule_grant')->add($data);
 		$ret                 = array("code"=>500);
		if($row){
			$ret["code"] = 200;
		}
		$this->ajaxReturn($ret);
 	}
 	public function getruleh(){
 		$data=M('rule_grant')->where("uid=".I('get.paramsid')." && endtime>='".date("Y-m-d H:i:s")."'")->select();
 		foreach ($data as $key => $value) {
 			$arr_u=$arr_l=$rule=array();
 			if($value['rulelist_data']!=''){
 				$arr_data=explode(',', $value['rulelist_data']);
	 			foreach ($arr_data as $k => $val) {
	 				if(substr($val,0,1)=='l')$arr_l[]=substr($val,2);
	 				elseif(substr($val,0,1)=='u') $arr_u[]=substr($val,2);
	 			}
	 			$data_str='';
	 			if(count($arr_l)>0){
	 				$data_l=M('business_line')->field('group_concat(name) as linename')->where("id in (".implode(',', $arr_l).")")->find();
	 				$data_str.=$data_l['linename'];
	 			}
	 			if(count($arr_u)>0){
	 				$data_u=M('user')->field('group_concat(real_name) as uname')->where("id in (".implode(',', $arr_u).")")->find();
	 				if($data_str=='')$data_str.=$data_l['linename'];
	 				else $data_str.=",".$data_u['uname'];
	 			}
	 			if($data_str!='')$rule[]=$data_str;
	 		}
 			if($value['rulelist_fun']!=''){
 				$data_fun=M('auth_rule')->field('group_concat(title) as alltitle')->where("id in (".$value['rulelist_fun'].")")->find();
	 			$rule[]=$data_fun['alltitle'];
	 		}
 			$data[$key]['allrule']=implode(',', $rule);
 		}
 		echo json_encode($data);
 	}
 } 
?>