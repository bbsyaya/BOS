<?php
namespace Home\Model;
use Think\Model;
class BeforepayGgzModel extends Model {
	public function edit($where='',$data=array()){
		$res=M('beforepay_ggz')->where($where)->save($data);
		return $res;
	}
	public function adddata($data){
		$res=M('beforepay_ggz')->add($data);
		return $res;
	}
	public function getdata($where=''){
		$res=M('beforepay_ggz')->where($where)->select();
		return $res;
	}
	public function getnum($where=''){
		$res=M('beforepay_ggz')->where($where)->count();
		return $res;
	}
}