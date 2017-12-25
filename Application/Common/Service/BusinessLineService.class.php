<?php
/**
* 业务线 tgd 03-07 
*/
namespace Common\Service;
use Think\Model;
class BusinessLineService
{
	/**
	 * 根据条件查找charging对象
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	public function getBusinessLineOneByWhere($where_,$field=""){
		$one = M("business_line")->field($field)->where($where_)->find();
		return $one;
	}
	
	/**
	 * 根据条件查询  
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	function getListByWhere($where_,$field_="",$order_=""){
		$list = M("business_line")->field($field_)->where($where_)->order($order_)->select();
		return $list;
	}

	/**
	 * 添加计费标识数据
	 * @param [type] $saveData [description]
	 */
	public function addBusinessLineLog($saveData){
		$row = M("business_line")->add($saveData);
		return $row;
	}
	

	/**
	 * 保存数据
	 * @param  [type] $saveData [description]
	 * @return [type]           [description]
	 */
	public function saveData($saveData,$where_){
		$row = M("business_line")->where($where_)->save($saveData);
		return $row;
	}
}

?>
