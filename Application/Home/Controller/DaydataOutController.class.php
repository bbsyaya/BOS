<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class DaydataOutController extends BaseController {
    public function index(){//数据录入

        if(!empty(I('get.jfname')))$wheres[]='b.name = "'.I('get.jfname').'"';
        if(!empty(I('get.comname')))$wheres[]='c.name = "'.I('get.comname').'"';
        $strtime=(!empty(I('get.strtime')))?I('get.strtime'):date('Y-m-').'01';
        $endtime=(!empty(I('get.endtime')))?I('get.endtime'):date('Y-m-d');
        $wheres[]='a.adddate >= "'.$strtime.'"';
        $wheres[]='a.adddate <= "'.$endtime.'"';
        $wheres[]="a.status=1";
        if(count($wheres)>0)$where=implode(' && ',$wheres);
        else $where='';
        $this->data=M('daydata_out')->field('b.name as jfname,a.newmoney,a.adddate,a.id,c.name as comname')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.prot_id=c.id')->where($where)->select();
    	$this->display();
    }

    public function outdata(){

        if(!empty(I('get.status')) && I('get.status')!=2)$wheres2[]="f.status=".I('get.status');
        else if(I('get.status')==2)$wheres2[]="f.status=0";
        else $wheres2[]="f.status=1";
        if(!empty(I('get.ggzname')))$wheres2[]='d.name like "%'.I('get.ggzname').'%"';
        if(!empty(I('get.comname')))$wheres2[]='c.name like "%'.I('get.comname').'%"';
        if(!empty(I('get.jfname')))$wheres2[]='b.name like "%'.I('get.jfname').'%"';
        if(!empty(I('get.jfid')))$wheres2[]='b.id = '.I('get.jfid');
        if(!empty(I('get.ywline')))$wheres2[]='c.bl_id = "'.I('get.ywline').'"';

        
        //判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
        $isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr   = $_SESSION["userinfo"]["realname"];
            $wheres2[] = 'b.name like "%'.$spidStr.'%"';
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('f.bl_id');
        $arr_name['user']=array('f.business_uid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres2[]= $myrule_data;
        

        if(count($wheres2)>0)$where2=implode(' && ', $wheres2);
        else $where2='';
        $total=$alljfdata=M('ChargingLogo')->field('b.*,c.name as comname,d.name as advname')->join('b join boss_product c on b.ad_id=c.id join boss_charging_logo_assign f on f.cl_id=b.id join boss_supplier d on f.sup_id=d.id')->where($where2)->group('b.id')->count();

        $this->getpagelist($total);

        $data=D('ChargingLogo')->getOutlistdata($where2);
        $strtime=(!empty(I('get.strtime')))?I('get.strtime'):date('Y-m-').'01';
        $endtime=(!empty(I('get.endtime')))?I('get.endtime'):date('Y-m-d');
        foreach ($data as $key => $value) {
            $jfidarr[]=$value['id'];
            for($i=strtotime($strtime);$i<=strtotime($endtime);$i+=3600*24){

                if(date('Y-m-d',$i)>=$value['promotion_stime'] && ($value['fstatus']!=0 || date('Y-m-d',$i)<=$value['promotion_etime']))$showdata[]=array_merge($value,array('date'=>date('Y-m-d',$i),'yzkey'=>$value['id'].'_'.date('Y-m-d',$i)));
            }
        }

        if(count($jfidarr)>0){
            if(!empty(I('get.inanddata'))){
                $olddata=M('daydata_inandout')->where("in_jfid in (".implode(',',$jfidarr).") && in_status!=0 && adddate>='$strtime' && adddate<='$endtime'")->select();
                $olddata2=M('daydata_inandout')->getdata("in_jfid in (".implode(',',$jfidarr).") && in_status=0 && adddate>='$strtime' && adddate<='$endtime'");
            }else{
                $olddata=D('DaydataOut')->getdata("jfid in (".implode(',',$jfidarr).") && status!=0 && adddate>='$strtime' && adddate<='$endtime'");
                $olddata2=D('DaydataOut')->getdata("jfid in (".implode(',',$jfidarr).") && status=0 && adddate>='$strtime' && adddate<='$endtime'");
            }

            foreach ($olddata as $key => $value) {
                $oldidarr[]=$value['jfid'].'_'.$value['adddate'];
            }
            foreach ($olddata2 as $key => $value) {
                $olddata3[$value['jfid'].'_'.$value['adddate']]=$value;
                $oldidarr2[]=$value['jfid'].'_'.$value['adddate'];
            }
            foreach ($showdata as $key => $value) {

                if(in_array($value['yzkey'],$oldidarr))unset($showdata[$key]);
                if(in_array($value['yzkey'],$oldidarr2)){
                    $showdata[$key]['datanum']=$olddata3[$value['yzkey']]['datanum'];
                    $showdata[$key]['money']=$olddata3[$value['yzkey']]['money'];
                    $showdata[$key]['yzkey']=$olddata3[$value['yzkey']]['id'];
                    if($showdata[$key]['price_type']!=2){
                        $showdata[$key]['price']=$olddata3[$value['yzkey']]['price'];
                        if($showdata[$key]['charging_mode']==3)$showdata[$key]['price']=$showdata[$key]['price']*1000;
                    }
                    $showdata[$key]['remarks']=$olddata3[$value['yzkey']]['remarks'];
                }

            }
        }
        $this->assign('data',$showdata);
        $ywlinelist=M('Business_line')->select();
        $this->assign('ywline',$ywlinelist);
        $this->display();
    }

    public function edit(){
    	if(!empty(I('post.id'))){
            $data=M('daydata_out')->where("id=".I('post.id'))->find();
    		M('daydata_out')->where("id=".I('post.id'))->save(array('newmoney'=>I('post.money')));
            M('daydata_inandout')->where('out_id='.I('post.id'))->save(array('out_newmoney'=>I('post.money')));
    		$logid=D('DaydataLog')->adddata(array('dataid'=>I('post.id'),'remark'=>'修改金额 -> '.I('post.money'),'datatype'=>2,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username'],'olddata'=>$data['newmoney']));
    		exit('完成');
    	}
    	$this->data=M('daydata_out')->where("id=".I('get.id'))->find();
    	$this->display();
    }

    public function add(){
        if(!empty(I('post.id'))){
            $jfid=M('charging_logo')->where("name='".I('post.jfname')."'")->find();
            $sangwu=M('user')->where("real_name='".I('post.sw')."'")->find();
            $sbid=M('data_dic')->where("name='".I('post.jszt')."'")->find();
            $super=M('supplier')->where("name='".I('post.super')."'")->find();
            $line=M('business_line')->where("name='".I('post.line')."'")->find();
            if(!$jid || !$sangwu || !$line || !$super || !$sbid){
                exit('没有找到相应的基础数据');
            }
            $res=M('daydata_out')->where("adddate='".I('post.adddate')."' && jfid=".$jfid['id'])->find();
            if($res){
                exit('此计费标识已有当日数据');
            }
            $id=M('daydata_out')->add(array('jfid'=>$jfid['id'],'superid'=>$super['id'],'businessid'=>$sangwu['id'],'lineid'=>$line['id'],'price'=>I('post.price'),'adddate'=>I('post.adddate'),'datanum'=>I('post.data'),'newdata'=>I('post.data'),'money'=>I('post.money'),'newmoney'=>I('post.money'),'sbid'=>$sbid['id']));
            $res_io=M('daydata_inandout')->where('jfid='.$jfid['id'].' && adddate="'.I('post.adddate').'"')->find();
            if($res_io){
                    M('daydata_inandout')->where('id='.$res_io['id'])->save(array('out_newdata'=>I('post.data'),'out_newmoney'=>I('post.money'),'out_status'=>1,'out_superid'=>$super['id'],'out_businessid'=>$sangwu['id'],'out_price'=>I('post.price'),'out_lineid'=>$line['id'],'out_sbid'=>$sbid['id'],'out_datanum'=>I('post.data'),'out_money'=>I('post.money'),'out_id'=>$id));
            }else{
                 M('daydata_inandout')->add(array('adddate'=>I('post.adddate'),'jfid'=>$jfid['id'],'out_newdata'=>I('post.data'),'out_newmoney'=>I('post.money'),'out_status'=>1,'out_superid'=>$super['id'],'out_businessid'=>$sangwu['id'],'out_price'=>I('post.price'),'out_lineid'=>$line['id'],'out_sbid'=>$sbid['id'],'out_datanum'=>I('post.data'),'out_money'=>I('post.money'),'out_id'=>$id));
            }
            $logid=D('DaydataLog')->adddata(array('dataid'=>I('post.id'),'remark'=>'修改金额 -> '.I('post.money'),'datatype'=>2,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
            echo '添加完成';
        }
        $this->display();
    }

    public function addDaydata(){//成本录入数据
        $data_io=M('daydata_inandout')->where("jfid=".I('post.jfid')." && adddate='".I('post.date')."'")->find();
        $data=D('DaydataOut')->getonedata("jfid=".I('post.jfid')." && adddate='".I('post.date')."'");
        $config_logores=D('ChargingLogo')->getdataoutjfid(I('post.jfid'));
        //echo D('ChargingLogo')->getLastSql();exit;
        $price=I('post.price');

        if($data){
            D('DaydataOut')->edit("id=".$data['id'],array('datanum'=>I('post.datanum'),'newdata'=>I('post.datanum'),'remarks'=>I('post.remarks'),'money'=>$price*I('post.datanum'),'newmoney'=>$price*I('post.datanum'),'price'=>$price));
            $id=$data['id'];
            echo $data['id'];
        }else{

            $newdata=array('adddate'=>I('post.date'),'jfid'=>I('post.jfid'),'datanum'=>I('post.datanum'),'newdata'=>I('post.datanum'),'remarks'=>I('post.remarks'),'money'=>$price*I('post.datanum'),'newmoney'=>$price*I('post.datanum'),'status'=>0,'superid'=>$config_logores['sup_id'],'businessid'=>$config_logores['business_uid'],'price'=>$price,'lineid'=>$config_logores['bl_id'],'sbid'=>$config_logores['sb_id']);
            $id=D('DaydataOut')->adddata($newdata);
            echo $id;
        }
        if($data_io){
            if($data_io['out_status']==0){
                M('daydata_inandout')->where("id=".$data_io['id'])->save(array('out_datanum'=>I('post.datanum'),'out_newdata'=>I('post.datanum'),'out_remarks'=>I('post.remarks'),'out_money'=>$price*I('post.datanum'),'out_newmoney'=>$price*I('post.datanum'),'out_price'=>$price,'out_status'=>0,'out_superid'=>$config_logores['sup_id'],'out_businessid'=>$config_logores['business_uid'],'out_price'=>$price,'out_lineid'=>$config_logores['bl_id'],'out_sbid'=>$config_logores['sb_id'],'out_id'=>$id));
            }
        }else{
            $newdata=array('adddate'=>I('post.date'),'out_id'=>$id,'jfid'=>I('post.jfid'),'out_datanum'=>I('post.datanum'),'out_newdata'=>I('post.datanum'),'out_remarks'=>I('post.remarks'),'out_money'=>$price*I('post.datanum'),'out_newmoney'=>$price*I('post.datanum'),'out_status'=>0,'out_superid'=>$config_logores['sup_id'],'out_businessid'=>$config_logores['business_uid'],'out_price'=>$price,'out_lineid'=>$config_logores['bl_id'],'out_sbid'=>$config_logores['sb_id']);
            M('daydata_inandout')->add($newdata);
        }
    }

    public function subaudit(){//提交审核
        if(empty(I('post.idlist'))){
            $this->assign('data','你没有提交任何数据');
            $this->display('Public/alertpage');
        }
        M()->startTrans();
        $edit=array('status'=>1);
        foreach (I('post.idlist') as $key => $value) {
            if(!is_numeric($value)){
                continue;
            }
            $data=D('DaydataOut')->getonedata("id=".$value);
            if(!$data)continue;
            $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'提交审核','datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
            $idlist[]=$value;
        }
        M('daydata_inandout')->where("out_id in (".implode(',',$idlist).")")->save(array('out_status'=>1));
        $res=D('DaydataOut')->edit("id in (".implode(',',$idlist).")",$edit);
        if($res){
            $tb=D('DaydataOut')->postDatatoFenfafroid(implode(',',$idlist));
            if($tb->code!='1'){
                M()->rollback();
                $this->assign('data',$tb->code);
                $this->display('Public/alertpage');
                exit();
            }
            M()->commit();
            $this->assign('data',1);
            $this->display('Public/alertpage');
        }else{
            M()->rollback();
            $this->assign('data',0);
            $this->display('Public/alertpage');
        }
    }

}