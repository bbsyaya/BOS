<?php
namespace Home\Controller;
use Think\Controller;

class CheckAllDataController extends Controller {//效正所有数据
	public $filename;
	public function _initialize(){
		$GLOBALS['islog']=1;
		$this->filename='./upload/log/checklog'.date("Y-m-d").'.txt';
	}
	public function nextrw(){
		$data=file_get_contents('./upload/log/allrw.txt');
		if(trim($data)=='')$this->redirect("CheckAllData/over");
		$arr=explode(',', $data);
		if(!empty(I('post.strtime'))){
			$time=strtotime(I('post.strtime'));
			$m=date('m',$time);
			$y=date('Y',$time);
		}
		$nowrw=$arr[0]."?mo=$m&y=$y";
		unset($arr[0]);
		file_put_contents('./upload/log/allrw.txt', implode(',', $arr));
		$this->redirect("CheckAllData/".$nowrw);
	}
	public function index(){
		if(!empty(I('post.outzt'))>0){
			file_put_contents('./upload/log/allrw.txt', implode(',', I('post.outzt')));
			file_put_contents($this->filename, date('Y-m-d H:i:s').'\r\n');
			$this->nextrw();
		}
		$this->display();
	}
	public function index2(){
		if(!is_dir('./upload/log'))mkdir('./upload/log',0777,true);
		file_put_contents($this->filename, date('Y-m-d H:i:s').'\r\n');
		$this->redirect("CheckAllData/checkadv");
	}
	//检查基础信息
	public function checkadv(){
		//广告主信息
		$res=M('advertiser')->query('SELECT name FROM `boss_advertiser` group by name having count(*)>1');
		$str='';
		foreach ($res as $k => $v) {
			$str.=$v['name'].' ';
		}
		if($str!=''){
			$str2=" 名称为 ".$str."的广告主出现重复\r\n";
			$r=file_put_contents($this->filename, $str2 ,FILE_APPEND);
		}
		$res2=M('product')->query('SELECT id FROM `boss_product` group by name,ad_id,sb_id having count(*)>1');
		$str='';
		foreach ($res2 as $k => $v) {
			$str.=$v['id'].' ';
		}
		if($str!=''){
			$str2=" ID为 ".$str."的产品出现重复\r\n";
			$r=file_put_contents($this->filename, $str2 ,FILE_APPEND);
		}
		$res_adver=M('advertiser')->join("a left join boss_advertiser_contacts b on a.id=b.ad_id left join boss_advertiser_fireceiver c on c.ad_id=a.id")->where("a.account_name is null || a.account_name = '' || a.object_type is null || a.object_type = '' || a.cooperation_time is null || a.cooperation_time = '' || a.established_time is null || a.established_time = '' || a.register_capital is null || a.register_capital = '' || a.rating_level is null || a.rating_level = '' || a.info_owner is null || a.info_owner = '' || a.qualif_img is null || a.qualif_img = '' || a.audit_status is null || a.audit_status = '' || a.audit_time is null || a.audit_time = '' || a.auditor is null || a.auditor = '' || a.assign_time is null || a.assign_time = '' || a.assigner is null || a.assigner = '' || a.reg_time is null || a.reg_time = '' || a.region is null || a.region = '' || a.contract_num is null || a.contract_num = '' || a.address is null || a.address = '' || a.email is null || a.email = '' || a.bank_no is null || a.bank_no = '' || a.opening_bank is null || a.opening_bank = '' || a.invoice_type is null || a.invoice_type = '' || a.invoice_remark is null || a.invoice_remark = '' || a.taxpayer_num is null || a.taxpayer_num = '' || a.reg_address is null || a.reg_address = '' || a.reg_mobile is null || a.reg_mobile = '' || c.name is null || c.name = '' || c.mobile is null || c.mobile = '' || c.address is null || c.address = '' || b.name is null || b.name = '' || b.mobile is null || b.mobile = '' || b.qq is null || b.qq = '' || b.email is null || b.email = '' || b.address is null || b.address = '' || b.user is null || b.user = '' || b.bl is null || b.bl = ''")->select();
		$str='';
		foreach ($res_adver as $k => $v) {
			$str.=$v['id'].' ';
		}
		if($str!=''){
			$str4=" ID为 ".$str."的广告主相关信息不全\r\n";
			$r=file_put_contents($this->filename, $str4 ,FILE_APPEND);
		}
		//产品信息
		$res_prot=M('product')->join("a left join boss_product_contacts b on a.id=b.pro_id left join boss_product_plane_bl c on c.pro_id=a.id")->where("a.name is null || a.name = '' || a.type is null || a.type = '' || a.category is null || a.category = '' || a.ad_id is null || a.ad_id = '' || a.bl_id is null || a.bl_id = '' || a.sb_id is null || a.sb_id = '' || a.source_type is null || a.source_type = '' || a.saler_id is null || a.saler_id = '' || a.cooperate_state is null || a.cooperate_state = '' || a.contract_num is null || a.contract_num = '' || a.contract_s_duration is null || a.contract_s_duration = '' || a.contract_e_duration is null || a.contract_e_duration = '' || a.order_type is null || a.order_type = '' || a.invoice_type is null || a.invoice_type = '' || a.return_cycle is null || a.return_cycle = '' || a.settle_cycle is null || a.settle_cycle = '' || a.reconciliation_day is null || a.reconciliation_day = '' || a.bill_day is null || a.bill_day = '' || a.receivables_day is null || a.receivables_day = '' || a.charging_mode is null || a.charging_mode = '' || a.price_type is null || a.price_type = '' || a.price is null || a.price = '' || a.package_return_type is null || a.package_return_type = '' || a.package_return_email is null || a.package_return_email = '' || a.package_size is null || a.package_size = '' || a.quality_requirements is null || a.quality_requirements = '' || a.qr_check_rule is null || a.qr_check_rule = '' || c.bl_type is null || c.bl_type = '' || c.bl_id is null || c.bl_id = '' || b.name is null || b.name = '' || b.mobile is null || b.mobile = '' || b.email is null || b.email = '' || b.qq is null || b.qq = ''")->select();
		$str='';
		foreach ($res_prot as $k => $v) {
			$str.=$v['id'].' ';
		}
		if($str!=''){
			$str5=" ID为 ".$str."的产品相关信息不全\r\n";
			$r=file_put_contents($this->filename, $str5 ,FILE_APPEND);
		}
		//计费标识信息
		$res_cl=M('charging_logo')->where("name is null || name = '' || promotion_url is null || promotion_url = '' || price_type is null || price_type = '' || price is null || price = '' || charging_mode is null || charging_mode = '' || back_url is null || back_url = '' || account is null || account = '' || password is null || password = ''")->select();
		$str='';
		foreach ($res_cl as $k => $v) {
			$str.=$v['id'].' ';
		}
		if($str!=''){
			$str6=" ID为 ".$str."的计费标识相关信息不全\r\n";
			$r=file_put_contents($this->filename, $str6 ,FILE_APPEND);
		}
		//供应商信息
		$res_sup=M('supplier')->join("a left join boss_supplier_contacts b on a.id=b.sp_id")->where("a.name is null || a.name = '' || a.type is null || a.type = '' || a.email is null || a.email = '' || a.mobile is null || a.mobile = '' || a.region is null || a.region = '' || a.address is null || a.address = '' || a.contract_num is null || a.contract_num = '' || b.name is null || b.name = '' || b.mobile is null || b.mobile = '' || b.email is null || b.email = '' || b.qq is null || b.qq = '' || b.address is null || b.address = '' || b.business_uid is null || b.business_uid = ''")->select();
		$str='';
		foreach ($res_sup as $k => $v) {
			$str.=$v['id'].' ';
		}
		if($str!=''){
			$str7=" ID为 ".$str."的供应商相关信息不全\r\n";
			$r=file_put_contents($this->filename, $str7 ,FILE_APPEND);
		}
		//明细信息
		$res_data_in=M('daydata')->where("adddate is null || adddate = '' || jfid is null || jfid = '' || newmoney is null || newmoney = '' || newdata is null || newdata = '' || comid is null || comid = '' || adverid is null || adverid = '' || lineid is null || lineid = '' || price is null || price = '' || ztid is null || ztid = ''")->select();
		$str='';
		foreach ($res_data_in as $k => $v) {
			$str.=$v['id'].' ';
		}
		if($str!=''){
			$str8=" ID为 ".$str."的收入数据信息不全\r\n";
			$r=file_put_contents($this->filename, $str8 ,FILE_APPEND);
		}
		//合同信息
		/*
		$res_ht=M('flow_data_434')->where('1=0')->select();
		$str='';
		foreach ($res_ht as $k => $v) {
			$str.=$v['id'].' ';
		}
		if($str!=''){
			$str3=" ID为 ".$str."的合同不合格\r\n";
			$r=file_put_contents($this->filename, $str3 ,FILE_APPEND);
		}*/
		$this->nextrw();
	}
	//检查收入数据状态
	public function changesetstatus_in(){
		$p=I('get.p',0);
		$str='';
		if(!empty(I('get.strtime')))$where=" && adddate>='".I('get.strtime')."' && adddate<='".I('get.endtime')."'";
		else $where="";
		$data=M('daydata')->where("status!=0 && status!=9".$where)->order('id desc')->limit(($p*100).',100')->select();
		foreach ($data as $key => $v) {
			$res=M('settlement_in')->where("id>1224 && strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && (allcomid like '".$v['comid'].",%' || allcomid like '%,".$v['comid']."' || allcomid like '%,".$v['comid'].",%' || allcomid='".$v['comid']."') && status!=6 && advid=".$v['adverid'])->order('status desc')->find();
			if($res['status']==1 && $v['status']!=2){
				//M('daydata')->where("id=".$v['id'])->save(array('status'=>2));
				$str.= 'ID为'.$v['id'].'  '.$v['status'].'=>2\r\n';
			}elseif($res['status']==2 && $v['status']!=3){
				//M('daydata')->where("id=".$v['id'])->save(array('status'=>3));
				$str.= 'ID为'.$v['id'].'  '.$v['status'].'=>3\r\n';
			}elseif($res['status']==3 && $v['status']!=4){
				//M('daydata')->where("id=".$v['id'])->save(array('status'=>4));
				$str.= 'ID为'.$v['id'].'  '.$v['status'].'=>4\r\n';
			}elseif($res['status']==4 && $v['status']!=5){
				//M('daydata')->where("id=".$v['id'])->save(array('status'=>5));
				$str.= 'ID为'.$v['id'].'  '.$v['status'].'=>5\r\n';
			}elseif($res['status']==5 && $v['status']!=8){
				//M('daydata')->where("id=".$v['id'])->save(array('status'=>8));
				$str.= 'ID为'.$v['id'].'  '.$v['status'].'=>8\r\n';
			}
			//if($res && $res['salerid']!=$v['salerid'] && $res['id']<=1224)M('daydata')->where("id=".$v['id'])->save(array('salerid'=>$res['salerid']));
			if($v['salerid']==''){
				$r=M('product')->where("id=".$v['comid'])->find();
				//M('daydata')->where("id=".$v['id'])->save(array('salerid'=>$r['saler_id']));
			}
		}
		if($str!='')file_put_contents($this->filename, $str ,FILE_APPEND);
		if(count($data)<100)$this->redirect("CheckAllData/changesetstatus_in2");
		else echo '<script>window.location="?p='.($p+1).'&strtime='.I('get.strtime').'&endtime='.I('get.endtime').'"</script>';
	}
		//查2.0已有结算单对应数据状态
	public function changesetstatus_in2(){
		if(!empty(I('get.strtime')))$where=" && enddate>='".I('get.strtime')."' && strdate<='".I('get.endtime')."'";
		else $where="";
		$p=I('get.p',0);
		$data=M('settlement_in')->where("status!=6 && id<=1224 && status!=0".$where)->order('id desc')->limit($p.',1')->select();
		if(count($data)==0)$this->nextrw();
		$data=$data[0];
		if($data['alldataid']!=''){
			$where=" && id in (".$data['alldataid'].")";
		}
		else $where='';
		$str='';
		if($data['status']!=5){
			$res=M('daydata')->where("status!=".($data['status']+1)." && adddate<='".$data['enddate']."' && adddate>='".$data['strdate']."' && comid in (".$data['allcomid'].")".$where)->select();
			foreach ($res as $k => $v) {
				$str.='ID为'.$v['id'].'  '.$v['status'].'=>'.($data['status']+1).'\r\n';
			}
			//M('daydata')->where("adddate<='".$data['enddate']."' && adddate>='".$data['strdate']."' && comid in (".$data['allcomid'].")".$where)->save(array('status'=>$data['status']+1));
		}elseif($data['status']==5){
			$res=M('daydata')->where("status!=8 && adddate<='".$data['enddate']."' && adddate>='".$data['strdate']."' && comid in (".$data['allcomid'].")".$where)->select();
			foreach ($res as $k => $v) {
				$str.='ID为'.$v['id'].'  '.$v['status'].'=>8\r\n';
			}
		}
		if($str!='')file_put_contents($this->filename, $str ,FILE_APPEND);
		echo '<script>window.location="?p='.($p+1).'&strtime='.I('get.strtime').'&endtime='.I('get.endtime').'"</script>';
	}
	//检查成本数据状态
	public function changesetstatus_out(){
		if(!empty(I('get.strtime')))$where=" && adddate>='".I('get.strtime')."' && adddate<='".I('get.endtime')."'";
		else $where="";
		$p=I('get.p',0);
		$data=M('daydata_out')->where("status!=0 && status!=9".$where)->order('id desc')->limit(($p*100).',100')->select();
		$str='';
		foreach ($data as $key => $v) {
			$res=M('settlement_out')->where("id>1346 && strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && (alljfid like '".$v['jfid'].",%' || alljfid like '%,".$v['jfid']."' || alljfid like '%,".$v['jfid'].",%' || alljfid='".$v['jfid']."') && status!=6 && status!=0 && superid='".$v['superid']."'")->order('status desc')->find();
			if($res['status']==1 && $v['status']!=2){
				//M('daydata_out')->where("id=".$v['id'])->save(array('status'=>2));
				$str.= '成本ID为'.$v['id'].'  '.$v['status'].'=>2\r\n';
			}elseif($res['status']==2 && $v['status']!=3){
				//M('daydata_out')->where("id=".$v['id'])->save(array('status'=>3));
				$str.= '成本ID为'.$v['id'].'  '.$v['status'].'=>3\r\n';
			}elseif($res['status']==4 && $v['status']!=4){
				//M('daydata_out')->where("id=".$v['id'])->save(array('status'=>4));
				$str.= '成本ID为'.$v['id'].'  '.$v['status'].'=>4\r\n';
			}
		}
		if($str!='')file_put_contents($this->filename, $str ,FILE_APPEND);
		if(count($data)<100)$this->redirect("CheckAllData/changesetstatus_out2");
		else echo '<script>window.location="?p='.($p+1).'&strtime='.I('get.strtime').'&endtime='.I('get.endtime').'"</script>';
	}
	//查2.0已有结算单对应数据状态
	public function changesetstatus_out2(){
		if(!empty(I('get.strtime')))$where=" && enddate>='".I('get.strtime')."' && strdate<='".I('get.endtime')."'";
		else $where="";
		$p=I('get.p',0);
		$data=M('settlement_out')->where("status!=6 && id<=1348 && status!=0".$where)->order('id desc')->limit($p.',1')->select();
		if(count($data)==0)$this->nextrw();
		$data=$data[0];
		if($data['alldataid']!=''){
			if(substr($data['alldataid'],0,1)==','){
				$data['alldataid']=substr($data['alldataid'],1);
				//M('settlement_out')->where("id=".$data['id'])->save(array('alldataid'=>$data['alldataid']));
			}
			$where=" && id in (".$data['alldataid'].")";
		}
		else $where='';
		if($data['status']<=2){
			$res=M('daydata')->where("status!=".($data['status']+1)." && adddate<='".$data['enddate']."' && adddate>='".$data['strdate']."' && comid in (".$data['allcomid'].")".$where)->select();
			foreach ($res as $k => $v) {
				$str.='ID为'.$v['id'].'  '.$v['status'].'=>'.($data['status']+1).'\r\n';
			}
			//M('daydata_out')->where("adddate<='".$data['enddate']."' && adddate>='".$data['strdate']."' && jfid in (".$data['alljfid'].")".$where)->save(array('status'=>$data['status']+1));
		}elseif($data['status']==4){
			$res=M('daydata')->where("status!=4 && adddate<='".$data['enddate']."' && adddate>='".$data['strdate']."' && comid in (".$data['allcomid'].")".$where)->select();
			foreach ($res as $k => $v) {
				$str.='ID为'.$v['id'].'  '.$v['status'].'=>4\r\n';
			}
		}
		echo '<script>window.location="?p='.($p+1).'&strtime='.I('get.strtime').'&endtime='.I('get.endtime').'"</script>';
	}
	//查成本结算单数据与详细数据金额
	public function changesetmoney_out(){
		if(!empty(I('get.strtime')))$where=" && enddate>='".I('get.strtime')."' && strdate<='".I('get.endtime')."'";
		else $where="";
		$p=I('get.p',0);
		$data=M('settlement_out')->where("status!=6 && status!=0".$where)->group('alljfid,strdate,enddate,superid,lineid')->order('id desc')->limit($p.',1')->select();
		if(count($data)==0)$this->nextrw();
		$data=$data[0];
		if($data['alldataid']!=''){
			$where=" && id in (".$data['alldataid'].")";
		}else $where='';
		if($data['id']<=1346)$data_res=M('daydata_out')->field('sum(newmoney) as money')->where("adddate<='".$data['enddate']."' && adddate>='".$data['strdate']."' && jfid in (".$data['alljfid'].")".$where)->find();
		else $data_res=M('daydata_out')->field('sum(newmoney) as money')->where("adddate<='".$data['enddate']."' && adddate>='".$data['strdate']."' && status!=9 && jfid in (".$data['alljfid'].") && superid in (".$data['superid'].") && lineid=".$data['lineid'])->find();
		$str='';

		if(twonum($data_res['money'])!=twonum($data['settlementmoney'])){
			$b=twonum($data['settlementmoney'])-twonum($data_res['money']);
			if($b*$b>100)$a='dddd';
			if($data['id']>1346 || $b*$b>=100)$str= $a.'成本ID为'.$data['id'].'金额为'.twonum($data['settlementmoney']).'与明细金额'.twonum($data_res['money']).'不一致,差额'.(twonum($data['settlementmoney'])-twonum($data_res['money'])).'\r\n';
		}
		if($str!='')file_put_contents($this->filename, $str ,FILE_APPEND);
		echo '<script>window.location="?p='.($p+1).'&strtime='.I('get.strtime').'&endtime='.I('get.endtime').'"</script>';
	}
	//查收入结算单数据与详细数据金额
	public function changesetmoney_in(){
		if(!empty(I('get.strtime')))$where=" && enddate>='".I('get.strtime')."' && strdate<='".I('get.endtime')."'";
		else $where="";
		$p=I('get.p',0);
		$data=M('settlement_in')->where("status!=6 && status!=0".$where)->group('allcomid,strdate,enddate,advid,lineid')->order('id desc')->limit($p.',1')->select();
		if(count($data)==0)$this->nextrw();
		$data=$data[0];
		if($data['alldataid']!=''){
			$where="id in (".$data['alldataid'].")";
		}else $where='';
		if($data['id']<=1224 && $where!='')$data_res=M('daydata')->field('sum(newmoney) as money')->where($where)->find();
		else $data_res=M('daydata')->field('sum(newmoney) as money')->where("adddate<='".$data['enddate']."' && adddate>='".$data['strdate']."' && comid in (".$data['allcomid'].") && adverid=".$data['advid']." && lineid=".$data['lineid']." && status!=9 && salerid=".$data['salerid'])->find();
		$str='';
		if(twonum($data_res['money'])!=twonum($data['settlementmoney'])){
			$b=twonum($data['settlementmoney'])-twonum($data_res['money']);
			if($b*$b>100)$a='dddd';
			if($data['id']>1224 || $b*$b>=100){
				$sql = M()->getLastSql();
				$str='\r\n'.$a. '收入ID为'.$data['id'].'金额为'.twonum($data['settlementmoney']).'与明细金额'.twonum($data_res['money']).'不一致,差额'.(twonum($data['settlementmoney'])-twonum($data_res['money'])).'\r\n';
			}
		}
		if($str!='')file_put_contents($this->filename, $str ,FILE_APPEND);
		echo '<script>window.location="?p='.($p+1).'&strtime='.I('get.strtime').'&endtime='.I('get.endtime').'"</script>';
	}
	//自检结束，打印日志
	public function over(){
		var_dump(is_dir('./upload/log'));
		if(!empty(I('get.date')))$this->filename='./upload/log/checklog'.I('get.date').'.txt';
		$data=file_get_contents($this->filename);
		echo str_replace('\r\n', '<br/>', $data);
	}
	//记录运行日志至数据库

