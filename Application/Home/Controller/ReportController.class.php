<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Org\Util\PHPEXCEL;
use Common\Service;
class ReportController extends BaseController {//报表中心
	public function dayReport(){//日报
		$_GET['inandout']=1;
		$this->linelist=M('business_line')->field('name,id')->select();
		$this->comlist=M('product')->field('name,id')->select();
		$this->zt=M('data_dic')->field('name,id')->where("dic_type=4")->select();
		$this->adverlist=M('advertiser')->field('name,id')->where("ad_type>0")->select();
		$this->superlist=M('supplier')->field('name,id')->select();
		$this->userlist=M('user')->field('real_name,id')->select();
		$this->jflist=M('charging_logo')->field('name,id')->where("status=1")->select();
		//取出所有相关收入成本结算单

		$total=D('ChargingLogo')->getdaydatacount();

		$this->getpagelist($total['num']);
		$showdata=D('ChargingLogo')->getdaytable();

		$this->assign('data',$showdata);
		$this->alldatalist=D('ChargingLogo')->getalldata();

/*
		//7天流水
		$date7=date('Y-m-d',time()-3600*24*8);
		$data7=array();
		
		$res7=M('charging_logo')->field("b.adddate,sum(b.newmoney) as inmoney,sum(d.newmoney) as outmoney,sum(b.newmoney)-sum(d.newmoney) as lirun")->join('a join boss_daydata b on a.id=b.jfid join boss_daydata_out d on a.id=d.jfid && d.adddate=b.adddate')->where("b.adddate>='$date7' && b.adddate<='".date('Y-m-d',time()-3600*24)."'")->group("b.adddate")->order("b.adddate")->select();
		foreach ($res7 as $key => $value) {
			$res7_date[$value['adddate']]=$value;
		}
		for($str7=time()-3600*24*8;$str7<=time()-3600*24;$str7+=3600*24){
			$t=date('Y-m-d',$str7);
			if(!empty($res7_date[$t])){
				$data7['inmoney'][]=$res7_date[$t]['inmoney'];
				$data7['outmoney'][]=$res7_date[$t]['outmoney'];
				$data7['lirun'][]=$res7_date[$t]['lirun'];
				$data7['date'][]=$res7_date[$t]['adddate'];
			}else{
				$data7['inmoney'][]=0;
				$data7['outmoney'][]=0;
				$data7['lirun'][]=0;
				$data7['date'][]=$t;
			}
			
		}
		
		$this->assign('data7',$data7);

		//广告主收入流水TOP10
		$nowmonth=date('Y-m');
		$nowmonth="2016-10";
		$data_adver=array();
		$res_adver=M('daydata_inandout')->field("sum(a.out_newmoney) as outmoney,sum(a.in_newmoney) as inmoney,b.name as advname")->join("a join boss_advertiser b on a.in_adverid=b.id")->where("a.adddate like '$nowmonth%'$lineid")->group("a.in_adverid")->order("sum(a.in_newmoney) desc")->limit("0,10")->select();
		foreach ($res_adver as $key => $value) {
			$data_adver['advername'][]=$value['advname'];
			$data_adver['outmoney'][]=twonum($value['outmoney']);
			$data_adver['lirun'][]=twonum($value['inmoney']-$value['outmoney']);
		}
		$this->assign('data_adver',$data_adver);
		//供应商成本流水TOP10
		$data_super=array();

		$res_adver=M('daydata_inandout')->field("sum(a.in_newmoney) as inmoney,sum(a.out_newmoney) as outmoney,b.name as supername")->join("a join boss_supplier b on a.out_superid=b.id")->where("a.adddate like '$nowmonth%'$lineid")->group("a.out_superid")->order("sum(a.out_newmoney) desc")->limit("0,10")->select();
		foreach ($res_adver as $key => $value) {
			$data_super['supername'][]=$value['supername'];
			$data_super['outmoney'][]=twonum($value['outmoney']);
			$data_super['lirun'][]=twonum($value['inmoney']-$value['outmoney']);
		}
		$this->assign('data_super',$data_super);
		*/
		$this->display();
	}
	public function downloaddayreportlist(){//日报导出
		$_GET['inandout']=1;
		$showdata=D('ChargingLogo')->getdaytable(1);
		$list=array(array('adddate','日期'),array('bl_id','业务线'),array('comname','产品名称'),array('jfname','计费标识'),array('charging_mode','计费模式'),array('price','接入单价'),array('outprice','推广单价'),array('datanum','原始数据'),array('indata','收入金额'),array('outdata','成本金额'),array('profit','毛利润'),array('instatus','收入状态'),array('outstatus','成本状态'),array('inauditdate','收入确认时间'),array('outauditdate','成本确认时间'),array('invoicetime','开票时间'),array('nowskmoneytime','回款时间'),array('nowfkmoneytime','付款时间'),array('inhejianmoney','收入核检金额'),array('outhejianmoney','成本核检金额'),array('adverid','广告主'),array('superid','供应商'),array('logostatus','使用状态'),array('settle_cycle','收入结算周期'),array('settlement_cycle','成本结算周期'),array('injszt','收入主体'),array('outjszt','成本主体'),array('salerid','销售'),array('businessid','商务'),array('back_url','后台地址'),array('account','账号'),array('password','密码'),array('neibujiesuan','内部结算金额'));
        $this->downloadlist($showdata,$list,'日报表');
	}
	public function monthReport(){//月报
		$_GET['inandout']=1;
		$linelist=M('business_line')->field('id,name')->select();
		$this->assign('linelist',$linelist);
		$comlist=M('product')->field('id,name')->select();
		$this->assign('comlist',$comlist);
		$advlist=M('advertiser')->field('id,name')->where("ad_type>0")->select();
		$this->assign('advlist',$advlist);
		$suplist=M('supplier')->field('id,name')->select();
		$this->assign('suplist',$suplist);
		$jflist=M('charging_logo')->field('id,name')->where("status=1")->select();
		$this->assign('jflist',$jflist);
		$this->userlist=M('user')->field('real_name,id')->select();

		//控制前台日期显示
		$time_s = date('Y-m').'-01';
		$time_e = date('Y-m-d', strtotime("$time_s +1 month -1 day"));
		
		if(!empty(I('time_s')))$time_s = date("Y-m",strtotime(I('time_s'))).'-01';
		if(!empty(I('time_e')))$time_e = date('Y-m-d', strtotime(date("Y-m",strtotime(I('time_e'))).'-01'." +1 month -1 day"));//date("Y-m",strtotime(

		//只查询最近七天的数据
		if(trim(I("check7data"))){
			$time_s = date('Y-m-d',strtotime("-7days"));
			$time_e = date('Y-m-d',time());
		}

		$map["time_s"] = $time_s;
		$map["time_e"] = $time_e;
		$this->assign("map",$map);
		
		$total = D('ChargingLogo')->getmonthdatacount($time_s,$time_e);
		
		$this->getpagelist($total);
		$data = D('ChargingLogo')->getmonthtable($time_s,$time_e);

		$this->assign('data',$data['data']);

		$this->alldatalist=D('ChargingLogo')->getmonthalldata($time_s,$time_e);
		$showtablestr=$data['showtablestr'];
		if(bjalltj($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],$_SESSION['showtablestr']['Report/monthReport']['url']))$showtablestr=$_SESSION['showtablestr']['Report/monthReport']['str'];
		$this->assign('showtablestr',$showtablestr);


