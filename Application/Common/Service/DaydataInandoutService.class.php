<?php
/**
* 收入成本明细 tgd 03-07 
*/
namespace Common\Service;
use Think\Model;
class DaydataInandoutService
{
	/**
	 * 获取收入明细
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	public function getInanDouOneByWhere($where_,$field=""){
		$one = M("daydata_inandout")->field($field)->where($where_)->find();
		return $one;
	}

	/**
	 * 修改数据
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function updateInan($data,$where_){
		$row = M("daydata_inandout")->where($where_)->save($data);
		return $row;
	}
	/**
	* 修改数据
	* @param  [type] $data [description]
	* @return [type]       [description]
	*/
	public function addInan($data){
		$row = M("daydata_inandout")->add($data);
		return $row;
	}

}

?>