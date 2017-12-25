<?php
namespace Home\Model;
use Think\Model;
class DaydataInandoutModel extends Model {
    public function getlinelv(){
    	//不存在内部交易的部分
    	$wheres[]="adddate>='".I('get.strtime')."'";
    	$wheres[]="adddate<='".I('get.endtime')."'";
    	$wheres['s']="in_lineid>0 && (in_lineid=out_lineid || out_lineid is null || out_status=9) && in_status!=0 && in_status!=9";
 		$res_in_no = $this->field("sum(ifnull(in_newmoney,0)) as indata,in_lineid")->where(implode(' && ', $wheres))->group('in_lineid')->select();
 		$wheres['s']="out_lineid>0 && (in_lineid=out_lineid || in_lineid is null || in_status=9) && out_status!=0 && out_status!=9";
 		$res_out_no = $this->field("sum(ifnull(out_newmoney,0)) as outdata,out_lineid")->where(implode(' && ', $wheres))->group('out_lineid')->select();
        
 		//存在内部交易的部分
 		$wheres['s']="in_lineid>0 && out_lineid>0 && in_lineid!=out_lineid && in_status!=9 && out_status!=9 && in_status!=0";
 		$res_in_yes = $this->field("sum(a.in_newmoney)*(1-b.in_settlement_prate) as indata,in_lineid,out_lineid")->join("a join boss_charging_logo_assign b on a.jfid=b.cl_id && a.adddate>=b.promotion_stime && if(b.promotion_etime is null,1,a.adddate<=b.promotion_etime)")->where(implode(' && ', $wheres))->group('in_lineid,out_lineid')->select();
 		return array('in_no'=>$res_in_no,'out_no'=>$res_out_no,'in_yes'=>$res_in_yes);
    }
    public function getqushidata(){
    	$wheres[]="adddate>='".I('get.strtime')."'";
    	$wheres[]="adddate<='".I('get.endtime')."'";
    	$time_s=I('get.strtime');
    	$time_e=I('get.endtime');
    	if((strtotime($time_e)-strtotime($time_s))/(3600*24)<60)$group='adddate';
    	else $group="left(adddate,7)";
    	if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
			$linearr=implode(',',I('get.lineid'));
				$file="sum(if(b.in_lineid not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)*(1-d.in_settlement_prate),if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))) as indata,sum(if(b.out_lineid not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)*(1-d.in_settlement_prate),if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney))) as outdata,$group as date";
				$join="b left join boss_charging_logo_assign d on d.cl_id=b.jfid && d.promotion_stime<=b.adddate && if(d.promotion_etime is null,1,d.promotion_etime>=b.adddate)";
				$wheres[]="(b.in_lineid in ($linearr) || b.out_lineid in ($linearr))";
			
		}else{
			$file="sum(if(in_status is null || in_status = 0 || in_status = 9,0,in_newmoney)) as indata,sum(if(out_status is null || out_status = 0 || out_status = 9,0,out_newmoney)) as outdata,$group as date";
			$join='';
		}
		$where=implode(' && ', $wheres);
    	$data=$this->field($file)->join($join)->where($where)->group($group)->select();
        