		//计费标识选择
		$jfbsid = I('jfid');
		if($jfbsid){
			$jfids = "";
			foreach ($jfbsid as $k => $v) {
				$jfids .= $v.",";
			}
			if($jfids){
				$jfids = substr($jfids,0,strlen($jfids)-1);
			}
			
			$jfids = empty($jfids)?"0":$jfids;
			$where = "id in ({$jfids})";
			$jfList = M("charging_logo")->field("id,name")->where($where)->select();
			if($jfList){
				$jf_str = "";
				foreach ($jfList as $k => $v) {
					$jf_str .= $v["name"].",";
				}
				if($jf_str){
					$jf_str = substr($jf_str,0,strlen($jf_str)-1);
				}
				$this->assign("jf_str",$jf_str);
			}
			
			$this->assign("nowjflist",$jfList);
		}
		
		/*
		$time=date('Y-m');
		//业务线收入占比
		$inmoneyforline=M('business_line')->field('sum(b.newmoney) as value,a.name')->join('a join boss_daydata b on b.lineid=a.id')->where("b.adddate like '$time%'")->group('a.id')->select();
		$inmoneyforline2=M('business_line')->field('sum(cc.newmoney*cb.in_settlement_prate) as value,a.name,sum(c.newmoney) as cb')->join('a join boss_daydata_out c on c.lineid=a.id join boss_daydata cc on cc.jfid=c.jfid && cc.adddate=c.adddate join boss_charging_logo_assign cb on cb.cl_id=c.jfid && cb.promotion_stime<=c.adddate && cb.promotion_etime>=c.adddate')->where("c.adddate like '$time%'")->group('a.id')->select();
		$lirunforline2=$inmoneyforline2;
		//业务线利润占比
		foreach ($inmoneyforline as $key => $value) {
			$name=$value['name'];
			foreach ($inmoneyforline2 as $k => $v) {
				if($v['name']==$name){
					$inmoneyforline[$key]['value']=$v['value']+$value['value'];
					unset($inmoneyforline2[$k]);
					continue;
				}
			}
		}
		foreach ($inmoneyforline2 as $key => $value) {
			$inmoneyforline[]=$value;
		}
		$this->assign('inmoneyforline',$inmoneyforline);
		$lirunforline=M('business_line')->field('sum(b.newmoney*(1-bb.in_settlement_prate)) as value,a.name')->join('a join boss_daydata b on b.lineid=a.id join boss_charging_logo_assign bb on bb.cl_id=b.jfid && bb.promotion_stime<=b.adddate && bb.promotion_etime>=b.adddate')->where("b.adddate like '$time%'")->group('a.id')->select();
		foreach ($lirunforline as $key => $value) {
			$name=$value['name'];
			foreach ($lirunforline2 as $k => $v) {
				$v['value']=$v['value']-$v['cb'];
				if($v['name']==$name){
					$lirunforline[$key]['value']=$v['value']+$value['value'];
					unset($lirunforline2[$k]);
					continue;
				}
			}
		}
		foreach ($lirunforline2 as $key => $value) {
			$lirunforline[]=$value;
		}
		$this->assign('lirunforline',$lirunforline);
		//全公司流水
		$time=date('Y');
		$alllinedata=M('closing_data')->where("yearandmonth like '$time%'")->select();
		$allinmoney=M('daydata')->field('sum(newmoney) as money,left(adddate,7) as month')->where("adddate like '$time%'")->group('left(adddate,7)')->select();
		$alloutmoney=M('daydata_out')->field('sum(newmoney) as money,left(adddate,7) as month')->where("adddate like '$time%'")->group('left(adddate,7)')->select();
		$alllinedataarr=array();
		$advtop10arr=array();
		$suptop10arr=array();
		for($i=1;$i<=date('m');$i++){
			$isold=false;
			if(strlen($i)==1)$i='0'.$i;
			$time=date('Y-').$i;
			$alllinedataarr['time'][$time]=$time;
			foreach ($alllinedata as $key => $value) {
				if($value['yearandmonth']==$time){
					$alllinedataarr['oldout'][$time]=twonum($value['outmoney']/10000);
					$alllinedataarr['oldin'][$time]=twonum($value['inmoney']/10000);
					$alllinedataarr['oldlirun'][$time]=twonum(($value['inmoney']-$value['outmoney'])/10000);
					$alllinedataarr['inquerenlv'][$time]=$value['indataquerenlv'];
					$alllinedataarr['outquerenlv'][$time]=$value['outdataquerenlv'];
					$isold=true;
					break;
				}
			}
			if(!$isold){
				$alllinedataarr['oldout'][$time]='';
				$alllinedataarr['oldin'][$time]='';
				$alllinedataarr['oldlirun'][$time]='';
				$alllinedataarr['inquerenlv'][$time]='';
				$alllinedataarr['outquerenlv'][$time]='';
			}
			foreach ($allinmoney as $key => $value) {
				if($value['month']==$time){
					$alllinedataarr['newin'][$time]=twonum($value['money']/10000);
					break;
				}
			}
			foreach ($alloutmoney as $key => $value) {
				if($value['month']==$time){
					$alllinedataarr['newout'][$time]=twonum($value['money']/10000);
					break;
				}
			}

		}
		foreach ($alllinedataarr['newin'] as $key => $value) {

			$alllinedataarr['newlirun'][$key]=twonum(($value-$alllinedataarr['newout'][$key])/10000);
		}
		$this->assign('alllinedataarr',$alllinedataarr);
		//广告主收入流水

		//当月广告主收入TOP10
		$time=date('Y');
		$res_adver=M('daydata')->field("sum(newmoney) as money,adverid")->where("adddate like '$time%'")->group("adverid")->order("sum(newmoney) desc")->limit("0,10")->select();
		foreach ($res_adver as $key => $value) {
			$adv_arr[]=$value['adverid'];
		}
		foreach ($advlist as $key => $value) {
			if(in_array($value['id'],$adv_arr)){
				$advnamearr[$value['id']]=$value['name'];
			}
		}
		foreach ($res_adver as $key => $value) {
			$res_adver[$key]['name']=$advnamearr[$value['adverid']];
			$res_adver[$key]['money']=twonum($value['money']/10000);
		}
		$res_super=M('daydata_out')->field("sum(a.newmoney) as money,b.name as name")->join("a join boss_supplier b on a.superid=b.id")->where("a.adddate like '$time%'")->group("a.superid")->order("sum(a.newmoney) desc")->limit("0,10")->select();
		foreach ($res_super as $key => $value) {
			$res_super[$key]['money']=twonum($value['money']/10000);
		}
		$this->assign('res_adver',$res_adver);
		$this->assign('res_super',$res_super);
		$this->assign('imgtimelist',$imgtimelist);
		//数据确认率
		*/
	

