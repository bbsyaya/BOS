<?php
namespace Home\Model;
use Think\Model;
use Common\Service;
class SettlementInModel extends Model {
	public function getlist($isdown=0){

		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
			else $wheres[]="a.status != 0";
		if(!empty(I('get.advname')))$wheres[]="g.name like '%".I('get.advname')."%'";
		if(!empty(I('get.comname')))$wheres[]="b.name like '%".I('get.comname')."%'";
		if(!empty(I('get.salerid')))$wheres[]="f.real_name like '%".I('get.salerid')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.addstrtime')))$wheres[]="a.addtime >= '".I('get.addstrtime')."'";
		if(!empty(I('get.addendtime')))$wheres[]="a.addtime <= '".I('get.addendtime')." 23:59:59'";
		if(I('get.kaiPiao') !='')$wheres[]="a.iskaipiao = ".I('get.kaiPiao')." ";

		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " f.real_name like '%".$spidStr."%'";
        }

        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;


		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where=''; 
		if(!empty(I('get.id')))$where="a.id=".I('get.id');
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		if($isdown==0)$data=M('settlement_in')->field('a.basispath,a.settlementmoney,a.status,a.id,g.name as advname,b.name as comname,a.strdate,a.enddate,d.name as jszt,f.real_name,a.jsztid,a.salerid,a.advid,a.allcomid,a.iskaipiao,a.alljfid')->join('a join boss_product b on a.comid=b.id join boss_business_line c on a.lineid=c.id join boss_data_dic d on a.jsztid=d.id join boss_user f on a.salerid=f.id join boss_advertiser g on a.advid=g.id')->where($where)->order('a.id desc')->limit($str.',10')->select();
		else $data=M('settlement_in')->field('a.basispath,a.settlementmoney,a.status,a.id,g.name as advname,b.name as comname,a.strdate,a.enddate,d.name as jszt,f.real_name,a.jsztid,a.salerid,a.advid,a.allcomid,a.iskaipiao,a.alljfid')->join('a join boss_product b on a.comid=b.id join boss_business_line c on a.lineid=c.id join boss_data_dic d on a.jsztid=d.id join boss_user f on a.salerid=f.id join boss_advertiser g on a.advid=g.id')->where($where)->order('a.id desc')->select();
		return $data;
	}

	public function getSumIn(){
		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
		else $wheres[]="a.status != 0";
		if(!empty(I('get.advname')))$wheres[]="g.name like '%".I('get.advname')."%'";
		if(!empty(I('get.comname')))$wheres[]="b.name like '%".I('get.comname')."%'";
		if(!empty(I('get.salerid')))$wheres[]="f.real_name like '%".I('get.salerid')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.addstrtime')))$wheres[]="a.addtime >= '".I('get.addstrtime')."'";
		if(!empty(I('get.addendtime')))$wheres[]="a.addtime <= '".I('get.addendtime')." 23:59:59'";
		if(I('get.kaiPiao') !='')$wheres[]="a.iskaipiao = ".I('get.kaiPiao')." ";

		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " f.real_name like '%".$spidStr."%'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;


		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		if(!empty(I('get.id')))$where="a.id=".I('get.id');
		$data=M('settlement_in')->field('round(sum(a.settlementmoney),2) as settlementmoney')->join('a join boss_product b on a.comid=b.id join boss_business_line c on b.bl_id=c.id join boss_data_dic d on a.jsztid=d.id join boss_user f on a.salerid=f.id join boss_advertiser g on a.advid=g.id')->where($where)->select();

		return $data;
	}

	public function getlistcount(){
		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
		else $wheres[]="a.status != 0";
		if(!empty(I('get.advname')))$wheres[]="g.name like '%".I('get.advname')."%'";
		if(!empty(I('get.comname')))$wheres[]="b.name like '%".I('get.comname')."%'";
		if(!empty(I('get.salerid')))$wheres[]="f.real_name like '%".I('get.salerid')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.addstrtime')))$wheres[]="a.addtime >= '".I('get.addstrtime')."'";
		if(!empty(I('get.addendtime')))$wheres[]="a.addtime <= '".I('get.addendtime')." 23:59:59'";
		if(I('get.kaiPiao') !='')$wheres[]="a.iskaipiao = ".I('get.kaiPiao')." ";

		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " f.real_name like '%".$spidStr."%'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;

		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		if(!empty(I('get.id')))$where="a.id=".I('get.id');

		$data=M('settlement_in')->join('a join boss_product b on a.comid=b.id join boss_business_line c on b.bl_id=c.id join boss_data_dic d on a.jsztid=d.id join boss_user f on a.salerid=f.id join boss_advertiser g on a.advid=g.id')->where($where)->count();
		return $data;
	}
	public function getFinancelistdata($type=0){//应收列表
		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
			else $wheres[]="(a.status=3 || a.status=4 || a.status=2 || a.status=5)";
		if(!empty(I('get.ggzname')))$wheres[]="g.name like '%".I('get.ggzname')."%'";
		if(!empty(I('get.iskaipiao'))){
			$kp=I('get.iskaipiao');
			if($kp == 1){
				$wheres[]="a.iskaipiao = ".$kp;
			}elseif($kp == 2){
				$wheres[]="(a.iskaipiao =0 || a.iskaipiao ='')";
			}

		}
		if(!empty(I('get.comname')))$wheres[]="b.name like '%".I('get.comname')."%'";
		if(!empty(I('get.jfname')))$wheres[]="f.real_name like '%".I('get.jfname')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.jszt')))$wheres[]="a.jsztid=".I('get.jszt');

		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " f.real_name like '%".$spidStr."%'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;



		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where=''; 
		if(!empty(I('get.id')))$where="a.id=".I('get.id');
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		if($type==1)$data=M('settlement_in')->field('a.iskaipiao,ROUND(a.yskmoney,2) as yskmoney,a.basispath,ROUND(a.settlementmoney,2) as settlementmoney,a.status,a.id,g.name as advname,b.name as comname,a.strdate,a.enddate,d.name as jszt,concat(e.name,e.bank_no) as bankname,f.real_name')->join('a join boss_product b on a.comid=b.id join boss_business_line c on b.bl_id=c.id join boss_data_dic d on a.jsztid=d.id join boss_company_bankaccount e on c.sb_id=e.sb_id join boss_user f on b.saler_id=f.id join boss_advertiser g on a.advid=g.id')->where($where)->order('id desc')->select();
			else $data=M('settlement_in')->field('a.iskaipiao,ROUND(a.yskmoney,2) as yskmoney,a.basispath,ROUND(a.settlementmoney,2) as settlementmoney,a.status,a.id,g.name as advname,b.name as comname,a.strdate,a.enddate,d.name as jszt,concat(e.name,e.bank_no) as bankname,f.real_name')->join('a join boss_product b on a.comid=b.id join boss_business_line c on b.bl_id=c.id join boss_data_dic d on a.jsztid=d.id join boss_company_bankaccount e on c.sb_id=e.sb_id join boss_user f on a.salerid=f.id join boss_advertiser g on a.advid=g.id')->where($where)->order('id desc')->limit($str.',10')->select();
		return $data;
	}

	//应收求和
	public function getSum($type=0){

		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
		else $wheres[]="(a.status=3 || a.status=4 || a.status=2 || a.status=5)";
		if(!empty(I('get.ggzname')))$wheres[]="g.name like '%".I('get.ggzname')."%'";
		if(!empty(I('get.iskaipiao'))){
			$kp=I('get.iskaipiao');
			if($kp == 1){
				$wheres[]="a.iskaipiao = ".$kp;
			}elseif($kp == 2){
				$wheres[]="(a.iskaipiao =0 || a.iskaipiao ='')";
			}

		}
		if(!empty(I('get.comname')))$wheres[]="b.name like '%".I('get.comname')."%'";
		if(!empty(I('get.jfname')))$wheres[]="f.real_name like '%".I('get.jfname')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.jszt')))$wheres[]="a.jsztid=".I('get.jszt');

		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " f.real_name like '%".$spidStr."%'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;


        
		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		if(!empty(I('get.id')))$where="a.id=".I('get.id');

		if($type==1)$data=M('settlement_in')->field('ROUND(sum(a.yskmoney),2) as yskmoney,ROUND(sum(a.settlementmoney),2) as settlementmoney')->join('a join boss_product b on a.comid=b.id join boss_business_line c on b.bl_id=c.id join boss_data_dic d on a.jsztid=d.id join boss_company_bankaccount e on c.sb_id=e.sb_id join boss_user f on b.saler_id=f.id join boss_advertiser g on a.advid=g.id')->where($where)->select();
		else $data=M('settlement_in')->field('ROUND(sum(a.yskmoney),2) as yskmoney,ROUND(sum(a.settlementmoney),2) as settlementmoney')->join('a join boss_product b on a.comid=b.id join boss_business_line c on b.bl_id=c.id join boss_data_dic d on a.jsztid=d.id join boss_company_bankaccount e on c.sb_id=e.sb_id join boss_user f on a.salerid=f.id join boss_advertiser g on a.advid=g.id')->where($where)->select();
		//echo M('settlement_in')->getLastSql();exit;
		return $data;
	}

	public function getFcount(){
		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
			else $wheres[]="(a.status=3 || a.status=4 || a.status=2 || a.status=5)";
		if(!empty(I('get.ggzname')))$wheres[]="g.name like '%".I('get.ggzname')."%'";
		/*if(!empty(I('get.iskaipiao'))){
			$kp=I('get.iskaipiao');
			$wheres[]="a.iskaipiao = ".$kp;
		}*/
		if(!empty(I('get.iskaipiao'))){
			$kp=I('get.iskaipiao');
			if($kp == 1){
				$wheres[]="a.iskaipiao = ".$kp;
			}elseif($kp == 2){
				$wheres[]="(a.iskaipiao =0 || a.iskaipiao ='')";
			}

		}
		if(!empty(I('get.comname')))$wheres[]="b.name like '%".I('get.comname')."%'";
		if(!empty(I('get.jfname')))$wheres[]="f.real_name like '%".I('get.jfname')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.jszt')))$wheres[]="a.jsztid=".I('get.jszt');

		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " f.real_name like '%".$spidStr."%'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;



		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where=''; 
		if(!empty(I('get.id')))$where="a.id=".I('get.id');
		$data=M('settlement_in')->join('a join boss_product b on a.comid=b.id join boss_business_line c on b.bl_id=c.id join boss_data_dic d on a.jsztid=d.id join boss_user f on b.saler_id=f.id join boss_advertiser g on a.advid=g.id')->where($where)->count();
		return $data;
	}

	public function getlistForAdverid($id,$where=''){
		//数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where.= " && $myrule_data";
		$data=M('settlement_in')->field('a.id,a.strdate,a.enddate,b.name as comname,a.settlementmoney,a.yskmoney,e.real_name as salername,c.name as linename')->join('a join boss_product b on a.comid=b.id join boss_business_line c on b.bl_id=c.id join boss_user e on a.salerid=e.id')->where("a.advid=$id && a.settlementmoney!=a.yskmoney" .$where)->select();
		return $data;
	}
	public function getdata($where=''){
		$M=M('settlement_in');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where=''){
		$M=M('settlement_in');
		$res=$M->where($where)->find();
		return $res;
	}
	public function adddata($data){
		$M=M('settlement_in');
		$res=$M->add($data);
		return $res;
	}
	public function getnum($where=''){
		return M('settlement_in')->where($where)->count();
	}
	public function edit($where='',$data=array()){
		$res=M('settlement_in')->where($where)->save($data);
		return $res;
	}
	public function deldata($where=''){
		$res=M('settlement_in')->where($where)->delete();
		return $res;
	}
	public function getonedatadetail(){//获取详细页结算单信息
		return M('settlement_in')->field('c.name as advername,c.*,e.name as jszt,a.settlementmoney,a.strdate,a.enddate,f.name as username,f.mobile,f.address,g.real_name as salername,a.basispath,a.allcomid,a.invoiceer,a.invoicetime,a.auditer,a.audittime,a.invoiceinfo,a.expresscode,a.yskmoney,a.settlementmoney-a.yskmoney as dskmoney,a.advid,a.salerid,a.jsztid,a.iskaipiao,a.alldataid,a.lineid,a.alljfid')->join('a join boss_product z on a.comid=z.id join boss_business_line b on z.bl_id=b.id join boss_advertiser c on a.advid=c.id  join boss_data_dic e on a.jsztid=e.id left join boss_advertiser_fireceiver f on a.addresseeid=f.id join boss_user g on a.salerid=g.id')->where("a.id=".I('get.id'))->find();
	}
	
}