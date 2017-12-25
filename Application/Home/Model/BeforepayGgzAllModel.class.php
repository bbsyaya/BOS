<?php
namespace Home\Model;
use Think\Model;
class BeforepayGgzAllModel extends Model {
	public function getlist($type=0){
		if(!empty(I('get.advername')))$wheres[]="b.name like '%".I('get.advername')."%'";
		if(!empty(I('get.status')) && I('get.status')==2)$wheres[]="a.dhxmoney=0";
		if(!empty(I('get.status')) && I('get.status')==1)$wheres[]="a.dhxmoney!=0";
		$where=implode(' && ',$wheres);
		if($type==1)$data=M('beforepay_ggz_all')->field('a.*,b.name')->join('a join boss_advertiser b on a.adverid=b.id')->where($where)->select();
		else $data=M('beforepay_ggz_all')->field('a.*,b.name')->join('a join boss_advertiser b on a.adverid=b.id')->where($where)->select();
		return $data;
	}
	public function getBcount(){
		if(!empty(I('get.advername')))$wheres[]="b.name like '%".I('get.advername')."%'";
		if(!empty(I('get.status')) && I('get.status')==2)$wheres[]="a.dhxmoney=0";
		if(!empty(I('get.status')) && I('get.status')==1)$wheres[]="a.dhxmoney!=0";
		$where=implode(' && ',$wheres);
		return $this->join('a join boss_advertiser b on a.adverid=b.id')->where($where)->count();
	}
	public function edit($where='',$data=array()){
		$res=M('beforepay_ggz_all')->where($where)->save($data);
		return $res;
	}
	public function adddata($data){
		$res=M('beforepay_ggz_all')->add($data);
		return $res;
	}
	public function getdata($where=''){
		$res=M('beforepay_ggz_all')->where($where)->select();
		return $res;
	}
	public function getnum($where=''){
		$res=M('beforepay_ggz_all')->where($where)->count();
		return $res;
	}
	public function getonedata($where){
		$M=M('beforepay_ggz_all');
		$res=$M->where($where)->find();
		return $res;
	}
}