<?php

namespace Home\Model;
use Think\Model;
/**
 * 数据字典类型模型
 */
class DataDicTypeModel extends Model{
    
	public $totalPage = 0;
	protected $tableName = 'data_dic_type';

	//权限列表
	public function getList($where) {
		$groupList = $this->where($where)->page($_GET['p'],10)->select();
		$this->totalPage = $this->where($where)->count();

		//没有数据
		if ($this->totalPage == 0) {
			return array();
		}
		return $groupList;
	}

}
