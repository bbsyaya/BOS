<?php
namespace Home\Model;
use Think\Model;
class UserruleModel extends Model {
	public function adddata($data){
		$res=$this->add($data);
		return $res;
	}
	public function getnum($where=''){
		$res=$this->where($where)->count();
		return $res;
	}
	public function getonedata($where){
		$res=$this->where($where)->find();
		return $res;
	}
	public function edit($where='',$data=array()){
		$res=$this->where($where)->save($data);
		return $res;
	}
	public function deldata($where=""){
		return $this->where($where)->delete();
	}
	public function getdatalist(){//部门提成规则设置列表
		if(!empty(I('get.groupid')))$where="a.uid=".I('get.groupid')." && a.usertype=1";
		else $where="a.usertype=1";
		return $this->field('a.*,b.name')->join('a join boss_user_department b on a.groupid=b.id')->where($where)->select();
	}
	public function getgrouplist($where){//获取部门列表
		$list = $this->join('a join boss_user_department b on a.groupid=b.id')->where($where)->select();
		return $list;
	}
	public function getgrouplistforone($where){//个人而面获取部门列表
		$list = $this->join('a join boss_user_department b on a.groupid=b.id')->where($where)->group('b.id')->select();
		return $list;
	}
	public function getusername(){//获取名字列表
		return $this->join('a join boss_user b on a.uid=b.id')->where($where)->group('b.id')->select();
	}
	public function getonedatalist(){//个人提成规则设置列表
		$wheres[]="a.usertype=2";
		if(!empty(I('get.groupid')))$wheres[]="a.groupid=".I('get.groupid')."";
		if(!empty(I('get.upid')))$wheres[]="a.uid=".I('get.groupid')."";
		$where=implode(' && ',$wheres);
		return $this->field('a.*,b.real_name,c.name,d.title as posttype')->join('a join boss_user b on a.uid=b.id join boss_user_department c on b.dept_id=c.id join boss_auth_group_access cc on a.uid=cc.uid join boss_auth_group d on cc.group_id=d.id')->where($where)->select();
	}

}