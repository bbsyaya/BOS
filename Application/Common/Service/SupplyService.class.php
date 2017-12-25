<?php
/**
* 供应商 tgd 2017-03-
*/
namespace Common\Service;
use Think\Model;
class SupplyService
{	
	/**
	 * 根据条件查询  
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	function getListByWhere($where_,$field_="",$order_=""){
		$list = M("supplier")->field($field_)->where($where_)->order($order_)->select();
		return $list;
	}
	
}

?>