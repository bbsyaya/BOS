<?php

namespace Home\Model;
use Think\Model;
/**
 * 数据字典模型
 */
class DataDicModel extends Model{
    
	public $totalPage = 0;
	protected $tableName = 'data_dic';

	//权限列表
	public function getList($where) {
		$groupList = $this->where($where)->page($_GET['p'],10)->order('id asc')->select();
		$this->totalPage = $this->where($where)->count();

		//没有数据
		if ($this->totalPage == 0) {
			return array();
		}
		return $groupList;
	}


	//根据父级获取字典
	public function getByPid($pid) {
		$_map['pid'] = $pid;
		$_map['status'] = 1;
		return $this->where($_map)->order('sort asc')->getField('id,code,name');
	}

	//获取结算主体
	public function getSignBody() {
		return $this->where('dic_type=4')->getField('id,name');
	}

	//开票内容
	public function getInvoiceRemark() {
		return $this->where('dic_type=13')->getField('name,name as val');
	}

}