	//对比分发平台的成本数据
	public function getfenfaoutdataandcheckmydata(){
		$url = 'http://dist.youxiaoad.com/api.php/Coststatus/getcostdata';

		

		$money=I('get.money');
		$str='';
		$appsecret = "b#asb%svp&^";
		$data['ts'] = time();
		$data['sign'] = md5($appsecret.$data['ts']);
		$y=date('Y',time()-3600*24*30);
		$m=date('m',time()-3600*24*30);
		if(!empty(I('post.strtime'))){
			$y=substr(I('post.strtime'), 0,4);
			$m=substr(I('post.strtime'), 4,2);
		}
		if(!empty(I('get.mo')))$m=I('get.mo');
		if(!empty(I('get.y')))$y=I('get.y');
		$d=I('get.d',1);
		if(strlen($m)==1)$m='0'.$m;
		if(strlen($d)==1)$d='0'.$d;
		$data['startdate'] = $y.$m.$d;
		$data['enddate'] = $y.$m.$d;

		$time=$y.'-'.$m.'-'.$d;
		$result = bossPostData($url, $data);
		$getdata=json_decode($result,true);
		$oldres=M('daydata_out')->where("adddate='$time' && lineid=1")->select();
		foreach ($oldres as $k => $v) {
			$oldres2[$v['jfid']]=$v;
		}
		foreach ($getdata['data'] as $k => $v) {
			if($v['status']==0)continue;
			if($v['status']==1)$money+=twonum($oldres2[$v['jfid']]['newmoney']);
			if(empty($oldres2[$v['jfid']])) $str.="缺少".$v['jfid'].' '.$time.' <br/>';
			else{
				if(((float)$v['real_data']!=(float)$oldres2[$v['jfid']]['newdata'] && $v['status']==1) || (twonum($v['settle_money'])!=twonum($oldres2[$v['jfid']]['newmoney']) && $v['status']==1) || ($v['status']==-1 && $oldres2[$v['jfid']]['status']!=9) || ($v['status']!=-1 && $oldres2[$v['jfid']]['status']==9) || ($v['status']==2 && $oldres2[$v['jfid']]['newmoney']!=0)){
					echo $v['settle_money'].' => '.$oldres2[$v['jfid']]['newmoney'].'<br/>';
					$str.=$v['jfid'].' '.$time.' <br/>';
				}
				unset($oldres2[$v['jfid']]);
			}
			
		}
		foreach ($oldres2 as $key => $value) {
			if($value['newmoney']!=0)$str.=' '.$value['id'].'数据多余 ';
		}
		if($str!='')file_put_contents($this->filename, $str ,FILE_APPEND);
		if(I('get.d')>=31){
			file_put_contents($this->filename, '总金额：'.$money ,FILE_APPEND);
			$this->nextrw();
		}

		echo '<script>window.location="?d='.($d+1)."&mo=$m&y=$y&money=$money".'"</script>';
	}
	//对比分发平台的收入数据
	public function getfenfaindataandcheckmydata(){
		$url = 'http://dist.youxiaoad.com/api.php/Coststatus/getincomedata';
		$money=I('get.money');
		$num=I('get.num');
		$str='';
		$appsecret = "b#asb%svp&^";
		$data['ts'] = time();
		$data['sign'] = md5($appsecret.$data['ts']);
		$y=date('Y',time()-3600*24*30);
		$m=date('m',time()-3600*24*30);
		if(!empty(I('post.strtime'))){
			$y=substr(I('post.strtime'), 0,4);
			$m=substr(I('post.strtime'), 4,2);
		}
		if(!empty(I('get.mo')))$m=I('get.mo');
		if(!empty(I('get.y')))$y=I('get.y');
		$d=I('get.d',1);
		if(strlen($m)==1)$m='0'.$m;
		if(strlen($d)==1)$d='0'.$d;
		$data['startdate'] = $y.$m.$d;
		$data['enddate'] = $y.$m.$d;

		$time=$y.'-'.$m.'-'.$d;
		$result = bossPostData($url, $data);
		$getdata=json_decode($result,true);
		
		$oldres=M('daydata')->where("adddate='$time' && lineid=1")->select();
		foreach ($oldres as $k => $v) {
			$oldres2[$v['jfid']]=$v;
		}
		foreach ($getdata['data'] as $k => $v) {
			if($v['status']<2)continue;
			$num++;
			echo $v['settle_money'].' => '.$oldres2[$v['jfid']]['newmoney'].'<br/>';
			$money+=twonum($oldres2[$v['jfid']]['newmoney']);
			echo $money.' ';
			if(empty($oldres2[$v['jfid']])) $str.="缺少".$v['jfid'].' '.$time.' <br/>';
			else{
				if((twonum($v['settle_money'])!=twonum($oldres2[$v['jfid']]['newmoney']) && $v['status']==2) || ($v['status']==3 && $oldres2[$v['jfid']]['newmoney']!=0) || ($v['dofalse']==1 && $oldres2[$v['jfid']]['status']!=9) || ($v['dofalse']!=1 && $oldres2[$v['jfid']]['status']==9)){
					M('daydata')->where("id=".$oldres2[$v['jfid']]['id'])->save(array("istbok"=>2));
					
					$str.=$v['jfid'].' '.$time.' <br/>';
				}
				unset($oldres2[$v['jfid']]);
			}
			
		}
		foreach ($oldres2 as $key => $value) {
			if($value['newmoney']!=0)$str.=' '.$value['id'].'数据多余 ';
		}
		if($str!='')file_put_contents($this->filename, $str ,FILE_APPEND);
		if(I('get.d')>=31){
			file_put_contents($this->filename, '总金额：'.$money." 总条数：".$num ,FILE_APPEND);
			$this->nextrw();
		}

		echo '<script>window.location="?d='.($d+1)."&mo=$m&y=$y&money=$money&num=$num".'"</script>';
	}
	//对比SSP平台的成本数据
	public function getsspoutdataandcheckmydata(){

		$d=I('get.d');
		if(!empty(I('post.strtime'))){
			$time=strtotime(I('post.strtime'));
		}else $time=time()-3600*24*30;
		$y=date('Y',$time);
		$m=date('m',$time);
		if(!empty(I('get.mo')))$m=I('get.mo');
		if(!empty(I('get.y')))$y=I('get.y');

		if(strlen($m)==1)$m='0'.$m;
		if(strlen($d)==1)$d='0'.$d;
		$cs=$y.$m.$d;
		$url = "http://60.205.150.74/api/bosapi.php?action=index&method=incost";
		$money=I('get.money');
		$data['startTime']=$cs;
		$data['endTime']=$cs;
		$result = bossPostData($url, $data);

		$host = array("Host: sspadmin.youxiaoad.com");
		$ch = curl_init();
		$res= curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$host);
		$output = curl_exec ($ch);
		curl_close($ch);