		//检查当前用户有关账权限 udpate 0707 tgd
		$isHas_check = $_SESSION["sec_/Home/monthReport/auth_guangzhang"];

		if(!$isHas_check){
			$isHas_check = isHasAuthToQuery("/Home/monthReport/auth_guangzhang",UID);
			$_SESSION["sec_/Home/monthReport/auth_guangzhang"]  = $isHas_check;
		}

		$this->assign('isHas_check',$isHas_check);

		$this->display();
	}
	public function downloadmonthreportlist(){//月报下载
		$_GET['inandout']=1;
		/*$time_s=date('Y-m').'-01';
		$time_e=date('Y-m').'-31';
		if(!empty(I('get.time_s')))$time_s=I('get.time_s').'-01';
		if(!empty(I('get.time_e')))$time_e=I('get.time_e').'-31';*/

		$time_s = date('Y-m').'-01';
		$time_e = date('Y-m-d', strtotime("$time_s +1 month -1 day"));
		if(!empty(I('time_s')))$time_s = date("Y-m",strtotime(I('time_s'))).'-01';
		if(!empty(I('time_e')))$time_e = date('Y-m-d', strtotime(date("Y-m",strtotime(I('time_e'))).'-01'." +1 month -1 day"));
		
		$data=D('ChargingLogo')->getmonthtable($time_s,$time_e,1);
		$list=array(array('date','年月'),array('lineid','业务线'),array('comname','产品名称'),array('jfname','计费标识'),array('charging_mode','计费模式'),array('source_type','来源类型'),array('indata','收入金额'),array('outdata','成本金额'),array('lirun','毛利润'),array('instatus','收入状态'),array('outstatus','成本状态'),array('neibujiesuan','内部结算金额'),array('inquerenlv','收入确认率'),array('outquerenlv','成本确认率'),array('kaipiaolv','开票率'),array('huikuanlv','回款率'),array('fukuanlv','付款率'),array('adverid','广告主'),array('superid','供应商'),array('salerid','销售'),array('businessid','商务'),array('inhejianmoney','收入核检'),array('outhejianmoney','成本核检'));
        $this->downloadlist($data['data'],$list,'月报表');
	}
	public function closing(){//关账
		set_time_limit(0);
		$time=date('Y-m');
		if(!empty(I('get.time')))$time=date('Y-m',strtotime(I('get.time')));

		
		$hetongcheck=true;//合同检查
		/*
		if(M('advertiser')->where("add_time like '$time%' && is_check=0")->find()){
			$this->assign('data','广告主当月新增记录未检查完毕');
            $this->display('Public/alertpage');
            return;
		}
		if(M('product')->where("add_time like '$time%' && is_check=0")->find()){
			$this->assign('data','产品当月新增记录未检查完毕');
            $this->display('Public/alertpage');
            return;
		}
		if(M('charging_logo')->where("add_time like '$time%' && is_check=0")->find()){
			$this->assign('data','计费标识当月新增记录未检查完毕');
            $this->display('Public/alertpage');
            return;
		}
		if(M('supplier')->where("add_time like '$time%' && is_check=0")->find()){
			$this->assign('data','供应商当月新增记录未检查完毕');
            $this->display('Public/alertpage');
            return;
		}
		/*if(M('daydata')->where("adddate like '$time%' && is_check=0")->find()){
			$this->assign('data','数据当月新增记录未检查完毕');
            $this->display('Public/alertpage');
            return;
		}*/
/*旧关账逻辑
		$data=D('ChargingLogo')->clossing($time);
		$showdata=array();
		foreach ($data as $k => $v) {
			if($v['inline']==$v['outline'] || empty($v['outline'])){
				$data[$k]['lineid']=$v['inline'];
				M('closing')->add($data[$k]);
			}elseif(empty($v['inline'])){
				$data[$k]['lineid']=$v['outline'];
				$data[$k]['inmoney']=0;
				M('closing')->add($data[$k]);
			}else{
				$adddata=$v;
				$v['lineid']=$v['outline'];
				$v['inmoney']=$v['neibujiesuan'];
				$v['profit']=$v['inmoney']-$v['outmoney'];
				M('closing')->add($v);
				
				$adddata['lineid']=$adddata['inline'];
				$adddata['outmoney']=$adddata['neibujiesuan'];
				$adddata['profit']=$adddata['inmoney']-$adddata['neibujiesuan'];
				$showdata[]=$adddata;
				M('closing')->add($adddata);
			}
		}
		$indata=M('daydata')->field('sum(if(status=9 || status=0,0,newmoney)) as money,sum(if(status=0 || status=1 || status=9,0,newmoney)) as querenmoney')->where("adddate like '$time%'")->find();
		$outdata=M('daydata_out')->field('sum(if(status=9 || status=0,0,newmoney)) as money,sum(if(status=1 || status=9,0,newmoney)) as querenmoney')->where("adddate like '$time%'")->find();
		$id=M('closing_data')->add(array('inmoney'=>$indata['money'],'outmoney'=>$outdata['money'],'indataquerenlv'=>$indata['querenmoney']/$indata['money']*100,'outdataquerenlv'=>$outdata['querenmoney']/$outdata['money']*100,'yearandmonth'=>$time,'addtime'=>date('Y-m-d H:i:s')));
*/

		$id=M('closing')->where("adddate like '$time%'")->find();
		$id=false;
		if($id){
			$this->assign('data','关过账了');
            $this->display('Public/alertpage');
		}
		else{
			M()->execute("delete from boss_closing where adddate like '$time%'");
			$res=M()->execute("insert into boss_closing(jfid,adddate,in_id,in_money,in_newmoney,in_datanum,in_newdata,in_comid,in_status,in_adverid,in_lineid,in_price,in_remarks,in_auditdate,in_salerid,in_banimgpath,in_ztid,in_ischeck,out_id,out_money,out_newmoney,out_datanum,out_newdata,out_status,out_superid,out_businessid,out_auditdate,out_price,out_lineid,out_sbid,out_remarks,out_addid) select jfid,adddate,in_id,in_money,in_newmoney,in_datanum,in_newdata,in_comid,in_status,in_adverid,in_lineid,in_price,in_remarks,in_auditdate,in_salerid,in_banimgpath,in_ztid,in_ischeck,out_id,out_money,out_newmoney,out_datanum,out_newdata,out_status,out_superid,out_businessid,out_auditdate,out_price,out_lineid,out_sbid,out_remarks,out_addid from boss_daydata_inandout where adddate like '$time%'");
			if(M('closing_data')->where("yearandmonth='$time'")->find())M('closing_data')->where("yearandmonth='$time'")->save(array('addtime'=>date('Y-m-d H:i:s')));
			else M('closing_data')->add(array('addtime'=>date('Y-m-d H:i:s'),'yearandmonth'=>$time));
			if($res){
				$this->assign('data','关账成功');
				bossGetData('http://sspadmin.youxiaoad.com/bosapi.php?action=closing&method=closingStatusSave&month='.str_replace('-', '', $time).'&status=1');
            	$this->display('Public/alertpage');
			}else{
				$this->assign('data','关账失败');
            	$this->display('Public/alertpage');
			}
		}
	}
	public function fundFlow(){//资金流向表
		if(!empty(I('get.year')))$time=I('get.year').'-'.I('get.month');
		else $time=date('Y-m');
		$data=M('daydata')->field("a.adverid,sum(a.newmoney) as money,b.name")->join('a join boss_advertiser b on a.adverid=b.id')->where("(a.status=3 || a.status=4 || a.status=5) && a.adddate like '$time%'")->group("a.adverid")->select();
		$moneyininfo=M('settlement_in')->field('a.advid,sum(b.money) as money,d.name,substring(b.time,6,2) as date')->join('a join boss_rkrecord b on b.skjsdid=a.id && b.type=1 join boss_advertiser d on a.advid=d.id')->where("a.strdate like '$time%'")->group("a.advid,left(b.time,7)")->select();
		$moneyininfo2=M('settlement_in')->field('a.advid,sum(c.money) as money,d.name,substring(c.time,6,2) as date')->join('a join boss_dkrecord c on c.skjsdid=a.id join boss_advertiser d on a.advid=d.id')->where("a.strdate like '$time%'")->group("a.advid,left(c.time,7)")->select();
		$showdata=array();
		foreach ($moneyininfo as $key => $value) {
			$showdata[$value['advid']][$value['date']]['rk']=$value;
		}
		foreach ($moneyininfo2 as $key => $value) {
			$showdata[$value['advid']][$value['date']]['dk']=$value;
		}
		foreach ($data as $key => $value) {
			$showdata[$value['adverid']]['info']=$value;
		}
		$this->assign('showdata',$showdata);
		$this->display();
	}
	public function downloadfundflowlist(){//资金流向表导出
		if(!empty(I('get.year')))$time=I('get.year').'-'.I('get.month');
		else $time=date('Y-m');
		$data=M('daydata')->field("a.adverid,sum(a.newmoney) as money,b.name")->join('a join boss_advertiser b on a.adverid=b.id')->where("(a.status=3 || a.status=4 || a.status=5) && a.adddate like '$time%'")->group("a.adverid")->select();
		$moneyininfo=M('settlement_in')->field('a.advid,sum(b.money) as money,d.name,substring(b.time,6,2) as date')->join('a join boss_rkrecord b on b.skjsdid=a.id && b.type=1 join boss_advertiser d on a.advid=d.id')->where("a.strdate like '$time%'")->group("a.advid,left(b.time,7)")->select();
		$moneyininfo2=M('settlement_in')->field('a.advid,sum(c.money) as money,d.name,substring(c.time,6,2) as date')->join('a join boss_dkrecord c on c.skjsdid=a.id join boss_advertiser d on a.advid=d.id')->where("a.strdate like '$time%'")->group("a.advid,left(c.time,7)")->select();
		$showdata=array();

		foreach ($moneyininfo as $key => $value) {
			$showdata[$value['advid']][$value['date']]['rk']=$value;
		}
		foreach ($moneyininfo2 as $key => $value) {
			$showdata[$value['advid']][$value['date']]['dk']=$value;
		}
		foreach ($data as $key => $value) {
			$showdata[$value['adverid']]['info']=$value;
		}
		$outdata=array();
		foreach ($showdata as $key => $v) {
			$thismonthmoney=0;
			$nowarr=array('name'=>$v['info']['name'],'money'=>$v['info']['money']);
			for($i=1;$i<=12;$i++){
				if(strlen($i)==1)$i='0'.$i;
				$thisdata=$v[$i];
				$thismoney=$thisdata['rk']['money']+$thisdata['dk']['money'];
				$nowarr['month'.$i]=$thismoney;
				$thismonthmoney+=$thismoney;
			}
			$nowarr['allmoney']=$thismonthmoney;
			$outdata[]=$nowarr;
		}
		$list=array(array('name','广告主'),array('money','应收款'),array('month01','1月收款'),array('month02','2月收款'),array('month03','3月收款'),array('month04','4月收款'),array('month05','5月收款'),array('month06','6月收款'),array('month07','7月收款'),array('month08','8月收款'),array('month09','9月收款'),array('month10','10月收款'),array('month11','11月收款'),array('month12','12月收款'),array('allmoney','合计'));
        $this->downloadlist($outdata,$list,'资金流向表');
	}
	public function getjflist(){
		if(!empty(I('post.name')))$wheres[]="a.name like '%".I('post.name')."%'";
        if(!empty(I('post.code')))$wheres[]="a.code like '%".I('post.code')."%'";
    	$res=M('charging_logo')->field('a.id,a.name,a.code,b.name as comname')->join('a join boss_product b on a.prot_id=b.id')->where(implode(' && ',$wheres))->select();
    	echo json_encode($res);
	}
}