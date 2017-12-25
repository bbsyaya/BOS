<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class DaydataController extends BaseController {
    public function indata(){//数据录入
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
        $arr_name['line']=array('c.bl_id');
        $arr_name['user']=array('c.saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres2[]= $myrule_data;


        if(count($wheres2)>0)$where2=implode(' && ', $wheres2);
        else $where2='';
        $total=$alljfdata=M('ChargingLogo')->field('b.*,c.name as comname,d.name as advname')->join(' b join boss_product c on b.prot_id=c.id join boss_advertiser d on c.ad_id=d.id join boss_user e on c.saler_id=e.id 
join boss_charging_logo_assign f on f.cl_id=b.id')->where($where2)->count();
        $this->getpagelist($total);

    	$data=D('ChargingLogo')->getlistdata($where2);
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
                $olddata=D('Daydata')->getdata("jfid in (".implode(',',$jfidarr).") && status!=0 && adddate>='$strtime' && adddate<='$endtime'");
                $olddata2=D('Daydata')->getdata("jfid in (".implode(',',$jfidarr).") && status=0 && adddate>='$strtime' && adddate<='$endtime'");
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
    /*public function indataforexcel(){//导入数据
        $info=$this->uplaodfile('file',UPLOAD_INDATA_EXCEL_PATH);
        if(!is_array($info))$this->error('上传失败');
    	$file_name=UPLOAD_INDATA_EXCEL_PATH.$info['file']['savepath'].$info['file']['savename'];
        if(substr($info['file']['savename'],-4)=='xlsx')$exceltype='Excel2007';
        else $exceltype='Excel5';
        $data=$this->exceltoarray($file_name,$exceltype);
        $keyvaluearray=array('日期'=>'adddate','计费标识ID'=>'jfid','有效数据'=>'datanum');
        $jfid=$data[1]['计费标识ID'];
        $logo=D('BossChargingLogo')->where("id='$jfid'")->getonedata();//第一条数据对应计费标识
        $logos=D('BossChargingLogo')->where("prot_id={$logo['prot_id']}")->getdata();
        foreach ($logos as $key => $value) {
            $priceall[$value['id']]=$value['price'];
        }
        $beginnum=D('Daydata')->getnum("status=0");
        M()->startTrans();
        foreach ($data as $key => $value) {
            $newdata=array();
            $outdata=array();
            foreach($value as $k => $v){
                $newdata[$keyvaluearray[$k]]=$v;
            }
            $newdata['money']=$priceall[$newdata['jfid']]*$newdata['datanum'];
            $newdata['ismadeoutdata']=0;
            $cla=D('ChargingLogoAssign')->getdataforjfid($newdata['jfid'],$newdata['adddate']);
            if(count($cla)==1){
                $jfdata=$cla[0];
                $newdata['ismadeoutdata']=1;
                $outdata['adddate']=$newdata['adddate'];
                $outdata['jfid']=$jfdata['id'];
                $outdata['status']=1;
                $outdata['datanum']=round($newdata['datanum']*(1-$jfdata['check_ratio']));
                $outdata['money']=round($outdata['datanum']*($priceall[$newdata['jfid']]*$jfdata['price_ratio']),2);
                D('DaydataOut')->adddata($outdata);
            }
            D('Daydata')->adddata($newdata);
            
        }
        $endnum=D('Daydata')->getnum("status=0");

        if($endnum-$beginnum==count($data)-1){
            M()->commit();
            $this->assign('data',1);
            $this->display('Public/alertpage');
        }else{
            M()->rollback(); 
            $this->assign('data',0);
            $this->display('Public/alertpage');
        }
    }*/
    public function addDaydata(){//录入数据
        $data_io=M('daydata_inandout')->where("jfid=".I('post.jfid')." && adddate='".I('post.date')."'")->find();
        $data=D('Daydata')->getonedata("jfid=".I('post.jfid')." && adddate='".I('post.date')."'");
        $config_logores=D('ChargingLogo')->getdatainfoforjfid(I('post.jfid'));
        
        $price=I('post.price');
        
        if($data){
            D('Daydata')->edit("id=".$data['id'],array('datanum'=>I('post.datanum'),'newdata'=>I('post.datanum'),'remarks'=>I('post.remarks'),'money'=>$price*I('post.datanum'),'newmoney'=>$price*I('post.datanum'),'price'=>$price));
            $id=$data['id'];
            echo $data['id'];
        }else{
            
            $newdata=array('adddate'=>I('post.date'),'jfid'=>I('post.jfid'),'datanum'=>I('post.datanum'),'newdata'=>I('post.datanum'),'remarks'=>I('post.remarks'),'money'=>$price*I('post.datanum'),'newmoney'=>$price*I('post.datanum'),'status'=>0,'comid'=>$config_logores['prot_id'],'adverid'=>$config_logores['ad_id'],'salerid'=>$config_logores['saler_id'],'price'=>$price,'lineid'=>$config_logores['bl_id'],'ztid'=>$config_logores['sb_id']);
            $id=D('Daydata')->adddata($newdata);
            echo $id;
        }
        if($data_io){
            if($data_io['in_status']==0){
                M('daydata_inandout')->where("id=".$data_io['id'])->save(array('in_datanum'=>I('post.datanum'),'in_newdata'=>I('post.datanum'),'in_remarks'=>I('post.remarks'),'in_money'=>$price*I('post.datanum'),'in_newmoney'=>$price*I('post.datanum'),'in_price'=>$price,'in_status'=>0,'in_comid'=>$config_logores['prot_id'],'in_adverid'=>$config_logores['ad_id'],'in_salerid'=>$config_logores['saler_id'],'in_lineid'=>$config_logores['bl_id'],'in_ztid'=>$config_logores['sb_id'],'in_id'=>$id));
            }
        }else{
            $newdata=array('adddate'=>I('post.date'),'in_id'=>$id,'jfid'=>I('post.jfid'),'in_datanum'=>I('post.datanum'),'in_newdata'=>I('post.datanum'),'in_remarks'=>I('post.remarks'),'in_money'=>$price*I('post.datanum'),'in_newmoney'=>$price*I('post.datanum'),'in_status'=>0,'in_comid'=>$config_logores['prot_id'],'in_adverid'=>$config_logores['ad_id'],'in_salerid'=>$config_logores['saler_id'],'in_price'=>$price,'in_lineid'=>$config_logores['bl_id'],'in_ztid'=>$config_logores['sb_id']);
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
            $data=D('Daydata')->getonedata("id=".$value);
            if(!$data)continue;
            $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'提交审核','datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
            $idlist[]=$value;
        }
        M('daydata_inandout')->where("in_id in (".implode(',',$idlist).")")->save(array('in_status'=>1));
        $res=D('Daydata')->edit("id in (".implode(',',$idlist).")",$edit);
        if($res){
            $tb=D('Daydata')->postDatatoFenfafroid(implode(',',$idlist));
                if($tb->code!='1'){
                    M()->rollback();
                    $this->assign('data',$tb->code);
                    $this->display('Public/alertpage');
                    exit();
                }else{
                    foreach ($tb->data as $k => $v_tb) {
                        if($v_tb->status!='1')M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>2));
else M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>1));
                    }
                    M()->commit();
                    $this->assign('data',1);
                    $this->display('Public/alertpage');
                }
            checktbok(implode(',',$idlist));
        }else{
            M()->rollback();
            $this->assign('data',0);
            $this->display('Public/alertpage');
        }
    }

    public function audit(){//核检数据
       
        if(!empty(I('get.ggzname')))$wheres[]='d.name like "%'.I('get.ggzname').'%"';
        if(!empty(I('get.comname')))$wheres[]='c.name like "%'.I('get.comname').'%"';
        if(!empty(I('get.jfname')))$wheres[]='b.name like "%'.I('get.jfname').'%"';
        if(!empty(I('get.jfid')))$wheres[]='a.jfid like "%'.I('get.jfid').'%"';
        if(!empty(I('get.lineid'))){
            if(!empty(I('get.inanddata')))$wheres[]='a.in_lineid = "'.I('get.lineid').'"';
            else $wheres[]='a.lineid = "'.I('get.lineid').'"';
        }
        if(!empty(I('get.instatus'))){
            $w=array();
            foreach (I('get.instatus') as $key => $value) {
                if(!empty(I('get.inandout')))$w[]="a.in_status=".$value;
                else $w[]="a.status=".$value;
            }
            $wheres[]="(".implode(' || ',$w).")";
        }
        if(!empty(I('get.strtime')))$strtime=I('get.strtime');
        else $strtime=date('Y-m-d');
        $wheres[]='a.adddate >= "'.$strtime.'"';
        if(!empty(I('get.endtime')))$endtime=I('get.endtime');
        else $endtime=date('Y-m-d');
        $wheres[]='a.adddate <= "'.$endtime.'"';
        if(!empty(I('get.inanddata')))$wheres[]="a.in_status >= 1";
        else $wheres[]="a.status >= 1";

        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;


        if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='';
        if(!empty(I('get.inanddata')))$allcount=M('daydata_inandout')->join("a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.in_comid=c.id join boss_advertiser d on a.in_adverid=d.id")->where($where)->count();
        else $allcount=D('Daydata')->getlistcount($where);
        $this->getpagelist($allcount);
        if(!empty(I('get.inanddata'))){
            $p=I('get.p');
            if($p<1)$p=1;
            $str=($p-1)*10;
            $data=M('daydata_inandout')->field('a.id,a.adddate,a.jfid,a.in_newdata as datanum,a.in_newmoney as money,b.name as jfname,a.in_price as price,b.charging_mode as jftype,a.in_status as status,b.charging_mode,d.name as advname,c.name as comname,e.name as linename,f.name as jszt,g.real_name,a.in_lineid as lineid')->join("a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.in_comid=c.id join boss_advertiser d on a.in_adverid=d.id join boss_business_line e on a.in_lineid=e.id join boss_data_dic f on a.in_ztid=f.id join boss_user g on a.in_salerid=g.id")->where($where)->limit($str.',10')->select();
        }else $data=D('Daydata')->getlistdata($where);
        //foreach ($data as $key => $value) {
            //if($value['charging_mode']==3)$data[$key]['price']=$data[$key]['price']*1000;
        //}
        foreach ($data as $key => $value) {
            if($value['charging_mode']==4 || $value['charging_mode']==5)$data[$key]['datanum']=twonum($value['datanum']);
            else $data[$key]['datanum']=round($value['datanum']);
        }
        $this->assign('data',$data);
        if(!empty(I('get.inanddata')))$alldata=M('daydata_inandout')->field('sum(a.in_newdata) as allnum,sum(a.in_newmoney) as allmoney')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.in_comid=c.id join boss_advertiser d on a.in_adverid=d.id join boss_business_line e on a.in_lineid=e.id join boss_data_dic f on a.in_ztid=f.id join boss_user g on a.in_salerid=g.id')->where($where)->find();
        else $alldata=D('Daydata')->getalldata($where);
        $this->assign('alldata',$alldata);
        $linelist=M('business_line')->select();
        $this->assign('linelist',$linelist);
        if($this->checkRule('/home/passdata'))$this->checkRule_passdata=1;
        if($this->checkRule('/home/unpassdata'))$this->checkRule_unpassdata=1;
        if($this->checkRule('/home/changeadver'))$this->checkRule_changeadver=1;
        if($this->checkRule('/home/changecom'))$this->checkRule_changecom=1;
        if($this->checkRule('/home/changebuer'))$this->checkRule_changebuer=1;
        if($this->checkRule('/home/changeline'))$this->checkRule_changeline=1;
        if($this->checkRule('/home/changejszt'))$this->checkRule_changejszt=1;
        if($this->checkRule('/home/changeprice'))$this->checkRule_changeprice=1;
        if($this->checkRule('/home/deldata'))$this->checkRule_deldata=1;
        $this->display();
    }
    public function deldata(){//删除数据
        M('daydata_inandout')->where("in_id=".I('get.id'))->save(array('in_id'=>'','in_money'=>'','in_newmoney'=>'','in_datanum'=>'','in_newdata'=>'','in_comid'=>'','in_status'=>'','in_adverid'=>'','in_lineid'=>'','in_price'=>'','in_remarks'=>'','in_auditdate'=>'','in_salerid'=>'','in_banimgpath'=>'','in_ztid'=>'','in_ischeck'=>''));
        $res=D('Daydata')->deldata("id=".I('get.id'));
        if($res){
            $this->assign('data',1);
            $this->display('Public/alertpage');
        }else{
            $this->assign('data',0);
            $this->display('Public/alertpage');
        }
    }
    public function editnumormoney(){//修改数据
            $newmoney=I('post.money');
            $data_io=M('daydata_inandout')->field('a.*,b.charging_mode')->join('a join boss_charging_logo b on a.jfid=b.id')->where("a.in_id=".I('post.id'))->find();
            $data=M('Daydata')->field('a.*,b.charging_mode')->join('a join boss_charging_logo b on a.jfid=b.id')->where("a.id=".I('post.id'))->find();
            $price=I('post.price');
            $i=0;
            if($data['charging_mode']==4 || $data['charging_mode']==5)$i=2;
            //if($data['charging_mode']==3)$price=$price/1000;
            M()->startTrans();
            M('daydata_inandout')->where("in_id=".(int)I('post.id'))->save(array('in_newdata'=>round($newmoney/$price,$i),'in_newmoney'=>$newmoney));
            $res=D('Daydata')->edit('id='.(int)I('post.id'),array('newdata'=>round($newmoney/$price,$i),'newmoney'=>$newmoney));
            $logid=D('DaydataLog')->adddata(array('dataid'=>(int)I('post.id'),'remark'=>'数据修改 -> '.$newmoney,'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username'],'olddata'=>$data['newmoney']));
            $idlist=array(I('post.id'));

        if($res){
            $tb=D('Daydata')->postDatatoFenfafroid(implode(',',$idlist));
                if($tb->code!='1'){
                    M()->rollback();
                    echo '0';
                    exit();
                }else{
                    foreach ($tb->data as $k => $v_tb) {
                        if($v_tb->status!='1')M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>2));
else M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>1));
                    }
                    M()->commit();
                    echo '1';
                }
            checktbok(implode(',',$idlist));
        }else{
            M()->rollback();
            echo '0';
        }
    }
   public function banData(){//封禁数据
        $info=$this->uplaodfile('file',UPLOAD_BASIS_IMG_PATH);
        if(!is_array($info)){
            $this->assign('data',$info);
            $this->display('Public/alertpage');
            exit();
        }
        $file_name=UPLOAD_BASIS_IMG_PATH.$info['file']['savepath'].$info['file']['savename'];
        if(substr(I('post.idlist'),0,8)=='alldata_'){
            $url1=str_replace(array('alldata_http://bos3.yandui.com/Daydata/audit','alldata_http://devboss3.yandui.com/Daydata/audit'),'alldata_http://it.yandui.com/Daydata/audit', '',I('post.idlist'));
            $url=str_replace(array('html','?','&','=','amp;'),array('','/','/','/',''),substr($url1,1));
            $wherearr=explode('/',$url);
            for($i=0;$i<count($wherearr);$i+=2){
                $where_all[$wherearr[$i]]=urldecode($wherearr[$i+1]);
            }
            if(!empty($where_all['ggzname']))$wheres[]='d.name like "%'.$where_all['ggzname'].'%"';
            if(!empty($where_all['comname']))$wheres[]='c.name like "%'.$where_all['comname'].'%"';
            if(!empty($where_all['jfname']))$wheres[]='b.name like "%'.$where_all['jfname'].'%"';
            if(!empty($where_all['jfid']))$wheres[]='a.jfid like "%'.$where_all['jfid'].'%"';
            if(!empty(I('get.inanddata')))$wheres[]='a.in_lineid = "'.$where_all['lineid'].'"';
            if(!empty($where_all['lineid']))$wheres[]='a.lineid = "'.$where_all['lineid'].'"';
            if(!empty($where_all['strtime']))$strtime=$where_all['strtime'];
            else $strtime=date('Y-m-d');
            $wheres[]='a.adddate >= "'.$strtime.'"';
            if(!empty($where_all['endtime']))$endtime=$where_all['endtime'];
            else $endtime=date('Y-m-d');
            $wheres[]='a.adddate <= "'.$endtime.'"';
            $wheres[]="a.status = 1 && a.lineid!=2";
            if(count($wheres)>0)$where=implode(' && ', $wheres);
            else $where='';
            if(!empty(I('get.inanddata'))){
                $res_io=M('daydata_inandout')->field('a.in_id')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.in_comid=c.id join boss_advertiser d on a.in_adverid=d.id join boss_business_line e on a.in_lineid=e.id join boss_data_dic f on a.in_ztid=f.id join boss_user g on a.in_salerid=g.id')->where($where)->select();
                foreach ($data as $k => $v) {
                    $arr[]=$v['in_id'];
                }
                $idlist=implode(',',$arr);
            }else $idlist=D('Daydata')->getldlist($where);
        }else $idlist=substr(I('post.idlist'),0,-1);
        if(!empty(I('get.inanddata')))$data=M('daydata_inandout')->where("in_id in ($idlist) && in_status>1 && in_status != 9")->find();
        else $data=D('Daydata')->getonedata("id in ($idlist) && status > 1 && status != 9");
        if($data){
            $this->assign('data','部分封禁数据处于不能封禁状态');
            $this->display('Public/alertpage');
            exit();
        }
        M()->startTrans();
        $res=M('daydata_inandout')->where("in_id in ($idlist)")->save(array('in_status'=>9,'in_banimgpath'=>$file_name));
        $res=D('Daydata')->edit("id in ($idlist)",array('status'=>9,'banimgpath'=>$file_name));
        $idarr=explode(',', $idlist);
        foreach ($idarr as $key => $value) {
            $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'封禁数据','datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
        }
        if($res){
            $tb=D('Daydata')->postDatatoFenfafroid($idlist);
                if($tb->code!='1'){
                    M()->rollback();
                    $this->assign('data',$tb->code);
                    $this->display('Public/alertpage');
                    exit();
                }else{
                    foreach ($tb->data as $k => $v_tb) {
                        if($v_tb->status!='1')M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>2));
else M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>1));
                    }
                    M()->commit();
                    $this->assign('data',1);
                    $this->display('Public/alertpage');
                }
            checktbok(implode(',',$idlist));
        }else{
            M()->rollback();
            $this->assign('data',0);
            $this->display('Public/alertpage');
            exit();
        }
   }
   public function banEnd(){//解封数据
        if(substr(I('post.id'),0,8)=='alldata_'){
            $url1=str_replace(array('alldata_http://bos3.yandui.com/Daydata/audit','alldata_http://it.yandui.com/Daydata/audit','alldata_http://devboss3.yandui.com/Daydata/audit'), '',I('post.id'));
            
            $url=str_replace(array('html','?','&','=','amp;'),array('','/','/','/',''),substr($url1,1));
            $wherearr=explode('/',$url);
            for($i=0;$i<count($wherearr);$i+=2){
                $where_all[$wherearr[$i]]=urldecode($wherearr[$i+1]);
            }
            if(!empty($where_all['ggzname']))$wheres[]='d.name like "%'.$where_all['ggzname'].'%"';
            if(!empty($where_all['comname']))$wheres[]='c.name like "%'.$where_all['comname'].'%"';
            if(!empty($where_all['jfname']))$wheres[]='b.name like "%'.$where_all['jfname'].'%"';
            if(!empty($where_all['jfid']))$wheres[]='a.jfid like "%'.$where_all['jfid'].'%"';
            if(!empty($where_all['lineid']))$wheres[]='a.lineid = "'.$where_all['lineid'].'"';
            if(!empty(I('get.inanddata')))$wheres[]='a.in_lineid = "'.$where_all['lineid'].'"';
            if(!empty($where_all['strtime']))$strtime=$where_all['strtime'];
            else $strtime=date('Y-m-d');
            $wheres[]='a.adddate >= "'.$strtime.'"';
            if(!empty($where_all['endtime']))$endtime=$where_all['endtime'];
            else $endtime=date('Y-m-d');
            $wheres[]='a.adddate <= "'.$endtime.'"';
            $wheres[]="a.status = 9 && a.lineid!=2";
            if(count($wheres)>0)$where=implode(' && ', $wheres);
            else $where='';
            if(!empty(I('get.inanddata'))){
                $res_io=M('daydata_inandout')->field('a.in_id')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.in_comid=c.id join boss_advertiser d on a.in_adverid=d.id join boss_business_line e on a.in_lineid=e.id join boss_data_dic f on a.in_ztid=f.id join boss_user g on a.in_salerid=g.id')->where($where)->select();
                foreach ($data as $k => $v) {
                    $arr[]=$v['in_id'];
                }
                $idlist=implode(',',$arr);
            }else $idlist=D('Daydata')->getldlist($where);
        }else $idlist=substr(I('post.id'),0,-1);
        if(!empty(I('get.inanddata')))$data=M('daydata_inandout')->where("in_id in ($idlist) && in_status>1 && in_status != 9")->find();
        else $data=D('Daydata')->getonedata("id in ($idlist) && status > 1 && status != 9");
        if($data){
            echo '2';
            exit();
        }
        M()->startTrans();
        $res=M('daydata_inandout')->where("in_id in ($idlist)")->save(array('in_status'=>1,'in_banimgpath'=>''));
        $res=D('Daydata')->edit("id in ($idlist)",array('status'=>1,'banimgpath'=>''));
        $idarr=explode(',', $idlist);
        foreach ($idarr as $key => $value) {
            $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'解封数据','datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
        }
        if($res){
            $tb=D('Daydata')->postDatatoFenfafroid($idlist);
                if($tb->code!='1'){
                    M()->rollback();
                    echo '0';
                    exit();
                }else{
                    foreach ($tb->data as $k => $v_tb) {
                        if($v_tb->status!='1')M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>2));
else M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>1));
                    }
                    M()->commit();
                    echo '1';
                }
            checktbok(implode(',',$idlist));
            
        }else{
            M()->rollback();
            echo '0';
        }
   }
   public function changedata(){//更改广告主，产品,业务线，结算主体，单价或销售
        // print_r($_REQUEST);EXIT;
        if(empty(I('post.id')))exit('0');
        if(substr(I('post.id'),0,8)=='alldata_'){
            $url1=str_replace(array('alldata_http://bos3.yandui.com/Daydata/audit','alldata_http://localhost/Daydata/audit','alldata_http://it.yandui.com/Daydata/audit','alldata_http://devboss3.yandui.com/Daydata/audit','alldata_http://www.boss127.com/Daydata/audit'), '',I('post.id'));
            $url=str_replace(array('html','?','&','=','amp;'),array('','/','/','/',''),substr($url1,1));
            $wherearr=explode('/',$url);
            for($i=0;$i<count($wherearr);$i+=2){
                $where_all[$wherearr[$i]]=urldecode($wherearr[$i+1]);
            }

            if(!empty($where_all['ggzname']))$wheres[]='d.name like "%'.$where_all['ggzname'].'%"';
            if(!empty($where_all['comname']))$wheres[]='c.name like "%'.$where_all['comname'].'%"';
            if(!empty($where_all['jfname']))$wheres[]='b.name like "%'.$where_all['jfname'].'%"';
            if(!empty($where_all['jfid']))$wheres[]='a.jfid like "%'.$where_all['jfid'].'%"';
            if(!empty($where_all['lineid']))$wheres[]='a.in_lineid = "'.$where_all['lineid'].'"';
            if(!empty($where_all['strtime']))$strtime=$where_all['strtime'];
            else $strtime=date('Y-m-d');
            $wheres[]='a.adddate >= "'.$strtime.'"';
            if(!empty($where_all['endtime']))$endtime=$where_all['endtime'];
            else $endtime=date('Y-m-d');
            $wheres[]='a.adddate <= "'.$endtime.'"';
            $wheres[]="a.in_status = 1";
            if(count($wheres)>0)$where=implode(' && ', $wheres);
            else $where='';

            $res_io=M('daydata_inandout')->field('a.in_id')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on a.in_comid=c.id join boss_advertiser d on a.in_adverid=d.id join boss_business_line e on a.in_lineid=e.id join boss_data_dic f on a.in_ztid=f.id join boss_user g on a.in_salerid=g.id')->where($where)->select();

            foreach ($res_io as $k => $v) {
                $arr[]=$v['in_id'];
            }

            $idlist = implode(',',$arr);
            $idlist = empty($idlist)?0:$idlist;

        }else $idlist=substr(I('post.id'),0,-1);

        if(empty($idlist)){
            exit('0');
        }

        $logidarr=explode(',',$idlist);
        $olddata=M('Daydata')->where("id in ($idlist)")->select();
        $alllineid=1;
        foreach ($olddata as $k => $v) {
            $olddata2[$v['id']]=$v;
            if($v['lineid']==2)$alllineid=2;
        }
        switch (I('post.type')) {
            case '1'://广告主
                $action='更改广告主';
                $data=M('advertiser')->where("id=".I('post.toid'))->find();
                $name=$data['name'];
                $arr2=array('in_adverid'=>I('post.toid'));
                $arr=array('adverid'=>I('post.toid'));
                $zd='adverid';
                break;
            case '2'://产品
                $action='更改产品';
                $data=M('product')->where("id=".I('post.toid'))->find();
                $name=$data['name'];
                $arr2=array('in_comid'=>I('post.toid'));
                $arr=array('comid'=>I('post.toid'));
                $zd='comid';
                break;
            case '3'://销售
                $action='更改销售';
                $data=M('user')->where("id=".I('post.toid'))->find();
                $name=$data['real_name'];
                $arr2=array('in_salerid'=>I('post.toid'));
                $arr=array('salerid'=>I('post.toid'));
                $zd='salerid';
                break;
            case '4'://业务线
                if($alllineid==2){
                    exit('3');
                }
                $action='更改业务线';
                $data=M('business_line')->where("id=".I('post.toid'))->find();
                $name=$data['name'];
                $arr2=array('in_lineid'=>I('post.toid'));
                $arr=array('lineid'=>I('post.toid'));
                $zd='lineid';
                break;
            case '5'://结算主体
                $action='更改结算主体';
                $data=M('data_dic')->where("id=".I('post.toid'))->find();
                $name=$data['name'];
                $arr2=array('in_ztid'=>I('post.toid'));
                $arr=array('ztid'=>I('post.toid'));
                $zd='ztid';
                break;
            case '6'://单价
                if($alllineid==2){
                    exit('3');
                }
                $arr=array('price'=>I('post.toid'));
                $list=D('Daydata')->getdata("id in ($idlist)");
                foreach ($list as $key => $value) {
                    M('daydata_inandout')->where("in_id=".$value['id'])->save(array('in_price'=>I('post.toid'),'in_newmoney'=>round($value['newdata']*I('post.toid'),2)));
                    $arr['newmoney']=round($value['newdata']*I('post.toid'),2);
                    $res=D('Daydata')->edit("id = ".$value['id'],$arr);
                    $logid=D('DaydataLog')->adddata(array('dataid'=>$value['id'],'remark'=>'修改单价 -> '.I('post.toid'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username'],'olddata'=>$olddata2[$value['id']]['price']));
                }
                exit('1');
                break;
        }
        
        
        foreach ($logidarr as $key => $value) {
            $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>$action.' -> '.$name,'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username'],'olddata'=>$olddata2[$value][$zd]));
        }
        M()->startTrans();
        M('daydata_inandout')->where("in_id in ($idlist)")->save($arr2);
        $res=D('Daydata')->edit("id in ($idlist)",$arr);
        if($res){
            if($alllineid==2){
                M()->commit();
                    echo '1';
                    exit();
            }
            $tb=D('Daydata')->postDatatoFenfafroid($idlist);
                if($tb->code!='1'){
                    M()->rollback();
                    echo '2';
                    exit();
                }else{
                    foreach ($tb->data as $k => $v_tb) {
                        if($v_tb->status!='1')M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>2));
                        else M('daydata')->where("id=".$v_tb->bos_id)->save(array("istbok"=>1));
                    }
                    M()->commit();
                    echo '1';
                }
            checktbok(implode(',',$idlist));
        }else{
            M()->rollback();
            echo '2';
        }
   }
}