		$str='';
		$data=json_decode($output,true);
		$time=$y.'-'.$m.'-'.$d;
		$oldres=M('daydata_out')->where("adddate='$time' && lineid in(2,40,44)")->select();
		foreach ($oldres as $k => $v) {
			$oldres2[$v['jfid']]=$v;
		}
		foreach ($data['data'] as $k => $v) {
			$money+=$v['money'];
			echo $v['money'].' => '.$oldres2[$v['clid']]['newmoney'].'\s';
			if(empty($oldres2[$v['clid']]) && $v['status']!=0){
				$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$v['clid'])->find();
				$str.="缺少".$res_com['name'].' '.$res_com['jfname'].' '.$time.' null '.$v['money'].' \s';
			}else{
				if((float)$v['money']!=(float)$oldres2[$v['clid']]['newmoney']){
					$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$v['clid'])->find();
					$str.=$v['clid'].' '.$res_com['name'].' '.$res_com['jfname'].' '.$time.' '.$oldres2[$v['clid']]['newmoney'].' '.$v['money'].' \s';
				}
				if(($v['status']==-1 && $oldres2[$v['clid']]['status']!=9)){
					$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$v['clid'])->find();
					$str.=$v['clid'].' '.$res_com['name'].' '.$res_com['jfname'].' '.$time.' 已封禁 \s';
				}
				if(($v['status']!=-1 && $oldres2[$v['clid']]['status']==9)){
					$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$v['clid'])->find();
					$str.=$v['clid'].' '.$res_com['name'].' '.$res_com['jfname'].' '.$time.' 未封禁 \s';
				}
				unset($oldres2[$v['clid']]);
			}
		}
		foreach ($oldres2 as $key => $value) {
			$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$value['jfid'])->find();
			if($value['newmoney']!=0)$str.=$value['jfid'].' '.$res_com['name'].' '.$res_com['jfname'].' '.$time.' '.$oldres2[$v['clid']]['newmoney'].' null  数据多余 \s';
		}
		if($str!='')file_put_contents($this->filename, $str ,FILE_APPEND);
		if(count($data['data'])<1 || I('get.d')>=31){
			file_put_contents($this->filename, '总金额：'.$money ,FILE_APPEND);
			$this->nextrw();
		}
		echo '<script>window.location="?d='.($d+1)."&mo=$m&y=$y&money=$money".'"</script>';
		
	}
		//对比SSP平台的收入数据
	public function getsspindataandcheckmydata(){
		$y=date('Y',time()-3600*24*30);
		$m=date('m',time()-3600*24*30);
		if(!empty(I('post.strtime'))){
			$y=substr(I('post.strtime'), 0,4);
			$m=substr(I('post.strtime'), 5,2);
		}
		if(!empty(I('get.mo')))$m=I('get.mo');
		if(!empty(I('get.y')))$y=I('get.y');
		$d=I('get.d');
		if(strlen($m)==1)$m='0'.$m;
		if(strlen($d)==1)$d='0'.$d;
		$cs=$y.$m.$d;
		$url = "http://60.205.150.74/api/bosapi.php?action=index&method=income";
		$money=I('get.money');
		$mmoney=I('get.mmoney');
		$data['startTime']=$cs;
		$data['endTime']=$cs;
		
		$host = array("Host: sspadmin.youxiaoad.com");
		$ch = curl_init();
		$res= curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$host);
		$output = curl_exec ($ch);
		curl_close($ch);

		$str='';
		$data=json_decode($output,true);
		$time=$y.'-'.$m.'-'.$d;
		$oldres=M('daydata')->where("adddate='$time' && lineid in(2,40,44)")->select();
		foreach ($oldres as $k => $v) {
			$oldres2[$v['jfid']]=$v;
		}
		if(I('get.xq')=='gasidegg'){
			var_dump($oldres2);
			
			exit();
		}
		foreach ($data['data'] as $k => $v) {
			$mmoney+=$oldres2[$v['clid']]['newmoney'];
			$money+=$v['money'];
			echo $v['money'].' => '.$oldres2[$v['clid']]['newmoney'].'\s';
			if(empty($oldres2[$v['clid']]) && $v['status']!=0){
			$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$v['clid'])->find();
				$str.="缺少".$res_com['name'].' '.$res_com['jfname'].' '.$time.' null '.$v['money'].' \s';
			}else{

				if((float)$v['money']!=(float)$oldres2[$v['clid']]['newmoney']){
					$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$v['clid'])->find();
					$str.=$v['clid'].' '.$res_com['name'].' '.$res_com['jfname'].' '.$time.' '.$oldres2[$v['clid']]['newmoney'].' '.$v['money'].' \s';
				}
				if(($v['status']==-1 && $oldres2[$v['clid']]['status']!=9)){
					$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$v['clid'])->find();
					$str.=$v['clid'].' '.$res_com['name'].' '.$res_com['jfname'].' '.$time.' 已封禁 \s';
				}
				if(($v['status']!=-1 && $oldres2[$v['clid']]['status']==9)){
					$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$v['clid'])->find();
					$str.=$v['clid'].' '.$res_com['name'].' '.$res_com['jfname'].' '.$time.' 未封禁 \s';
				}
				unset($oldres2[$v['clid']]);
			}
		}
		
		
		foreach ($oldres2 as $key => $value) {
			$res_com=M('charging_logo')->field('a.name,b.name as jfname')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$value['jfid'])->find();
			if($value['newmoney']==0)unset($oldres2[$key]);
			if($value['newmoney']!=0)$str.=$res_com['name'].' '.$res_com['jfname'].' '.$time.' '.$value['jfid'].' null  数据多余 \s';
		}
		if($str!='')file_put_contents($this->filename, $str ,FILE_APPEND);
		if(count($data['data'])<1 || I('get.d')>=31){
			file_put_contents($this->filename, '总金额：'.$money.'=>'.$mmoney ,FILE_APPEND);
			$this->nextrw();
		}
		if(I('get.stop')==1)exit($str);
		echo '<script>window.location="?d='.($d+1)."&mo=$m&y=$y&money=$money&mmoney=$mmoney".'"</script>';
		
	}
	public function datatofenfa(){
		$p=I("get.p",1);
		$st=($p-1)*100;
		$res=M()->query("select group_concat(id) as id from(select id from boss_daydata where lineid=1 && adddate>='2017' && adddate<='2017-02-10' limit $st,100)z");
		if(strlen($res[0]['id'])<1)exit('ok');
		echo $res[0]['id'];
		$data=D('Daydata')->postDatatoFenfafroid($res[0]['id']);
		var_dump($data);
		echo '<script>window.location="?p='.($p+1).'"</script>';
	}
