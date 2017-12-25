<?php

namespace Home\Model;
use Think\Model;

/**
 * 用户组模型类
 * Class AuthGroupModel
 */
class AuthGroupModel extends Model {
    const TYPE_ADMIN                = 1;                   // 管理员用户组类型标识
    const MEMBER                    = 'user';
    const UCENTER_MEMBER            = 'ucenter_member';
    const AUTH_GROUP_ACCESS         = 'auth_group_access'; // 关系表表名
    const AUTH_EXTEND               = 'auth_extend';       // 动态权限扩展信息表
    const AUTH_GROUP                = 'auth_group';        // 用户组表名
    const AUTH_EXTEND_CATEGORY_TYPE = 1;              // 分类权限标识
    const AUTH_EXTEND_MODEL_TYPE    = 2; //分类权限标识

	protected $tableName = self::AUTH_GROUP;
	public $totalPage = 0;

    protected $_validate = array(
        array('title','require', '必须设置用户组标题', Model::MUST_VALIDATE ,'regex',Model::MODEL_INSERT),
        //array('title','require', '必须设置用户组标题', Model::EXISTS_VALIDATE  ,'regex',Model::MODEL_INSERT),
        array('desc','0,80', '描述最多80字符', Model::VALUE_VALIDATE , 'length'  ,Model::MODEL_BOTH ),
       // array('rules','/^(\d,?)+(?<!,)$/', '规则数据不合法', Model::VALUE_VALIDATE , 'regex'  ,Model::MODEL_BOTH ),
    );


	/**
	 * 角色列表
	 * @param $where
	 * @return array|mixed
	 */
	public function getList($where) {

		$groupList = $this->where($where)->page($_GET['p'],10)->select();
		$this->totalPage = $this->where($where)->count();

		//没有数据
		if ($this->totalPage == 0) {
			return array();
		}

		$ids = array();
		foreach ($groupList as $val) {
			$ids[] = $val['id'];
		}
		$idsStr = implode(',', $ids);
		$groupNum = M(self::AUTH_GROUP_ACCESS)->where("group_id IN ({$idsStr})")->group('group_id')->getField("group_id,COUNT(group_id) AS num");
		foreach ($groupList as &$val) {
			$val['num'] = $groupNum[$val['id']];
		}

		return $groupList;
	}


    public function getGroupRule($groupId=0) {



    }


	/**
	 * 添加用户到组
	 * @param $uid
	 * @param $gid
	 * @return bool
	 */
	public function addToGroup($uid,$gid){
		$uid = is_array($uid)?implode(',',$uid):trim($uid,',');
		$gid = is_array($gid)?$gid:explode( ',',trim($gid,',') );

		$Access = M(self::AUTH_GROUP_ACCESS);
		$del = $Access->where( array('uid'=>array('IN',$uid)) )->delete();

		$uid_arr = explode(',',$uid);
		$uid_arr = array_diff($uid_arr,array(C('USER_ADMINISTRATOR')));
		$add = array();
		if( $del!==false ){
			foreach ($uid_arr as $u){
				//判断用户id是否合法
				if(!M(self::MEMBER)->where('id='.$u)->getField('id')){
					$this->error = "编号为{$u}的用户不存在！";
					return false;
				}
				foreach ($gid as $g){
					if( is_numeric($u) && is_numeric($g) ){
						$add[] = array('group_id'=>$g,'uid'=>$u);
					}
				}
			}
			$Access->addAll($add);
		}
		if ($Access->getDbError()) {
			if( count($uid_arr)==1 && count($gid)==1 ){
				//单个添加时定制错误提示
				$this->error = "不能重复添加";
			}
			return false;
		}else{
			return true;
		}
	}


	/**
	 * 检查id是否全部存在
	 * @param array|string $gid  用户组id列表
	 */
	public function checkId($mid,$msg = '以下id不存在:'){
		if(is_array($mid)){
			$count = count($mid);
			$ids   = implode(',',$mid);
		}else{
			$mid   = explode(',',$mid);
			$count = count($mid);
			$ids   = $mid;
		}

		$s = M(self::AUTH_GROUP)->where(array('id'=>array('IN',$ids)))->getField('id',true);
		if(count($s)===$count){
			return true;
		}else{
			$diff = implode(',',array_diff($mid,$s));
			$this->error = $msg.$diff;
			return false;
		}
	}


	/**
	 * 获取角色相应的用户
	 * @param $groupId
	 * @return mixed
	 */
	public function getGroupUser($groupId){
		$groupId = intval($groupId);
		$ret = M(self::AUTH_GROUP_ACCESS)
			->field('bu.id,bu.real_name AS name')
			->alias('aga')
			->join('boss_user as bu ON aga.uid=bu.id')
			->where('aga.group_id='.$groupId)
			->select();

		return $ret;
	}


	/**
	 * 获取角色相应的用户 （关联数组  ）
	 * @param $groupId
	 * @return mixed
	 */
	public function getGroupUserAsso($groupId){
		$groupId = intval($groupId);
		$ret = M(self::AUTH_GROUP_ACCESS)
			->alias('aga')
			->join('boss_user as bu ON aga.uid=bu.id')
			->where('aga.group_id='.$groupId)
			->getField('bu.id,bu.real_name AS name');

		return $ret;
	}


}

