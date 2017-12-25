<?php
/**
* 用户
*/
namespace Common\Service;
class DutyService
{
	/**
	 * 根据条件获取分类列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getDutyListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("oa_position")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}

	/**
	 * 获取个数
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getDutyCountByWhere($where_,$fields_=""){
		$list = M("oa_position")->field($fields_)->where($where_)->count();
		return $list;
	}
	/**
	 * 获取个数
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getDutyOneByWhere($where_,$fields_=""){
		$list = M("oa_position")->field($fields_)->where($where_)->find();
		return $list;
	}
	/**
	 * 保存
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function saveDuty($where_,$data){
		$list = M("oa_position")->where($where_)->save($data);
		return $list;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addDutyData($data){
		$row = M("oa_position")->add($data);
		return $row;
	}
	/**
	 * delete数据
	 * @param [type] $data [description]
	 */
	function deleteDutyData($where_){
		$row = M("oa_position")->where($where_)->delete();
		return $row;
	}
}
?>