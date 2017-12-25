<?php
/**
* 广告主 tgd 2017-03-
*/
namespace Common\Service;
use Think\Model;
class AdvertiserService
{	
	/**
	 * 根据条件查询  
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	function getListByWhere($where_,$field_="",$order_=""){
		$list = M("advertiser")->field($field_)->where($where_)->order($order_)->select();
		return $list;
	}
	
}

?>