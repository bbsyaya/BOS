<?php
namespace Home\Model;
use Think\Model;
use Common\Service;
class SettlementOutModel extends Model {
	public function getallduser($id){
		$arr=array();
		$u_data=M('user')->where("dept_id=$id")->select();
		foreach ($u_data as $key => $value) {
			$arr[]=$value['id'];
		}
		$d_data=M('user_department')->where("pid=$id")->select();
		if(count($d_data)>0){
			foreach ($d_data as $key => $value) {
				$arr_d=$this->getallduser($value['id']);
				$arr=array_merge($arr,$arr_d);
			}
		}
		return $arr;
	}
	public function getlist($where_o='',$ischeck=''){
		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
			else $wheres[]=$where_o;
		if(!empty(I('get.skname')))$wheres[]="g.fukuanname like '%".I('get.skname')."%'";
		if(!empty(I('get.ggzname')))$wheres[]="g.name like '%".I('get.ggzname')."%'";
		if(!empty(I('get.comname')))$wheres[]="a.allcomname like '%".I('get.comname')."%'";
		if(!empty(I('get.jfname')))$wheres[]="e.real_name like '%".I('get.jfname')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.addstrtime')))$wheres[]="a.addtime >= '".I('get.addstrtime')."'";
		if(!empty(I('get.addendtime')))$wheres[]="a.addtime <= '".I('get.addendtime')." 23:59:59'";
		if(!empty(I('get.email')))$wheres[]="g.email like '%".I('get.email')."%'";
		if(!empty(I('get.skname')))$wheres[]="f.payee_name like '%".I('get.skname')."%'";

		//ÅÐ¶Ïµ±Ç°ÓÃ»§ÊÇ·ñÖ»¶ÁÈ¡×Ô¼ºµÄÊý¾Ý-ÁÙÊ±´¦Àí£ºÉÌÎñ×¨Ô±Ö»¿´×Ô¼ºµÄÊý¾Ý£¬Ä£¿é£ººÏ×÷¹ÜÀí¡¢Êý¾Ý¹ÜÀí¡¢²ÆÎñ¹ÜÀí update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " e.real_name like '%".$spidStr."%'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.sangwuid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;


		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where=''; 
		if(!empty(I('get.id')))$where="a.id=".I('get.id');
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$data=M('settlement_out')->field('a.id,g.name as advname,a.allcomname as comname,a.strdate,a.enddate,a.settlementmoney,a.status,a.sangwuid,a.jsztid,a.superid,a.tax,a.notaxmoney,a.alljfid,f.payee_name as fukuanname')->join('a join boss_supplier g on a.superid=g.id join boss_charging_logo c on a.jfid=c.id join boss_product d on c.prot_id=d.id join boss_business_line b on d.bl_id=b.id join boss_user e on a.sangwuid=e.id join boss_supplier_finance f on a.addresserid=f.sp_id && a.lineid=f.bl_id')->where($where)->order('a.id desc')->limit($str.',10')->select();
		foreach ($data as $key => $value) {
			$u=M('user')->field("group_concat(real_name) as name")->where("id in (".$value['sangwuid'].")")->find();
			$data[$key]['real_name']=$u['name'];
		}
		return $data;
	}
	public function getidlist($where_o=''){
		if(!empty(I('param.allid')))$wheres[]="a.id in (".I('param.allid').")";
		$wheres[]=$where_o;
		if(!empty(I('get.skname')))$wheres[]="g.fukuanname like '%".I('get.skname')."%'";
		if(!empty(I('get.ggzname')))$wheres[]="g.name like '%".I('get.ggzname')."%'";
		if(!empty(I('get.comname')))$wheres[]="a.allcomname like '%".I('get.comname')."%'";
		if(!empty(I('get.jfname')))$wheres[]="e.real_name like '%".I('get.jfname')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.addstrtime')))$wheres[]="a.addtime >= '".I('get.addstrtime')."'";
		if(!empty(I('get.addendtime')))$wheres[]="a.addtime <= '".I('get.addendtime')." 23:59:59'";
		if(!empty(I('get.skname')))$wheres[]="f.payee_name like '%".I('get.skname')."%'";
		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where=''; 
		$data=M('settlement_out')->field('group_concat(a.id) as str')->join('a join boss_supplier g on a.superid=g.id join boss_charging_logo c on a.jfid=c.id join boss_product d on c.prot_id=d.id join boss_business_line b on d.bl_id=b.id join boss_user e on a.sangwuid=e.id join boss_supplier_finance f on a.addresserid=f.sp_id && a.lineid=f.bl_id')->where($where)->find();
		return $data['str'];
	}
	public function getOutSettlementStatus($where){
        $data=M('settlement_out')->field('id,status')->where($where)->select();
        return $data;
	}
	//ÇóºÍ
	public function  getSum($where_o='',$ischeck=''){
		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
		else $wheres[]=$where_o;
		if(!empty(I('get.ggzname')))$wheres[]="g.name like '%".I('get.ggzname')."%'";
		if(!empty(I('get.comname')))$wheres[]="a.allcomname like '%".I('get.comname')."%'";
		if(!empty(I('get.jfname')))$wheres[]="e.real_name like '%".I('get.jfname')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.addstrtime')))$wheres[]="a.addtime >= '".I('get.addstrtime')."'";
		if(!empty(I('get.addendtime')))$wheres[]="a.addtime <= '".I('get.addendtime')." 23:59:59'";
		if(!empty(I('get.skname')))$wheres[]="f.payee_name like '%".I('get.skname')."%'";

		
		//ÅÐ¶Ïµ±Ç°ÓÃ»§ÊÇ·ñÖ»¶ÁÈ¡×Ô¼ºµÄÊý¾Ý-ÁÙÊ±´¦Àí£ºÉÌÎñ×¨Ô±Ö»¿´×Ô¼ºµÄÊý¾Ý£¬Ä£¿é£ººÏ×÷¹ÜÀí¡¢Êý¾Ý¹ÜÀí¡¢²ÆÎñ¹ÜÀí update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " e.real_name like '%".$spidStr."%'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.sangwuid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;

		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		if(!empty(I('get.id')))$where="a.id=".I('get.id');
		$data=M('settlement_out')->field('ROUND(SUM(a.settlementmoney),2) as settlementmoney,ROUND(SUM(a.notaxmoney),2) as notaxmoney')->join('a join boss_supplier g on a.superid=g.id join boss_charging_logo c on a.jfid=c.id join boss_product d on c.prot_id=d.id join boss_business_line b on d.bl_id=b.id join boss_user e on a.sangwuid=e.id join boss_supplier_finance f on a.addresserid=f.sp_id && a.lineid=f.bl_id')->where($where)->select();
		return $data;
	}
	public function getlistcount($where_o,$ischeck=''){
		if(!empty(I('get.skname')))$wheres[]="g.fukuanname like '%".I('get.skname')."%'";
		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
			else $wheres[]=$where_o;
		if(!empty(I('get.ggzname')))$wheres[]="g.name like '%".I('get.ggzname')."%'";
		if(!empty(I('get.comname')))$wheres[]="a.allcomname like '%".I('get.comname')."%'";
		if(!empty(I('get.jfname')))$wheres[]="e.real_name like '%".I('get.jfname')."%'";
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(!empty(I('get.addstrtime')))$wheres[]="a.addtime >= '".I('get.addstrtime')."'";
		if(!empty(I('get.addendtime')))$wheres[]="a.addtime <= '".I('get.addendtime')." 23:59:59'";

		//ÅÐ¶Ïµ±Ç°ÓÃ»§ÊÇ·ñÖ»¶ÁÈ¡×Ô¼ºµÄÊý¾Ý-ÁÙÊ±´¦Àí£ºÉÌÎñ×¨Ô±Ö»¿´×Ô¼ºµÄÊý¾Ý£¬Ä£¿é£ººÏ×÷¹ÜÀí¡¢Êý¾Ý¹ÜÀí¡¢²ÆÎñ¹ÜÀí update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " e.real_name like '%".$spidStr."%'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.sangwuid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;
        

		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where=''; 
		if(!empty(I('get.id')))$where="a.id=".I('get.id');
		$data=M('settlement_out')->join('a join boss_supplier g on a.superid=g.id join boss_charging_logo c on a.jfid=c.id join boss_product d on c.prot_id=d.id join boss_business_line b on d.bl_id=b.id join boss_user e on a.sangwuid=e.id')->where($where)->count();
		return $data;
	}

