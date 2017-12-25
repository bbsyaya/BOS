<?php
/**
* 收入数据 service tgd 03-07 
*/
namespace Common\Service;
use Think\Model;
class DayDataService{
	
	/**
	 * [根据where条件查询收入数据]
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	public function getDayDataOneByWhere($where_,$field=""){
		$one = M("daydata")->field($field)->where($where_)->find();
		return $one;
	}

	/**
	 * 保存daydata
	 * @param [type] $saveData [description]
	 */
	public function addDayData($saveData){
		$row = M("daydata")->add($saveData);
		return $row;
	}
	
	/**
	 * 修改数据
	 * @param  [type] $saveData [description]
	 * @param  string $where    [description]
	 * @return [type]           [description]
	 */
	public function updateDayData($saveData,$where_=""){
		$row = M("daydata")->where($where_)->save($saveData);
		return $row;
	}

}
?>