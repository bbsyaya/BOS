<?php
namespace Home\Model;
use Think\Model;
use Common\Service;
class DaydataModel extends Model {
	public function getlistdata($where){
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$Daydata=M('Daydata');
		return $Daydata->field('a.id,a.adddate,a.jfid,a.newdata as datanum,a.newmoney as money,b.name as jfname,a.price,b.charging_mode as jftype,a.status,b.charging_mode,d.name as advname,c.name as comname,e.name as linename,f.name as jszt,g.real_name,a.lineid')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.comid=c.id join boss_advertiser d on a.adverid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.ztid=f.id join boss_user g on a.salerid=g.id')->where($where)->limit($str.',10')->select();
	}
	public function getldlist($where){
		$data = $this->field('a.id')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.comid=c.id join boss_advertiser d on a.adverid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.ztid=f.id join boss_user g on a.salerid=g.id')->where($where)->select();
		foreach ($data as $k => $v) {
			$idlist[]=$v['id'];
		}
		return implode(',',$idlist);
	}
	public function getonedata($where=''){
		$M=M('Daydata');
		$res=$M->where($where)->find();
		return $res;
	}
	public function getlistcount($where){
		return M('Daydata')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.comid=c.id join boss_advertiser d on a.adverid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.ztid=f.id join boss_user g on a.salerid=g.id')->where($where)->count();
	}
	public function edit($where='',$data=array()){
		$res=M('Daydata')->where($where)->save($data);
		return $res;
	}
	public function adddata($data){
		$res=M('Daydata')->add($data);
		return $res;
	}
	public function getdata($where=''){
		$res=M('Daydata')->where($where)->select();
		return $res;
	}
	public function getnum($where=''){
		$res=M('Daydata')->where($where)->count();
		return $res;
	}
	public function deldata($where=''){
		$res=M('Daydata')->where($where)->delete();
		return $res;
	}
	public function getSetDataList($where){
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$Daydata=M('Daydata');
		return $Daydata->field('min(a.adddate) as start_date,max(a.adddate) as end_date,a.comid,c.name as comname,d.name as advername,concat(min(a.adddate),"-",max(a.adddate)) as date,sum(a.datanum) as ysdata,sum(ifnull(a.newdata,a.datanum)) as enddata,sum(a.money) as ysmoney,sum(ifnull(a.newmoney,a.money)) as endmoney,g.real_name as username,f.name as jszt,if(count(a.status)=sum(a.status),"","部分时间段数据已进入结算流程")as error')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.comid=c.id join boss_advertiser d on a.adverid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.ztid=f.id join boss_user g on a.salerid=g.id')->where($where)->group('a.comid')->limit($str.',10')->select();
	}
	public function getSetDataListcount($where){
		$Daydata=M('Daydata');
		return $Daydata->field('count(distinct a.comid) as num')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.comid=c.id join boss_advertiser d on a.adverid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.ztid=f.id join boss_user g on a.salerid=g.id join boss_company_bankaccount h on c.receivables_aid=h.id')->where($where)->find();
	}
	public function getMakeSettlementInData($strtime,$endtime,$comidarr,$jfid){//根据开始结束时间和产品ID字符串返回按产品分组的统计数据 2017.01.10改成按计费标识分组
		$Daydata=M('Daydata');
		$wheres[]="a.adddate >= '$strtime' && a.adddate <= '$endtime'";
		$wheres[]="c.id in ($comidarr) && a.jfid in ($jfid) && a.status!=9";
		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		return $Daydata->field('c.name as comname,b.price as price,c.id as comid,d.id as advid,d.name as advername,min(a.adddate) as strtime,max(a.adddate) as endtime,sum(ifnull(a.newdata,a.datanum)) as enddata,b.charging_mode,sum(ifnull(a.newmoney,a.money)) as endmoney,f.name as qdzt,f.id as qdztid,g.real_name as username,g.id as userid,e.name as linename,b.name as jfname,b.id as jfid,a.lineid')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.comid=c.id join boss_advertiser d on a.adverid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.ztid=f.id join boss_user g on a.salerid=g.id')->where($where)->group('a.adverid,a.lineid,a.salerid,a.jfid')->select();
	}
	public function getMakeSettlementInData2($strtime,$endtime,$adverid,$salerid,$lineid,$allcomid,$alljfid){
		$Daydata=M('Daydata');
		if($alljfid!='')$where="a.adddate >= '$strtime' && a.adddate <= '$endtime' && a.adverid=$adverid && a.lineid=$lineid && a.salerid=$salerid && b.id in ($alljfid) && a.status>=2 && a.status!=9";
		else $where="a.adddate >= '$strtime' && a.adddate <= '$endtime' && a.adverid=$adverid && a.lineid=$lineid && a.salerid=$salerid && c.id in ($allcomid) && a.status>=2 && a.status!=9";
		return $Daydata->field('c.name as comname,b.price as price,c.id as comid,d.id as advid,d.name as advername,min(a.adddate) as strtime,max(a.adddate) as endtime,sum(if(a.status=9,0,a.newdata)) as enddata,b.charging_mode,sum(if(a.status=9,0,a.newmoney)) as endmoney,f.name as qdzt,f.id as qdztid,g.real_name as username,g.id as userid,e.name as linename,b.name as jfname,b.id as jfid')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.comid=c.id join boss_advertiser d on a.adverid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.ztid=f.id join boss_user g on a.salerid=g.id')->where($where)->group('a.jfid')->select();
	}
	public function getMakeSettlementInData3($alldataid){
		return $this->field('c.name as comname,b.price as price,c.id as comid,d.id as advid,d.name as advername,min(a.adddate) as strtime,max(a.adddate) as endtime,sum(ifnull(a.newdata,a.datanum)) as enddata,b.charging_mode,sum(ifnull(a.newmoney,a.money)) as endmoney,f.name as qdzt,f.id as qdztid,g.real_name as username,g.id as userid,e.name as linename,b.name as jfname,b.id as jfid')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.comid=c.id join boss_advertiser d on a.adverid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.ztid=f.id join boss_user g on a.salerid=g.id')->where("a.id in ($alldataid)")->group('a.jfid')->select();
	}
	public function editdataforcom($advid,$salerid,$lineid,$strtime,$endtime,$allcomid='',$alljfid=''){
		if($alljfid!='')$where=" && a.jfid in ($alljfid)";
		return M('Daydata')->field('a.id')->join('a join boss_charging_logo b on a.jfid=b.id')->where("a.adverid = $advid && a.salerid=$salerid && a.lineid=$lineid && a.adddate>='$strtime' && a.adddate<='$endtime' && a.comid in ($allcomid) && a.status!=9 $where")->select();
	}
	public function getnoauditdata($where){//查询时间段时所有未进入结算的数据
        return M('daydata')->field('jfid')->where($where)->group('jfid')->select();
    }
    public function getPerformanceStatisticsList(){//获取销售业绩曲线
    	$year=(!empty(I('get.year')))?I('get.year'):date('Y');
    	$month=(!empty(I('get.month')))?I('get.month'):date('m');
    	if(strlen($month)==1)$month='0'.$month;
    	if(!empty(I('get.bumen')))$where="&& e.dept_id=".I('get.bumen');
    	else $where='';

    	//数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where.= " && $myrule_data";

    	$list = $this->field('e.real_name,d.postrule,ifnull(f.target,0) as target,sum(a.newmoney) as inmoney,ROUND(sum(if(a.status>2 && a.status!=9,a.newmoney,0))/sum(a.newmoney)*100,2) as kaipiaolv,ROUND(sum(if(a.status=4,a.newmoney,0))/sum(a.newmoney)*100,2) as huikuanlv,ROUND(sum(a.newmoney)/f.target*100,2) as yejiwanchenlv,d.rule,d.in_num,d.`out_num`,a.`salerid`')->join('a join boss_auth_group_access b on a.salerid=b.uid join boss_auth_group c on b.group_id=c.id left join boss_userrule d on a.salerid=d.uid join boss_user e on a.salerid=e.id left join boss_target f on a.salerid=f.uid && f.month=replace(left(a.adddate,7),"-","")')->where("a.adddate like '$year-$month%' $where")->group('a.salerid')->select();

    	if(I("showsql")=="showsql023"){
    		print_r($this->getLastsql());exit;
    	}
    	return $list;
    }
    public function getgongxian(){//销售贡献
    	//数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where= " && $myrule_data";
    	return $this->field('sum(a.newmoney) as inmoney,b.real_name,left(a.adddate,7) as month')->join('a join boss_user b on a.salerid=b.id')->where('a.adddate>="'.date('Y-m',time()-3600*24*365).'"'.$where)->group("a.salerid,left(a.adddate,7)")->select();
    }
    public function getalldata($where){//获取总数据
		return $this->field('sum(a.newdata) as allnum,sum(a.newmoney) as allmoney')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.comid=c.id join boss_advertiser d on a.adverid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.ztid=f.id join boss_user g on a.salerid=g.id')->where($where)->find();
    }
    public function postDatatoFenfafroid($str){
    	$data=$this->field('replace(adddate,"-","") as adddate,jfid,datanum,newdata,money,newmoney,status,price,comid,id')->where("id in ($str)")->select();
    	return postDatatoFenfa($data);
    }
    public function postStatustoFenfafroid($str){

			$data=$this->field('replace(adddate,"-","") as adddate,jfid,id,status,lineid')->where("id in ($str)")->select();
			if($data[0]['lineid']!=1){
	    		return true;
	    	}
	    	$res=postStatustoFenfa($data);

    	return $res;
    }
    public function getOrtherSuperData(){
    	$year=(!empty(I('get.year')))?I('get.year'):date('Y');
    	$month=(!empty(I('get.month')))?I('get.month'):date('m');
    	if(strlen($month)==1)$month='0'.$month;
    	$data=$this->field('a.adddate,a.jfid,a.newmoney,a.lineid')->join('a left join boss_daydata_out b on a.jfid=b.jfid && a.adddate=b.adddate')->where("a.adddate like '$year-$month%' && b.id is null")->order('a.adddate desc')->select();
    	foreach ($data as $key => $value) {
    		$newdata[$value['jfid'].str_replace('-','',$value['adddate'])]=array($value['newmoney'],$value['lineid']);
    	}
    	foreach ($newdata as $key => $v) {
    		if(!empty($newdata[$key-1])){
    			$newdata[$key-1][0]=$v[0]+$newdata[$key-1][0];
    			unset($newdata[$key]);
    		}
    	}
    	foreach ($newdata as $k => $v) {
    		if($v[0]==0){
    			unset($newdata[$k]);
    		}
    	}
    	foreach ($newdata as $key => $value) {
    		$jfid=substr($key,0,-8);
    		$str=substr($key,-8);
    		$time=substr($str,0,4).'-'.substr($str,4,2).'-'.substr($str,-2);
    		$res=M('daydata_out')->where("jfid=$jfid && adddate<='$time'")->order('adddate desc')->find();
    		$return[]=array('userid'=>$res['businessid'],'money'=>$value[0],'lineid'=>$value[1],'jfid'=>$jfid);
    	}
    	return $return;
    }
}
