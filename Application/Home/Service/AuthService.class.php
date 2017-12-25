<?php
/**
* 权限service
*/
namespace Home\Service;
use Think\Model;
use Common\Service;
class AuthService
{	
	
	/**
	 * 获取数据权限
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function getDataAuthListSer($data){
		$data_per       = array();//数据权限
		$where_u=$where_l='status=1';
 		switch ($data['type_id']) {
 			case 1:
 				//用户
 				//获取用户已经的配置的，拒绝的功能权限
				$user           = new Service\UserService();
				$userone        = $user->getOneByWhere(array("id"=>$data['paramsid']),"data_per");
				$data_per       = empty($userone["data_per"])?array():explode(",",$userone["data_per"]);
				unset($userone);
				$is_user = true;
 				break;
 			case 2:
 				//角色
				$user     = new Service\AuthAccessService();
				$userone  = $user->getAuth_groupOneByWhere(array("id"=>$data['paramsid']),"data_per");
				$data_per = empty($userone["data_per"])?array():explode(",",$userone["data_per"]);
 				break;
			case 3:
 				//部门
				$user     = new Service\DepartSettingService();
				$userone  = $user->getOneByWhere(array("id"=>$data['paramsid']),"data_per");
				$data_per = empty($userone["data_per"])?array():explode(",",$userone["data_per"]);
 				break;
 			case 5:
 				//授权权限
 				$data_user_rule=M('user_rule')->where("uid=".$data['paramsid'])->find();
				$data_per = explode(',', $data_user_rule['kesouquan_data']);
 				break;
 			case 7:
 				//授权
 				$data_user_rule=M('user_rule')->where("uid=".$_SESSION['userinfo']['uid'])->find();
 				$arr_rule=explode(',', $data_user_rule['kesouquan_data']);
 				$arr_line=$arr_user=array();

 				foreach ($arr_rule as $key => $value) {
 					if($value[0]=='u')$arr_user[]=substr($value, 2);
 					if($value[0]=='l')$arr_line[]=substr($value, 2);
 				}
				$data_per = array();
				$where_u="status=1 && id in (".implode(',', $arr_user).")";
				$where_l="status=1 && id in (".implode(',', $arr_line).")";
 				break;
 		}

 		//读取所有用户和业务线
		$res = array("userlist"=>array(),"linelist"=>array());
		$userlist = M("user")->field("id,real_name")->where($where_u)->select();
		foreach ($userlist as $k => $v) {
			$userlist[$k]["id"]       = "u_".$v['id'];
			$userlist[$k]['selected'] = ""; 
			//判断当前用户的选中了哪些权限
			if(in_array($userlist[$k]["id"], $data_per)){
				$userlist[$k]['selected'] = "selected='selected'"; 
			}
		}
		$res["userlist"] = $userlist;

		$linelist = M("business_line")->field("id,name")->where($where_l)->select();
		foreach ($linelist as $k => $v) {
			$linelist[$k]["id"]       = "l_".$v["id"];
			$linelist[$k]['selected'] = ""; 
			//判断当前用户的选中了哪些权限
			if(in_array($linelist[$k]["id"], $data_per)){
				$linelist[$k]['selected'] = "selected='selected'"; 
			}
		}
		$res["linelist"] = $linelist;
		return $res;
	}

	/**
	 * 保存用户的数据权限
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	function saveUserDataAuthSer($data){
		
		$data_["data_per"]     = $data["all_line"].",".$data["all_user"];
		$data_["data_updated"] = date("Y-m-d H:i:s",time());

		$row = D("user")->where(array("id"=>$data["userid"]))->save($data_);
		unset($data_);

		$res = array("code"=>500);
		if($row){
			$res["code"] = 200;
		}
		return $res;
	}
}

?>