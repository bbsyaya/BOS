<?php
/**
* 收入成本 tgd 03-07 
*/
namespace Common\Service;
use Think\Model;
class DayDataOutService
{
	/**
	 * 找到成本对象
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	public function getDayOutOneByWhere($where_,$field=""){
		$one = M("daydata_out")->field($field)->where($where_)->find();
		return $one;
	}

	/**
	 * 修改数据
	 * @param  [type] $saveData [description]
	 * @param  string $where    [description]
	 * @return [type]           [description]
	 */
	public function updateDayOut($saveData,$where_=""){
		$row = M("daydata_out")->where($where_)->save($saveData);
		return $row;
	}

	/**
	 * 添加
	 * @param [type] $saveData [description]
	 */
	public function addDayDataOut($saveData){
		$row = M("daydata_out")->add($saveData);
		return $row;
	}
}

?>