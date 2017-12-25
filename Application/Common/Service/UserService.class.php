<?php
/**
* 用户
*/
namespace Common\Service;
class UserService
{
	/**
	 * 根据条件获取分类列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getUserListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("user")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}

	/**
	 * 获取个数
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getUserCountByWhere($where_,$fields_=""){
		$list = M("user")->field($fields_)->where($where_)->count();
		return $list;
	}
	
	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneByWhere($where_,$fields_=""){
		$list = M("user")->field($fields_)->where($where_)->find();
		return $list;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addData($data){
		$row = M("user")->add($data);
		return $row;
	}

	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function saveUserData($where_,$data){
		$row = M("user")->where($where_)->save($data);
		return $row;
	}

	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneUserAuthByWhere($where_,$fields_=""){
		$list = M("auth_group_access")->field($fields_)->where($where_)->find();
		return $list;
	}

	public function generalOACode($id) {
		return str_pad(intval($id), 5, 0, STR_PAD_LEFT);
	}

	/**
	 * 检查是否修改部门和角色，同时修改用户表的data_updated，fun_updated
	 * @param  [type]  $data [description]
	 * @return boolean       [description]
	 */
	function isNeedSetUserGNDataUpdateTime($data){
		$userOne  = M("user")->field("dept_id,group_id")->where(array("id"=>$data["uid"]))->find();
		if((!empty($data["depart_id"]) && $userOne["dept_id"]!=$data["depart_id"]) || (!empty($data["group_id"]) && $userOne["group_id"]!=$data["group_id"])){
			$time                  = date("Y-m-d H:i:s",time());
			$data_["fun_updated"]  = $time;
			$data_["data_updated"] = $time;
			M("user")->where(array("id"=>$data["uid"]))->save($data_);
			unset($data_);
		}
		unset($userOne);
		unset($data);
	}
}
?>