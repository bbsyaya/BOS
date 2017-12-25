<?php
/**
* 职位
*/
namespace Common\Service;
use Think\Model;
class OAPositionService
{	
	/**
	 * 根据条件查询  
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	function getListByWhere($where_,$field_="",$order_=""){
		$list = M("oa_position")->field($field_)->where($where_)->order($order_)->select();
		return $list;
	}
	
}

?>