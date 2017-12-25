<?php
namespace Home\Model;
use Think\Model;
class RegionModel extends Model {

	/**
	 * 初始化数据
	 * @param int $provinceId 选中的 省级
	 * @param int $cityId
	 * @return array
	 */
	public function getInitData($provinceId=0, $cityId=0) {

		$provinceData = $this->where('pid=0')->getField('id,name');
		if ($provinceId > 0) {
			$cityData = $this->where("pid={$provinceId}")->getField('id,name');
		}
		if ($cityId > 0) {
			$districtData = $this->where("pid={$cityId}")->getField('id,name');
		}
		return array('province'=>$provinceData, 'city'=>$cityData, 'district'=>$districtData);
	}


	/**
	 * 获取初始化地区名称
	 * @param int $provinceId
	 * @param int $cityId
	 * @param int $districtId
	 * @return return
	 */
	public function getInitName($provinceId=0, $cityId=0, $districtId=0) {

		$fields = array();
		$ids = '';
		if ($provinceId > 0) {
			$ids .= $provinceId;
		}
		if ($cityId > 0) {
			$ids .= ',' . $cityId;
		}
		if ($districtId > 0) {
			$ids .= ',' . $districtId;
		}

		$datas = $this->where("id IN ($ids)")->getField('id,name');

		$fields['province'] = $datas[$provinceId];
		$fields['city'] =     $datas[$cityId];
		$fields['district'] = $datas[$districtId];
		return $fields;
	}


	/**
	 * 获取地区
	 * @param int $pid 上级id
	 * @return return
	 */
	public function getRegion ($pid=0) {
		//TODO: 缓存一个小时
		return $this->where('pid='.$pid)->getField("id,name");
	}

}