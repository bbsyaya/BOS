<?php
/**
* 计费标识 tgd 03-07 
*/
namespace Common\Service;
use Think\Model;
class ChargingLogoService
{
	/**
	 * 根据条件查找charging对象
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	public function getChargingOneByWhere($where_,$field=""){
		$one = M("charging_logo")->field($field)->where($where_)->find();
		return $one;
	}

	/**
	 * 添加计费标识数据
	 * @param [type] $saveData [description]
	 */
	public function addChargingLog($saveData){
		$row = M("charging_logo")->add($saveData);
		return $row;
	}
	/**
	 * [生成计费标识code]
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function generalCodePub($id) {
		return 'JF' . str_pad(intval($id), 7, 0, STR_PAD_LEFT);
	}

	/**
	 * 保存数据
	 * @param  [type] $saveData [description]
	 * @return [type]           [description]
	 */
	public function saveData($saveData,$where_){
		$row = M("charging_logo")->where($where_)->save($saveData);
		return $row;
	}
}

?>