	public function getFinanceListData(){
		if(!empty(I('get.status')))$wheres[]="a.status=".I('get.status');
			else $wheres[]="a.status in (2,3,4,5)";
		if(!empty(I('get.advname')))$wheres[]="g.name like '%".I('get.advname')."%'";
		if(!empty(I('get.comname')))$wheres[]="a.allcomname like '%".I('get.comname')."%'";
		if(!empty(I('get.salerid')))$wheres[]="a.sangwuid=".I('get.salerid');
		if(!empty(I('get.strtime')))$wheres[]="a.strdate >= '".I('get.strtime')."'";
		if(!empty(I('get.endtime')))$wheres[]="a.enddate <= '".I('get.endtime')."'";
		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		$data=M('settlement_out')->field('a.id,g.name as advname,d.name as comname,a.strdate,a.enddate,a.settlementmoney,e.real_name,a.status,a.tax,a.notaxmoney')->join('a join boss_supplier g on a.superid=g.id join boss_charging_logo c on a.jfid=c.id join boss_product d on c.prot_id=d.id join boss_business_line b on d.bl_id=b.id join boss_user e on a.sangwuid=e.id')->where($where)->select();
		return $data;
	}
	public function getdata($where=''){
		$M=M('settlement_out');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where=''){
		$M=M('settlement_out');
		$res=$M->where($where)->find();
		return $res;
	}
	public function adddata($data){
		$M=M('settlement_out');
		$res=$M->add($data);
		return $res;
	}
	public function getnum($where=''){
		return M('settlement_out')->where($where)->count();
	}
	public function edit($where='',$data=array()){
		$res=M('settlement_out')->where($where)->save($data);
		return $res;
	}
	public function deldata($where=''){
		$res=M('settlement_out')->where($where)->delete();
		return $res;
	}
	public function getonedatadetail(){
		$data = M('settlement_out')->field('c.name as supername,d.*,e.name as jszt,a.*')->join('a join boss_charging_logo b on a.jfid=b.id join boss_supplier c on a.superid=c.id left join boss_supplier_finance d on d.sp_id=a.addresserid && a.lineid=d.bl_id  join boss_data_dic e on a.jsztid=e.id')->where('a.id='.I('get.id'))->find();
		$u=M('user')->field("group_concat(real_name) as name")->where("id in (".$data['sangwuid'].")")->find();
		$data['real_name']=$u['name'];
		return $data;
	}
}