<?php
namespace Home\Model;
use Think\Model;
use Common\Service;
class DaydataOutModel extends Model {
	public function getdata($where){
		$M=M('daydata_out');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where){
		$M=M('daydata_out');
		$res=$M->where($where)->find();
		return $res;
	}
	public function adddata($data){
		$res=M('daydata_out')->add($data);
		return $res;
	}
	public function deldata($where=''){
		$res=M('daydata_out')->where($where)->delete();
		return $res;
	}
	public function getSetDataListcount($where){
		$Daydata=M('daydata_out');
		return $Daydata->field('count(distinct a.jfid) as num')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.prot_id=c.id join boss_supplier d on a.superid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.sbid=f.id join boss_user g on a.businessid=g.id')->where($where)->find();
	}
	public function getSetDataList($where,$p){
		$Daydata=M('daydata_out');
		return $Daydata->field('b.id as id,b.name as jfname,b.id as jfid,c.name as comname,d.name as advername,concat(min(a.adddate),"-",max(a.adddate)) as date,sum(a.datanum) as ysdata,sum(ifnull(a.newdata,a.datanum)) as enddata,sum(a.money) as ysmoney,sum(ifnull(a.newmoney,a.money)) as endmoney,f.name as qdzt,g.real_name as username,if(count(a.status)=sum(a.status),"","部分时间段数据已进入结算流程")as error')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.prot_id=c.id join boss_supplier d on a.superid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.sbid=f.id join boss_user g on a.businessid=g.id')->where($where)->group('a.jfid')->select();
	}
	public function getMakeSettlementOutData($strtime,$endtime,$jfidarr,$superid='',$ztid=''){
		$Daydata=M('daydata_out');
		$wheres[]="a.adddate >= '$strtime' && a.adddate <= '$endtime'";
		$wheres[]="a.jfid in ($jfidarr) && a.status=1";
		if(!empty($superid))$wheres[]="a.superid in ($superid)";
		if(!empty($ztid))$wheres[]="a.sbid in ($ztid)";
		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		return $Daydata->field('b.id as jfid,b.name as jfname,c.name as comname,d.id as advid,d.name as advername,min(a.adddate) as strtime,max(a.adddate) as endtime,sum(ifnull(a.newmoney,a.money)) as endmoney,f.name as qdzt,f.id as qdztid,g.real_name as username,g.id as userid,e.name as linename,a.lineid')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.prot_id=c.id join boss_supplier d  on a.superid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.sbid=f.id join boss_user g on a.businessid=g.id')->where($where)->group('a.superid,a.sbid,a.jfid,a.businessid')->select();
	}
	public function getMakeSettlementOutData2($strtime,$endtime,$jfidarr,$alldataid=''){
		$Daydata=M('daydata_out');
		$where="a.adddate >= '$strtime' && a.adddate <= '$endtime' && a.jfid in ($jfidarr) && a.status>=2";
		if($alldataid!='')$where.=" && a.id in (".$alldataid.")";
		return $Daydata->field('b.id as jfid,b.name as jfname,c.name as comname,d.id as advid,d.name as advername,min(a.adddate) as strtime,max(a.adddate) as endtime,sum(ifnull(a.newmoney,a.money)) as endmoney,f.name as qdzt,f.id as qdztid,g.real_name as username,g.id as userid,e.name as linename,sum(if(i.status=9,0,i.newmoney)) as inmoney,(sum(i.newmoney)-sum(a.newmoney))/sum(i.newmoney)*100 as lirun,group_concat(i.status) as instatus')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.prot_id=c.id join boss_supplier d on a.superid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.sbid=f.id join boss_user g on a.businessid=g.id left join boss_daydata i on a.jfid=i.jfid && a.adddate=i.adddate')->where($where)->group('a.jfid')->select();
	}
	public function getMakeSettlementOutData3($strtime,$endtime,$superid,$businessid,$sbid,$jfidarr){
		$Daydata=M('daydata_out');
		$where="a.adddate >= '$strtime' && a.adddate <= '$endtime' && a.superid in ($superid) && a.businessid in ($businessid) && a.sbid=$sbid && a.jfid in ($jfidarr) && a.status>=2";
		return $Daydata->field('b.id as jfid,b.name as jfname,c.name as comname,d.id as advid,d.name as advername,min(a.adddate) as strtime,max(a.adddate) as endtime,sum(ifnull(a.newmoney,a.money)) as endmoney,f.name as qdzt,f.id as qdztid,g.real_name as username,g.id as userid,e.name as linename,sum(i.newmoney) as inmoney,(sum(i.newmoney)-sum(a.newmoney))/sum(i.newmoney)*100 as lirun,group_concat(i.status) as instatus')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.prot_id=c.id join boss_supplier d on a.superid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.sbid=f.id join boss_user g on a.businessid=g.id left join boss_daydata i on a.jfid=i.jfid && a.adddate=i.adddate')->where($where)->group('a.jfid')->select();
	}
	public function editdataforcom($supid,$sangwuid,$lineid,$strtime,$endtime,$alljfid=''){
		if($alljfid)$where=" && jfid in ($alljfid)";
		else $where='';
		return M('daydata_out')->field('id,jfid,adddate,lineid')->where("status!=9 && superid in ($supid) && businessid in ($sangwuid) && lineid=$lineid && adddate>='$strtime' && adddate<='$endtime'".$where)->select();
	}
	public function edit($where='',$data=array()){
		$res=M('daydata_out')->where($where)->save($data);
		return $res;
	}
	public function getPerformanceStatisticsList(){//获取商务业绩曲线
		$year=(!empty(I('get.year')))?I('get.year'):date('Y');
    	$month=(!empty(I('get.month')))?I('get.month'):date('m');
    	if(strlen($month)==1)$month='0'.$month;

    	if(!empty(I('get.bumen')))$where=" && a.money >0 && e.dept_id=".I('get.bumen');
    	else $where='';

    	//数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where.= " && $myrule_data";


    	return $this->field('e.id as userid,e.real_name,d.postrule,ifnull(f.target,0) as target,sum(h.newmoney)-sum(a.newmoney) as maoli,sum(h.newmoney) as inmoney,sum(a.newmoney) as outmoney,ROUND(sum(if(a.status>2,a.newmoney,0))/sum(a.newmoney)*100,2) as qurenlv,d.rule')->join('a join boss_auth_group_access b on a.businessid=b.uid join boss_auth_group c on b.group_id=c.id left join boss_userrule d on a.businessid=d.uid join boss_user e on a.businessid=e.id left join boss_target f on a.businessid=f.uid && f.month=replace(left(a.adddate,7),"-","") join boss_daydata h on a.jfid=h.jfid && h.adddate=a.adddate')->where("h.adddate like '$year-$month%' && a.adddate like '$year-$month%' $where")->group('a.businessid')->select();
	}
	public function getgongxian(){//商务贡献
		//数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where= " && $myrule_data";
    	return $this->field('sum(a.newmoney) as inmoney,b.real_name,left(a.adddate,7) as month')->join('a left join boss_user b on a.businessid=b.id and a.businessid >0')->where('a.adddate>="'.date('Y-m',time()-3600*24*365).'"'.$where)->group("a.businessid,left(a.adddate,7)")->select();
    }

	public function postDatatoFenfafroid($str){
		$data=$this->field('replace(adddate,"-","") as adddate,jfid,datanum,newdata,money,newmoney,status,price,superid')->where("id in ($str)")->select();
		return postDatatoFenfa($data);
	}

}