/*
	public function cis(){
		$p=I('get.p',0);
		if($p==0)M('daydata')->where("status!=0 && status!=9")->save(array('status'=>1));
		$data=M('daydata')->where("status!=0 && status!=9")->order('id desc')->limit(($p*100).',100')->select();
		foreach ($data as $key => $v) {
			$res=M('settlement_in')->where("strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && (allcomid like '".$v['comid'].",%' || allcomid like '%,".$v['comid']."' || allcomid like '%,".$v['comid'].",%' || allcomid='".$v['comid']."') && status!=6 && id>1224 && advid=".$v['adverid'])->order('status desc')->find();
			if($res['status']==1 && $v['status']!=2){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>2));
			}elseif($res['status']==2 && $v['status']!=3){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>3));
			}elseif($res['status']==3 && $v['status']!=4){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>4));
			}elseif($res['status']==4 && $v['status']!=5){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>5));
			}elseif($res['status']==5 && $v['status']!=8){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>8));
			}
		}
		if(count($data)<100)$this->redirect("CheckAllData/cis2");
		else echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	public function cis2(){
		$p=I('get.p',0);
		$data=M('settlement_in')->where("status!=6 && id<=1224 && status!=0")->order('id desc')->limit($p.',1')->select();
		if(count($data)==0)$this->redirect("CheckAllData/cos");
		$data=$data[0];
		if($data['alldataid']!=''){
			if(substr($data['alldataid'],0,1)==','){
				$data['alldataid']=substr($data['alldataid'],1);
				M('settlement_in')->where("id=".$data['id'])->save(array('alldataid'=>$data['alldataid']));
			}
			$where="id in (".$data['alldataid'].")";
		}else $where='1=0';
		if($data['status']!=5){
			echo $where;
			M('daydata')->where($where)->save(array('status'=>$data['status']+1));
		}elseif($data['status']==5) M('daydata')->where($where)->save(array('status'=>8));
		
		echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	public function cos(){
		$p=I('get.p',0);
		if($p==0)M('daydata_out')->where("status!=0 && status!=9")->save(array('status'=>1));
		$data=M('daydata_out')->where("status!=0 && status!=9")->order('id desc')->limit(($p*100).',100')->select();
		$str='';
		foreach ($data as $key => $v) {
			$res=M('settlement_out')->where("strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && (alljfid like '".$v['jfid'].",%' || alljfid like '%,".$v['jfid']."' || alljfid like '%,".$v['jfid'].",%' || alljfid='".$v['jfid']."') && status!=6 && id>1346 && superid='".$v['superid']."'")->order('status desc')->find();
			if($res['status']==1 && $v['status']!=2){
				M('daydata_out')->where("id=".$v['id'])->save(array('status'=>2));
			}elseif($res['status']==2 && $v['status']!=3){
				M('daydata_out')->where("id=".$v['id'])->save(array('status'=>3));
			}elseif($res['status']==4 && $v['status']!=4){
				M('daydata_out')->where("id=".$v['id'])->save(array('status'=>4));
			}
		}
		if(count($data)<100)$this->redirect("CheckAllData/cos2");
		else echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	public function cos2(){
		$p=I('get.p',0);
		$data=M('settlement_out')->where("status!=6 && id<=1348 && status!=0")->order('id desc')->limit($p.',1')->select();
		if(count($data)==0)exit('ok');
		$data=$data[0];
		if($data['alldataid']!=''){
			if(substr($data['alldataid'],0,1)==','){
				$data['alldataid']=substr($data['alldataid'],1);
				M('settlement_out')->where("id=".$data['id'])->save(array('alldataid'=>$data['alldataid']));
			}
			$where="id in (".$data['alldataid'].")";
		}
		else $where='1=0';
		if($data['status']<=2){
			M('daydata_out')->where($where)->save(array('status'=>$data['status']+1));
		}elseif($data['status']==4) M('daydata_out')->where($where)->save(array('status'=>4));
		
		echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	*/
	public function testfenfa(){
		$data=M('daydata')->field('id')->where("lineid=1")->order("id desc")->limit('0,1000')->select();
		foreach ($data as $key => $value) {
			$arr[]=$value['id'];
		}
		$str=implode(',', $arr);
			$data2=M('daydata')->field('replace(adddate,"-","") as adddate,jfid,id,status,lineid')->where("id in ($str)")->order('id')->select();
			foreach ($data2 as $key => $value) {
				$data_b=array();
				$data_b['bos_id']=$value['id'];
				$data_b['status']=$value['status'];
				$data_b['adddate']=$value['adddate'];
				$data_b['cl_id']=$value['jfid'];
				$json_data[]=$data_b;
			}
		var_dump(json_encode($json_data));
	}
	public function xieqiang(){
		$data=D('SettlementIn')->getonedata("id=".I('get.id'));
        $alldataid=D('Daydata')->editdataforcom($data['advid'],$data['salerid'],$data['lineid'],$data['strdate'],$data['enddate'],$data['allcomid'],$data['alljfid']);
        foreach ($alldataid as $key => $value) {
            $id_arr[]=$value['id'];
        }
        $id_str=implode(',',$id_arr);
        $result=D('Daydata')->postStatustoFenfafroid($id_str);
	}
}