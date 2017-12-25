<?php
namespace Home\Model;
use Think\Model;
use Common\Service;
class TargetModel extends Model {
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
	public function getdata($where){
		$res=$this->where($where)->select();
		return $res;
	}
	public function edit($where='',$data=array()){
		$res=$this->where($where)->save($data);
		return $res;
	}
	public function deldata($where=""){
		return $this->where($where)->delete();
	}
	public function getdatalist(){
		
		if(!empty(I('get.year'))){
			$year=I('get.year');
			if(!empty(I('get.month'))){
				$month=I('get.month');
				if(strlen($month)==1)$month='0'.$month;
			}else{
				$month='';
			}
			$wheres[]="a.month like '$year"."$month%'";
		} 
		
		
		if(!empty(I('get.uid')))$wheres[]="c.real_name like '%".I('get.uid')."%'";
		if(!empty(I('get.groupid')))$wheres[]="d.name like '%".I('get.groupid')."%'";

		//数据权限
        $arr_name=array();
        $arr_name['user']=array('c.id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;


		$where=implode(' && ',$wheres);
		return $this->field('a.*,c.id as uid,c.real_name,d.name as groupname,b.postrule,f.title,a.target')->join('a left join boss_userrule b on a.uid=b.uid join boss_user c on a.uid=c.id join boss_user_department d on c.dept_id=d.id join boss_auth_group_access e on e.uid=a.uid join boss_auth_group f on e.group_id=f.id')->where($where)->group('a.uid')->select();
		//echo $this->getlastSql();exit;
	}

	//ÇóºÍ
	public function getSum(){
		if(!empty(I('get.year'))){
			$year=I('get.year');
			if(!empty(I('get.month'))){
				$month=I('get.month');
				if(strlen($month)==1)$month='0'.$month;
			}else{
				$month='';
			}
			$wheres[]="a.month like '$year"."$month%'";
		}


		//if(!empty(I('get.uid')))$wheres[]="b.uid=".I('get.uid');
		//if(!empty(I('get.groupid')))$wheres[]="b.groupid=".I('get.groupid');
		if(!empty(I('get.uid')))$wheres[]="c.real_name like '%".I('get.uid')."%'";
		if(!empty(I('get.groupid')))$wheres[]="d.name like '%".I('get.groupid')."%'";
		$where=implode(' && ',$wheres);
		$getData =  $this->field('SUM(b.postrule) as postrule,SUM(a.target) as target')->join('a left join boss_userrule b on a.uid=b.uid join boss_user c on a.uid=c.id join boss_user_department d on c.dept_id=d.id join boss_auth_group_access e on e.uid=a.uid join boss_auth_group f on e.group_id=f.id')->where($where)->select();
		return $getData;
	}
}