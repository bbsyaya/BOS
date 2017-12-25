<?php
namespace Home\Model;
use Think\Model;
class DkrecordModel extends Model {

	public function edit($where='',$data=array()){
		$res=M('dkrecord')->where($where)->save($data);
		return $res;
	}
	public function adddata($data){
		$res=M('dkrecord')->add($data);
		return $res;
	}
	public function getdata($where=''){
		$res=M('dkrecord')->where($where)->select();
		return $res;
	}
	public function getnum($where=''){
		$res=M('dkrecord')->where($where)->count();
		return $res;
	}
	public function deldata($where=''){
		$res=M('dkrecord')->where($where)->delete();
		return $res;
	}
	public function getdataforadvid($id){
		return M('dkrecord')->field('a.*,b.real_name,c.name')->join('a join boss_user b on a.dkerid=b.id join boss_user_department c on b.dept_id=c.id')->where("a.adverid=$id")->select();
	}
	public function getdataforsettlementid($id){
		return $this->field('a.time,a.money,b.real_name,e.name as deptname,a.id,"核销" as adddate')->join('a join boss_user b on a.dkerid=b.id join boss_settlement_in d on a.skjsdid=d.id join boss_user_department e on b.dept_id=e.id')->where('a.skjsdid='.$id)->select();
	}
}
