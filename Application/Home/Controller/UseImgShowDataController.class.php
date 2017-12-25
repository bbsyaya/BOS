<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Org\Util\PHPEXCEL;
class UseImgShowDataController extends BaseController {//可视化大图
	public function day_adver(){

		$nowmonth=date('Y');
		$strtime=$nowmonth.'-01-01';
		$endtime=date('Y-m-d');
		if(!empty(I('get.month'))){
			$nowmonth=date('Y').'-'.I('get.month');
			$strtime=$nowmonth.'-01';
			$endtime=$nowmonth.'-31';
		}
		if(!empty(I('get.lineid')))$lineid=" && a.lineid=".I('get.lineid');
		else $lineid='';
		$data_adver=array();
		$res_adver=M('daydata')->field("sum(if(a.lineid!=e.lineid,a.newmoney*d.in_settlement_prate,e.newmoney)) as outmoney,sum(a.newmoney) as inmoney,b.name as advname,b.id as advid")->join("a join boss_advertiser b on a.adverid=b.id join boss_charging_logo c on a.jfid=c.id join boss_daydata_out e on e.jfid=c.id && e.adddate=a.adddate join boss_charging_logo_assign d on d.cl_id=c.id && d.promotion_stime<=e.adddate && d.promotion_etime>=e.adddate")->where("a.adddate like '$nowmonth%'$lineid")->group("a.adverid")->order("sum(a.newmoney) desc")->limit("0,10")->select();
		foreach ($res_adver as $key => $value) {
			$data_adver['advername'][]=$value['advname'];
			$data_adver['outmoney'][]=twonum($value['outmoney']);
			$data_adver['lirun'][]=twonum($value['inmoney']-$value['outmoney']);
			if(!empty(I('get.month')))$data_adver['url'][]='/Report/dayReport?lineid[]='.I('get.lineid')."&strtime=$strtime&endtime=$endtime&adverid[]=".$value['advid'];
			else $data_adver['url'][]="javascript:alert('您选择了全年的数据，由于产生的大量运算可能会整体拖慢服务器速度，建议您切换到单月再点击查看');";
		}
		$this->assign('data_adver',$data_adver);
		$linelist=M('business_line')->select();
		$this->assign('linelist',$linelist);
		$this->display();
	}
	public function day_super(){
		$nowmonth=date('Y');
		$strtime=$nowmonth.'-01-01';
		$endtime=date('Y-m-d');
		if(!empty(I('get.month'))){
			$nowmonth=date('Y').'-'.I('get.month');
			$strtime=$nowmonth.'-01';
			$endtime=$nowmonth.'-31';
		}
		if(!empty(I('get.month')))$nowmonth=date('Y').'-'.I('get.month');
		if(!empty(I('get.lineid')))$lineid=" && e.lineid=".I('get.lineid');
		else $lineid='';
		$data_super=array();
		$res_adver=M('daydata')->field("sum(if(a.lineid!=e.lineid,a.newmoney*d.in_settlement_prate,a.newmoney)) as inmoney,sum(e.newmoney) as outmoney,b.name as supername,b.id as advid")->join("a join boss_charging_logo c on a.jfid=c.id join boss_daydata_out e on e.jfid=c.id && e.adddate=a.adddate join boss_supplier b on e.superid=b.id join boss_charging_logo_assign d on d.cl_id=c.id && d.promotion_stime<=e.adddate && d.promotion_etime>=e.adddate")->where("a.adddate like '$nowmonth%'$lineid")->group("e.superid")->order("sum(e.newmoney) desc")->limit("0,10")->select();

		foreach ($res_adver as $key => $value) {
			$data_super['supername'][]=$value['supername'];
			$data_super['outmoney'][]=twonum($value['outmoney']);
			$data_super['lirun'][]=twonum($value['inmoney']-$value['outmoney']);
			if(!empty(I('get.month')))$data_super['url'][]='/Report/dayReport?lineid[]='.I('get.lineid')."&strtime=$strtime&endtime=$endtime&superid[]=".$value['advid'];
			else $data_adver['url'][]="javascript:alert('您选择了全年的数据，由于产生的大量运算可能会整体拖慢服务器速度，建议您切换到单月再点击查看');return false;";
		}
		$this->assign('data_super',$data_super);
		$linelist=M('business_line')->select();
		$this->assign('linelist',$linelist);
		$this->display();
	}
	public function day_date7(){
		if(!empty(I('get.strtime')))$strtime=I('get.strtime');
		else $strtime=date('Y-m-d',time()-3600*24*7);
		$endtime=date('Y-m-d',strtotime($strtime)+3600*24*7);
		if(count(I('get.lineid'))>0 && I('get.lineid')[0]!=0){
			$lineid=implode(',',I('get.lineid'));
			$data7=array();
			$res7=M('charging_logo')->field("sum(if(b.lineid not in (".implode(',',I('get.lineid'))."),b.newmoney*(1-c.in_settlement_prate),b.newmoney)) as inmoney,sum(if(d.lineid not in (".implode(',',I('get.lineid'))."),b.newmoney*(1-c.in_settlement_prate),d.newmoney)) as outmoney,ifnull(b.adddate,d.adddate) as date")->join('a left join boss_daydata b on a.id=b.jfid && b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'" && b.status!=0 left join boss_daydata_out d on a.id=d.jfid && if(b.adddate is null,d.adddate >="'.$strtime.'" && d.adddate <="'.$endtime.'",d.adddate=b.adddate) && d.status!=0 join boss_product e on a.prot_id=e.id left join boss_charging_logo_assign c on c.cl_id=a.id && c.promotion_stime<=if(b.adddate is null,d.adddate,b.adddate) && if(c.promotion_etime is null,1,c.promotion_etime>=if(b.adddate is null,d.adddate,b.adddate))')->where('(b.lineid in ('.implode(',',I('get.lineid')).') || d.lineid in ('.implode(',',I('get.lineid')).')) && b.id>0')->group("ifnull(b.adddate,d.adddate)")->order("ifnull(b.adddate,d.adddate)")->select();
			$res2=M('charging_logo')->field("sum(d.newmoney) as outmoney,d.adddate as date")->join('a left join boss_daydata_out d on a.id=d.jfid && d.adddate >="'.$strtime.'" && d.adddate <="'.$endtime.'" && d.status!=0 left join boss_daydata b on a.id=b.jfid && if(d.adddate is null,b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'",d.adddate=b.adddate) && b.status!=0 join boss_product e on a.prot_id=e.id')->where('d.lineid in ('.implode(',',I('get.lineid')).') && b.id is null')->group("ifnull(b.adddate,d.adddate)")->order("ifnull(b.adddate,d.adddate)")->select();
			foreach ($res7 as $key => $value) {
					$data7[$value['date']]['inmoney']+=twonum($value['inmoney']);
					$data7[$value['date']]['outmoney']+=twonum($value['outmoney']);
			}
			foreach ($res2 as $key => $value) {
					$data7[$value['date']]['outmoney']+=twonum($value['outmoney']);
			}
				for($i=7;$i>0;$i--){
					$time=date('Y-m-d',strtotime($endtime)-3600*24*$i);
					if(!empty($data7[$time])){
						$data7[$time]['lirun']=twonum($data7[$time]['inmoney']-$data7[$time]['outmoney']);
						$data7[$time]['date']=$time;
					}else{
						$data7[$time]=array('inmoney'=>0,'outmoney'=>0,'lirun'=>0,'date'=>$time);
					}
					$enddata[]=$data7[$time];
				}

			foreach ($enddata as $key => $value) {
				$datae['inmoney'][]=$value['inmoney'];
				$datae['outmoney'][]=$value['outmoney'];
				$datae['lirun'][]=$value['lirun'];
				$datae['date'][]=$value['date'];
			}
		}else{
			$data7=array();
			$res7=M('charging_logo')->field("b.adddate,sum(ifnull(b.newmoney,0)) as inmoney,sum(ifnull(d.newmoney,0)) as outmoney,sum(b.newmoney)-sum(ifnull(d.newmoney,0)) as lirun,ifnull(b.adddate,d.adddate) as date")->join('a left join boss_daydata b on a.id=b.jfid && b.status!=0 left join boss_daydata_out d on a.id=d.jfid && d.adddate=b.adddate && d.status!=0')->where('ifnull(b.adddate,d.adddate)>="'.$strtime.'" && ifnull(b.adddate,d.adddate)<="'.$endtime.'" && b.id>0')->group("ifnull(b.adddate,d.adddate)")->order("ifnull(b.adddate,d.adddate)")->select();
			$res7_2=M('charging_logo')->field("b.adddate,sum(ifnull(b.newmoney,0)) as outmoney,b.adddate as date")->join('a left join boss_daydata_out b on a.id=b.jfid && b.status!=0 left join boss_daydata d on a.id=d.jfid && d.adddate=b.adddate && d.status!=0')->where('ifnull(b.adddate,d.adddate)>="'.$strtime.'" && ifnull(b.adddate,d.adddate)<="'.$endtime.'" && b.id>0 && d.id is null')->group("ifnull(b.adddate,d.adddate)")->order("ifnull(b.adddate,d.adddate)")->select();
			foreach ($res7 as $key => $value) {
				$data7['inmoney'][$value['date']]=twonum($value['inmoney']);
				$data7['outmoney'][$value['date']]=twonum($value['outmoney']);
				$data7['date'][$value['date']]=$value['date'];
			}
			foreach ($res7_2 as $key => $value) {
				$data7['outmoney'][$value['date']]+=twonum($value['outmoney']);
			}
			foreach ($data7['inmoney'] as $key => $value) {
				$data7['lirun'][]=twonum($value-$data7['outmoney'][$key]);
			}
			/*foreach ($data7['inmoney'] as $key => $value) {
				if(empty($data7['outmoney'][$key]))$$data7['outmoney'][$key]=0;
				if(empty($data7['lirun'][$key]))$data7['lirun'][$key]=0;
			}*/

			$datae=$data7;
		}
		$linelist=M('business_line')->select();
		$this->assign('linelist',$linelist);
		$this->assign('data7',$datae);
		$this->display();
	}
	public function lineinzb(){//业务线收入流水占比
		if(!empty(I('get.month_s')))$time_s=I('get.year').'-'.I('get.month_s').'-01';
		else $time_s=date('Y-').'01-01';
		if(!empty(I('get.month_e')))$time_e=I('get.year').'-'.I('get.month_e').'-31';
		else $time_e=date('Y-m-').'31';
		$where=implode(' && ',$wheres);
		//业务线收入占比
		$res=M('daydata')->field('sum(a.newmoney) as value,a.lineid as id,d.name')->join('a left join boss_daydata_out b on a.adddate=b.adddate && a.jfid=b.jfid join boss_business_line d on a.lineid=d.id')->where('a.adddate >= "'.$time_s.'" && a.adddate <= "'.$time_e.'" && a.status!=0')->group('a.lineid')->select();
		$res2=M('daydata')->field('sum(if(a.lineid!=b.lineid,a.newmoney*(1-c.in_settlement_prate),0)) as value,b.lineid as id')->join('a join boss_daydata_out b on a.adddate=b.adddate && a.jfid=b.jfid && b.`status`!=0 join boss_charging_logo_assign c on c.cl_id=a.jfid && c.promotion_stime<=a.adddate && if(c.promotion_etime is null,1,c.promotion_etime>=a.adddate)')->where('a.adddate >= "'.$time_s.'" && a.adddate <= "'.$time_e.'" && a.status!=0')->group('b.lineid')->select();
		foreach ($res as $key => $value) {
			foreach ($res2 as $k => $v) {
				if($v['id']==$value['id'])$res[$key]['value']+=$v['value'];
			}
		}
		$inmoneyforline=$res;

		//业务线利润占比
		foreach ($inmoneyforline as $key => $value) {
			$inmoneyforline[$key]['value']=twonum($value['value']/10000);
			$res_adver['url'][]='/Report/monthReport.html?time_s='.I('get.year').'-'.I('get.month_s').'&time_e='.I('get.year').'-'.I('get.month_e').'&lineid[]='.$value['id'];
			unset($inmoneyforline[$key]['id']);
		}

		$this->assign('res_adver',$res_adver);
		$this->assign('inmoneyforline',json_encode($inmoneyforline));
		$this->display();
	}
	public function linelrzb(){//业务线利润流水占比
		if(!empty(I('get.month_s')))$time_s=I('get.year').'-'.I('get.month_s').'-01';
		else $time_s=date('Y-').'01-01';
		if(!empty(I('get.month_e')))$time_e=I('get.year').'-'.I('get.month_e').'-31';
		else $time_e=date('Y-m-').'31';
		$where=implode(' && ',$wheres);
		//业务线收入占比
		$res=M('daydata')->field('sum(a.newmoney) as value,a.lineid as id,d.name')->join('a join boss_business_line d on a.lineid=d.id')->where('a.adddate >= "'.$time_s.'" && a.adddate <= "'.$time_e.'" && a.status!=0')->group('a.lineid')->select();
		$res2=M('daydata')->field('sum(if(a.lineid!=b.lineid,a.newmoney*(1-c.in_settlement_prate),0)) as value,b.lineid as id,a.lineid as oid')->join('a join boss_daydata_out b on a.adddate=b.adddate && a.jfid=b.jfid && b.`status`!=0 join boss_charging_logo_assign c on c.cl_id=a.jfid && c.promotion_stime<=a.adddate && if(c.promotion_etime is null,1,c.promotion_etime>=a.adddate)')->where('a.adddate >= "'.$time_s.'" && a.adddate <= "'.$time_e.'" && a.status!=0')->group('a.lineid,b.lineid')->select();
		$res3=M('daydata_out')->field('sum(a.newmoney) as value,a.lineid as id,d.name')->join('a join boss_business_line d on a.lineid=d.id')->where('a.adddate >= "'.$time_s.'" && a.adddate <= "'.$time_e.'" && a.status!=0')->group('a.lineid')->select();
		foreach ($res as $key => $value) {
			foreach ($res2 as $k => $v) {
				if($v['id']==$value['id'])$res[$key]['value']+=$v['value'];
			}
			foreach ($res3 as $k => $v) {
				if($v['id']==$value['id'])$res[$key]['value']-=$v['value'];
			}
			foreach ($res2 as $k => $v) {
				if($v['oid']==$value['id'])$res[$key]['value']-=$v['value'];
			}
		}
		$lirunforline=$res;
		foreach ($lirunforline as $key => $value) {
			$lirunforline[$key]['value']=twonum($value['value']/10000);
			$res_adver['url'][]='/Report/monthReport.html?time_s='.I('get.year').'-'.I('get.month_s').'&time_e='.I('get.year').'-'.I('get.month_e').'&lineid[]='.$value['id'];
		}
		$this->assign('lirunforline',json_encode($lirunforline));
		$this->assign('res_adver',$res_adver);
		$this->display();
	}
	public function allmoney(){//全公司流水
		if(I('get.lineid')=='' || in_array(0,I('get.lineid'))){
			$alllinedata=M('closing_data')->select();
			$allinmoney=M('daydata')->field('sum(newmoney) as money,left(adddate,7) as month')->group('left(adddate,7)')->where("status!=0")->select();
			$alloutmoney=M('daydata_out')->field('sum(newmoney) as money,left(adddate,7) as month')->group('left(adddate,7)')->where("status!=0")->select();
		}else{
			$alllinedata=M('closing')->field('sum(inmoney) as inmoney,sum(outmoney) as outmoney,month as yearandmonth')->where("lineid in (".implode(',',I('get.lineid')).")")->group('month')->select();
			$allinmoney=M('daydata')->field('sum(if(a.lineid not in ('.implode(',',I('get.lineid')).'),a.newmoney*(1-c.in_settlement_prate),a.newmoney)) as money,left(a.adddate,7) as month')->join("a left join boss_daydata_out b on a.jfid=b.jfid && a.adddate=b.adddate && b.status!=0 left join boss_charging_logo_assign c on c.cl_id=a.jfid && c.promotion_stime<=a.adddate && if(c.promotion_etime is null,1,c.promotion_etime>=a.adddate)")->where("a.status!=0 && (a.lineid in (".implode(',',I('get.lineid')).") || b.lineid in (".implode(',',I('get.lineid'))."))")->group('left(a.adddate,7)')->select();
			$alloutmoney=M('daydata_out')->field('sum(if(a.lineid not in ('.implode(',',I('get.lineid')).'),b.newmoney*(1-c.in_settlement_prate),a.newmoney)) as money,left(a.adddate,7) as month')->join("a left join boss_daydata b on a.jfid=b.jfid && a.adddate=b.adddate && b.status!=0 left join boss_charging_logo_assign c on c.cl_id=a.jfid && c.promotion_stime<=a.adddate && if(c.promotion_etime is null,1,c.promotion_etime>=a.adddate)")->where("a.status!=0 && (a.lineid in (".implode(',',I('get.lineid')).") || b.lineid in (".implode(',',I('get.lineid'))."))")->group('left(a.adddate,7)')->select();
		}
		
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
					$isold=true;
					break;
				}
			}
			if(!$isold){
				$alllinedataarr['oldout'][$time]='';
				$alllinedataarr['oldin'][$time]='';
				$alllinedataarr['oldlirun'][$time]='';
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
			$alllinedataarr['newlirun'][$key]=twonum($value-$alllinedataarr['newout'][$key]);
		}
		$this->assign('alllinedataarr',$alllinedataarr);
		$linelist=M('business_line')->select();
		$this->assign('linelist',$linelist);
		$this->display();
	}
	public function month_adver(){//广告主收入TOP10
		if(!empty(I('get.type'))){
			if(!empty(I('get.lineid')))$where=" && a.lineid in (".implode(',',I('get.lineid')).")";
			else $where='';
			$top=I('get.type')-1;
			$arr=array();
			for($i=1;$i<=12;$i++){
				if(strlen($i)==1)$i='0'.$i;
				$time=date('Y-').$i;
				$data=M('daydata')->field("sum(a.newmoney) as money,b.name as name,b.id,'$time' as month")->join("a join boss_advertiser b on a.adverid=b.id")->where("a.adddate like '$time%'".$where)->group("a.adverid")->order("sum(a.newmoney) desc")->limit($top.",1")->select();
				$arr[$i]=$data[0];
			}
			foreach ($arr as $key => $value) {
				$arr['url'][]='/Report/monthReport.html?time='.date('Y-m').'&advid[]='.$value['id'];
			}
			$this->assign('res_adver',$arr);
		}else{
			if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
				$wheres[]="(a.lineid in (".implode(',',I('get.lineid')).") || c.lineid in (".implode(',',I('get.lineid'))."))";
				$f="if(a.lineid not in (".implode(',',I('get.lineid'))."),a.newmoney*(1-d.in_settlement_prate),a.newmoney)";
			}else{
				$f='a.newmoney';
			}
			if(!empty(I('get.month_s')))$wheres[]="a.adddate >= '".I('get.year').'-'.I('get.month_s')."'";
			else $wheres[]="a.adddate >= '".date('Y')."'";
			if(!empty(I('get.month_e')))$wheres[]="a.adddate <= '".I('get.year').'-'.I('get.month_e')."-31'";
			else $wheres[]="a.adddate <= '".date('Y-m')."-31'";
			$where=implode(' && ',$wheres);
			$res_adver=M('daydata')->field("sum($f) as money,b.name as name,b.id,'' as month")->join("a join boss_advertiser b on a.adverid=b.id left join boss_daydata_out c on a.jfid=c.jfid && a.adddate=c.adddate left join boss_charging_logo_assign d on d.cl_id=a.jfid && d.promotion_stime<=a.adddate && if(d.promotion_etime is null,1,d.promotion_etime>=a.adddate)")->where($where)->group("a.adverid")->order("sum(a.newmoney) desc")->limit("0,10")->select();
			foreach ($res_adver as $key => $value) {
				$res_adver['url'][]='/Report/monthReport.html?time_s='.I('get.year').'-'.I('get.month_s').'&time_e='.I('get.year').'-'.I('get.month_e').'&advid[]='.$value['id'];
			}
			$this->assign('res_adver',$res_adver);
		}
		$linelist=M('business_line')->select();
		$this->assign('linelist',$linelist);
		$this->display();
	}
	public function month_super(){//供应商成本TOP10
		
		
		
		if(!empty(I('get.type'))){
			if(!empty(I('get.lineid')))$where=" && a.lineid in (".implode(',',I('get.lineid')).")";
			else $where='';
			$top=I('get.type')-1;
			$arr=array();
			for($i=1;$i<=12;$i++){
				if(strlen($i)==1)$i='0'.$i;
				$time=date('Y-').$i;
				$data=M('daydata_out')->field("sum(a.newmoney) as money,b.name as name,b.id,'$time' as month")->join("a join boss_supplier b on a.superid=b.id")->where("a.adddate like '$time%'".$where)->group("a.superid")->order("sum(a.newmoney) desc")->limit($top.",1")->select();
				$arr[$i]=$data[0];
			}
			foreach ($arr as $key => $value) {
				$arr['url'][]='/Report/monthReport.html?time='.date('Y-m').'&supid[]='.$value['id'];
			}
			$this->assign('res_super',$arr);
		}else{
			if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
				$wheres[]="(a.lineid in (".implode(',',I('get.lineid')).") || c.lineid in (".implode(',',I('get.lineid'))."))";
				$f="if(a.lineid not in (".implode(',',I('get.lineid'))."),a.newmoney*(1-d.in_settlement_prate),a.newmoney)";
			}else{
				$f='a.newmoney';
			}
			if(!empty(I('get.month_s')))$wheres[]="a.adddate >= '".I('get.year').'-'.I('get.month_s')."'";
			else $wheres[]="a.adddate >= '".date('Y')."'";
			if(!empty(I('get.month_e')))$wheres[]="a.adddate <= '".I('get.year').'-'.I('get.month_e').'-31'."'";
			else $wheres[]="a.adddate <= '".date('Y-m')."-31'";
			$where=implode(' && ',$wheres);

			$res_super=M('daydata_out')->field("sum($f) as money,b.name as name,b.id,'' as month")->join("a join boss_supplier b on a.superid=b.id left join boss_daydata c on a.adddate=c.adddate && a.jfid=c.jfid left join  boss_charging_logo_assign d on d.cl_id=a.jfid && d.promotion_stime<=a.adddate && if(d.promotion_etime is null,1,d.promotion_etime>=a.adddate)")->where($where)->group("a.superid")->order("sum(a.newmoney) desc")->limit("0,10")->select();
			foreach ($res_super as $key => $value) {
				$res_super['url'][]='/Report/monthReport.html?time_s='.I('get.year').'-'.I('get.month_s').'&time_e='.I('get.year').'-'.I('get.month_e').'&supid[]='.$value['id'];
			}

			$this->assign('res_super',$res_super);
		}
		$linelist=M('business_line')->select();
		$this->assign('linelist',$linelist);
		$this->display();
	}
}
