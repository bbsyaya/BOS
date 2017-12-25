<?php
/**
* 权限 tgd 20170707
*/
namespace Common\Service;
use Think\Model;
class AuthAccessService
{	
	/**
	 * 检查当前用户有查看检查项权限
	 * @return [type] [description]
	 */
	function currentIsHasAuth($auth_url,$current_uid){

		//获取传入的权限id
		$cur_auth_id = M("auth_rule")->field("id")->where(array("name"=>$auth_url))->find();
		$isHas = 500;
		if($cur_auth_id){
			$menus_ids_list = $_SESSION['userinfo']['fun_config'];
			if(in_array($cur_auth_id["id"], $menus_ids_list)){
				$isHas = 200;
			}
		}
		unset($menus_ids_list);unset($cur_auth_id);
		return $isHas;
	}


	/**
	 * 获取左边菜单栏--当前用户
	 * @return [type] [description]
	 */
	function getAuthLeftMenuSer($user_id,$module_name="OA"){
		$sql = "SELECT 
				  a.id,
				  a.title,
				  a.name,
				  b.id AS bid,
				  a.pid,
				  a.img,a.sort
				FROM
				  `boss_auth_rule` a 
				  LEFT JOIN boss_auth_rule b 
				    ON b.is_hide = 0 && a.id = b.pid 
				WHERE (
				    a.is_hide = 0 && a.name LIKE '/{$module_name}%'
				  ) 
				GROUP BY a.id 
				ORDER BY a.sort DESC ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		unset($sql);


		$menus_ids_list =$_SESSION['userinfo']['fun_config'];
		$new_auth_ids   = "";
		foreach ($list as $k => $v) {
			if(in_array($v["id"], $menus_ids_list)){
				$new_auth_ids .= $v["id"].",";
			}
		}
		if($new_auth_ids){
			$new_auth_ids = substr($new_auth_ids,0,strlen($new_auth_ids)-1);
		}else{
			return false;
		}
		
		//获取当前用户已确定的一级菜单和二级菜单
		$list    = M("auth_rule")->field("id,name,title,img,pid")->where(array("id"=>array("in",$new_auth_ids)))->order("sort desc")->select();
		unset($new_auth_ids);
		$newList = array();
		foreach ($list as $k => $v) {
			if($v["pid"]==0){
				//切换不同的图标
				$class_img = "";
				switch ($v["title"]) {
					case '行政办公':
						$class_img = "nav_account";
						break;
					case '人事办公':
						$class_img = "nav_agent";
						break;
					case "财务办公":
						$class_img = "nav_order";
					break;
					case "系统管理":
						$class_img = "nav_con";
					break;
					default:
						$class_img = "nav_account";
						break;
				}
				$v["class_img"] = $class_img;
				$newList[$v["id"].$v["sort"]] = $v;
			}else{
				continue;
			}
		}
		// 
		//sort越大越靠前
		ksort($newList);
		//循环新一级菜单的二级菜单
		foreach ($newList as $k => $v) {
			foreach ($list as $ka => $va) {
				if($v["id"]==$va["pid"]){
					$newList[$k]["childMenu"][] = $va;
				}
			}
		}
		unset($list);
		return $newList;

	}


