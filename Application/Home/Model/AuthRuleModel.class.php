<?php

namespace Home\Model;
use Think\Model;
/**
 * 权限规则模型
 */
class AuthRuleModel extends Model{
    
    const RULE_URL = 1;
    const RULE_MAIN = 2;
	public $totalPage = 0;
	protected $tableName = 'auth_rule';

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
