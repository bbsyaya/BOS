<?php
namespace Home\Model;
use Think\Model;
class RkrecordModel extends Model {

	public function edit($where='',$data=array()){
		$res=M('rkrecord')->where($where)->save($data);
		return $res;
	}
	public function adddata($data){
		$res=M('rkrecord')->add($data);
		return $res;
	}
	public function getdata($where=''){
		$res=M('rkrecord')->where($where)->select();
		return $res;
	}
	public function getnum($where=''){
		$res=M('rkrecord')->where($where)->count();
		return $res;
	}
	public function deldata($where=''){
		$res=M('rkrecord')->where($where)->delete();
		return $res;
	}
	public function getdataforsettlementid($id){
		return M('rkrecord')->field('a.time,a.money,b.real_name,e.name as deptname,a.id,c.adddate')->join('a join boss_user b on a.rkerid=b.id join boss_pay c on a.payid=c.id join boss_settlement_in d on a.skjsdid=d.id join boss_user_department e on b.dept_id=e.id')->where('a.type=1 && a.skjsdid='.$id)->select();
	}
	public function getdataforadvid($id){
		return M('rkrecord')->field('a.time,a.money,b.real_name,a.id,c.adddate')->join('a join boss_user b on a.rkerid=b.id join boss_pay c on a.payid=c.id')->where('a.type=2 && a.skjsdid='.$id)->select();
	}
}