	function getAuthOffice($uid,$id='233'){
		$sql = "SELECT
				  a.id,
				  a.title,
				  a.name,
				  b.id AS bid,
				  a.pid,
				  a.img,a.sort
				FROM
				  `boss_auth_rule` a
				  LEFT JOIN boss_auth_rule b
				    ON b.is_hide = 0 && a.id = b.pid
				WHERE (
				    a.is_hide =0 and a.pid={$id}
				  )
				GROUP BY a.id
				ORDER BY a.sort DESC ";

		$model = new \Think\Model();
		$list = $model->query($sql);
		unset($sql);


		$menus_ids_list = $_SESSION['userinfo']['fun_config'];
		$new_auth_ids   = "";
		foreach ($list as $k => $v) {
			if(in_array($v["id"], $menus_ids_list)){
				$new_auth_ids .= $v["id"].",";
			}
		}

		if($new_auth_ids){
			$new_auth_ids = substr($new_auth_ids,0,strlen($new_auth_ids)-1);
		}else{
			return false;
		}

		//获取当前用户已确定的一级菜单和二级菜单
		$list    = M("auth_rule")->field("id,name,title,img,pid")->where(array("id"=>array("in",$new_auth_ids)))->order("sort desc")->select();
		/*unset($new_auth_ids);
		$newList = array();
		foreach ($list as $k => $v) {
			if($v["pid"]==0){
				//切换不同的图标
				$class_img = "";
				switch ($v["title"]) {
					case '行政办公':
						$class_img = "nav_account";
						break;
					case '人事办公':
						$class_img = "nav_agent";
						break;
					case "财务办公":
						$class_img = "nav_order";
						break;
					case "系统管理":
						$class_img = "nav_con";
						break;
					default:
						$class_img = "nav_account";
						break;
				}
				$v["class_img"] = $class_img;
				$newList[$v["id"].$v["sort"]] = $v;
			}else{
				continue;
			}
		}
		//
		//sort越大越靠前
		ksort($newList);
		//循环新一级菜单的二级菜单
		foreach ($newList as $k => $v) {
			foreach ($list as $ka => $va) {
				if($v["id"]==$va["pid"]){
					$newList[$k]["childMenu"][] = $va;
				}
			}
		}
		unset($list);*/
		return $list;
	}
	/**
	 * 获取oa首页当前用户所拥有子菜单
	 * @return [type] [description]
	 */
	function getOAChildMenu($user_id,$module_name="OA"){
		$menu_list = $this->getAuthLeftMenuSer($user_id,$module_name);
		$newOAMenu = array();
		foreach ($menu_list as $k => $v) {
			switch ($v["title"]) {
				case '行政办公':
					foreach ($v["childMenu"] as $ka => $va) {
						$one         = array();
						$one["name"] = $va["title"];
						$one["type"] = "行政办公";
						$one["url"]  = $va["name"];
						$one["id"]   = $va["id"];
						// $one["img_click"] = "";
						// $one["img"] = "";
						$one["mid"] = $v["img"];
						$newOAMenu[] = $one;
					}
					
					break;
				case '人事办公':
					foreach ($v["childMenu"] as $ka => $va) {
						$one         = array();
						$one["name"] = $va["title"];
						$one["type"] = "人事办公";
						$one["url"]  = $va["name"];
						$one["id"]   = $va["id"];
						// $one["img_click"] = "";
						// $one["img"] = "";
						$one["mid"] = $v["img"];
						$newOAMenu[] = $one;
					}
					break;
				case "财务办公":
					foreach ($v["childMenu"] as $ka => $va) {
						$one         = array();
						$one["name"] = $va["title"];
						$one["type"] = "财务办公";
						$one["url"]  = $va["name"];
						$one["id"]   = $va["id"];
						// $one["img_click"] = "";
						// $one["img"] = "";
						$one["mid"] = $v["img"];
						$newOAMenu[] = $one;
					}
				break;
				default:
					continue;
				break;
			}
		}
		return $newOAMenu;
	}

	/**
	 * 获取权限
	 * @param  [type] $where [description]
	 * @param  string $order [description]
	 * @return [type]        [description]
	 */
	function getAuthByWhere($where,$order=""){
		$rules = M('auth_rule')->field('id,pid,title as name')->where($where)->order($orders)->select();
		return $rules;
	}


	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function saveAuth_groupData($where_,$data){
		$row = M("auth_group")->where($where_)->save($data);
		return $row;
	}

	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getAuth_groupOneByWhere($where_,$fields_=""){
		$list = M("auth_group")->field($fields_)->where($where_)->find();
		return $list;
	}
}

?>