    	return $data;
    }
    public function getadvintop10(){
    	$wheres[]="adddate>='".I('get.strtime')."'";
    	$wheres[]="adddate<='".I('get.endtime')."'";
        if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
            $wheres[]="a.in_lineid in (".implode(',',I('get.lineid')).")";
        }
    	$where=implode(' && ', $wheres);
    	$data=$this->field("sum(a.in_newmoney) as money,b.name,b.id")->join("a join boss_advertiser b on a.in_adverid=b.id")->where($where)->group("a.in_adverid")->order("sum(a.in_newmoney) desc")->limit("0,10")->select();
    	return $data;
    }
    public function getadvlrtop10(){
    	$wheres[]="adddate>='".I('get.strtime')."'";
    	$wheres[]="adddate<='".I('get.endtime')."'";
        $wheres[]="adddate<='".I('get.endtime')."'";
        if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
            $wheres[]="a.in_lineid in (".implode(',',I('get.lineid')).")";
        }
    	$where=implode(' && ', $wheres);
    	$data=$this->field("sum(a.in_newmoney-ifnull(a.out_newmoney,0)) as money,b.name,b.id")->join("a join boss_advertiser b on a.in_adverid=b.id")->where($where)->group("a.in_adverid")->order("sum(a.in_newmoney-ifnull(a.out_newmoney,0)) desc")->limit("0,10")->select();
    	return $data;
    }
    public function getadvzztop10(){
    	$time1=I('get.strtime');
    	$time2=I('get.endtime');
    	$time3=date('Y-m-d',strtotime($time1)*2-3600*24-strtotime($time2));
    	$wheres[]="adddate>='$time3'";
    	$wheres[]="adddate<='".I('get.endtime')."'";
        $wheres[]="adddate<='".I('get.endtime')."'";
        if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
            $wheres[]="a.in_lineid in (".implode(',',I('get.lineid')).")";
        }
    	$where=implode(' && ', $wheres);
    	$data=$this->field("sum(if(a.adddate>='$time1',a.in_newmoney-ifnull(a.out_newmoney,0),0))/sum(if(a.adddate<'$time1',a.in_newmoney-ifnull(a.out_newmoney,0),0))-1 as data,b.name,b.id")->join("a join boss_advertiser b on a.in_adverid=b.id")->where($where)->group("a.in_adverid")->order("sum(if(a.adddate>='$time1',a.in_newmoney-ifnull(a.out_newmoney,0),0))/sum(if(a.adddate<'$time1',a.in_newmoney-ifnull(a.out_newmoney,0),0)) desc")->limit("0,10")->select();
        foreach ($data as $k => $v) {
            $data[$k]['data']=twonum($v['data']*100).'%';
        }
    	return $data;
    }
    public function getsupouttop10(){
    	$wheres[]="adddate>='".I('get.strtime')."'";
    	$wheres[]="adddate<='".I('get.endtime')."'";
        $wheres[]="adddate<='".I('get.endtime')."'";
        $wheres[]="(b.type=1 || b.fukuanname !='')";
        if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
            $wheres[]="a.out_lineid in (".implode(',',I('get.lineid')).")";
        }
    	$where=implode(' && ', $wheres);
    	$data=$this->field("sum(a.out_newmoney) as money,if(b.fukuanname is null || b.fukuanname = '',b.name,b.fukuanname) as name,b.id")->join("a join boss_supplier b on a.out_superid=b.id")->where($where)->group("if(b.fukuanname is null || b.fukuanname = '',b.name,b.fukuanname)")->order("sum(a.out_newmoney) desc")->limit("0,10")->select();
    	return $data;
    }
    public function getsuplrtop10(){
    	$wheres[]="adddate>='".I('get.strtime')."'";
    	$wheres[]="adddate<='".I('get.endtime')."'";
        $wheres[]="adddate<='".I('get.endtime')."'";
        $wheres[]="(b.type=1 || b.fukuanname !='')";
        if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
            $wheres[]="a.out_lineid in (".implode(',',I('get.lineid')).")";
        }
    	$where=implode(' && ', $wheres);
    	$data=$this->field("sum(ifnull(a.in_newmoney,0)-a.out_newmoney) as money,if(b.fukuanname is null || b.fukuanname = '',b.name,b.fukuanname) as name,b.id")->join("a join boss_supplier b on a.out_superid=b.id")->where($where)->group("if(b.fukuanname is null || b.fukuanname = '',b.name,b.fukuanname)")->order("sum(ifnull(a.in_newmoney,0)-a.out_newmoney) desc")->limit("0,10")->select();
    	return $data;
    }
    public function getsupzztop10(){
    	$time1=I('get.strtime');
    	$time2=I('get.endtime');
    	$time3=date('Y-m-d',strtotime($time1)*2-3600*24-strtotime($time2));
    	$wheres[]="adddate>='$time3'";
    	$wheres[]="adddate<='".I('get.endtime')."'";
        $wheres[]="adddate<='".I('get.endtime')."'";
        $wheres[]="(b.type=1 || b.fukuanname !='')";
        if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
            $wheres[]="a.out_lineid in (".implode(',',I('get.lineid')).")";
        }
    	$where=implode(' && ', $wheres);
    	$data=$this->field("sum(if(a.adddate>='$time1',a.in_newmoney-ifnull(a.out_newmoney,0),0))/sum(if(a.adddate<'$time1',a.in_newmoney-ifnull(a.out_newmoney,0),0))-1 as data,if(b.fukuanname is null || b.fukuanname = '',b.name,b.fukuanname) as name,b.id")->join("a join boss_supplier b on a.out_superid=b.id")->where($where)->group("if(b.fukuanname is null || b.fukuanname = '',b.name,b.fukuanname)")->order("sum(if(a.adddate>='$time1',a.in_newmoney-ifnull(a.out_newmoney,0),0))/sum(if(a.adddate<'$time1',a.in_newmoney-ifnull(a.out_newmoney,0),0)) desc")->limit("0,10")->select();
        foreach ($data as $k => $v) {
            $data[$k]['data']=twonum($v['data']*100).'%';
        }
    	return $data;
    }
}