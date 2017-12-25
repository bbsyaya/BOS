<?php
namespace Home\Model;
use Think\Model;
class ChargingLogoAssignModel extends Model {
	public function getdata($where){
		$M=M('charging_logo_assign');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where){
		$M=M('charging_logo_assign');
		$res=$M->where($where)->find();
		return $res;
	}
	public function adddata($data){
		$res=M('charging_logo_assign')->add($data);
		return $res;
	}
	public function getdataforjfid($jfid,$time){
		$M=M('charging_logo_assign');
		$res=$M->where("cl_id=$jfid && ((status=1 && promotion_stime<='$time') || (status=0 && promotion_stime<='$time' && promotion_etime>='$time'))")->select();
		return $res;
	}
	public function getbankdataforjfid($id){
		return M('charging_logo_assign')->field('b.name as jszt,a.bl_id')->join('a join boss_data_dic b on a.sb_id=b.id')->where("a.cl_id=$id && a.status=1")->find();
	}
	/*
	public function getdatainfoforjfid($id){//根据计费标识ID查相关产品、广告主、销售数据
		return $this->field('a.*,b.saler_id,b.bl_id,b.sb_id')->join('a join boss_product b on a.prot_id=b.id')->where('a.id='.$id)->find();
	}
	*/
}