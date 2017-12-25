<?php
namespace Api\Controller;
//供应商
class DataController extends ApiController {
    public function addOneDayInDataApi(){//同步收入数据接口
        //{datanum:数量，remarks：备注，price：单价，date：时间，jfid：计费标识ID，type:操作类型(1:添加，2：删除)，username:操作人}
            $data=json_decode(htmlspecialchars_decode(I('post.datajson')),true);
            $allid=array();
            foreach ($data as $key => $v) {
                if((I('post.appid')=='101' && $v['lineid']!=2 && $v['lineid']!=44) || (I('post.appid')=='104' && $v['lineid']!=1 && $v['lineid']!=42 && $v['lineid']!=34) || (I('post.appid')=='103' && $v['lineid']!=31) || (I('post.appid')=='107' && $v['lineid']!=46)){
                    $status=2;
                    $remark='业务线不对应';
                }
                if($v['type']==2){
                    $res=D('Home/Daydata')->getonedata('jfid='.$v['cl_id'].' && adddate="'.$v['date'].'"');
                    //M('daydata_inandout')->where('jfid='.$v['cl_id'].' && adddate="'.$v['date'].'" && in_id>0')->find();
                    $id=$res['id'];
                    if($res && $res['status']<2){
                            D("Home/Daydata")->edit("jfid=".$v['cl_id']." && adddate='".$v['date']."'",array('newdata'=>0,'newmoney'=>0,'type'=>2));
                            M('daydata_inandout')->where('jfid='.$v['cl_id'].' && adddate="'.$v['date'].'"')->save(array('in_newdata'=>0,'in_newmoney'=>0));
                        $status=1;
                        $remark='操作成功';
                    }elseif($res && $res['status']>=2){
                        $status=2;
                        $remark='状态错误';
                    }elseif(!$res){
                        $status=1;
                        $remark='没有发现此数据';
                    }else{
                        $status=2;
                        $remark='未知错误';
                    }
                    $return[]=array('clid'=>$v['cl_id'],'id'=>$id,'msg'=>$remark,'status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                }else{
                    $remark='';
                    if(empty($v['date']))$remark='缺少date参数';
                    if(empty($v['cl_id']))$remark='缺少cl_id参数';
                    if($v['datanum']==='')$remark='缺少datanum参数';
                    if(empty($v['price']))$remark='缺少price参数';
                    if(!isset($v['is_pass']))$remark='缺少is_pass参数';   
                    $issetok=1;
                    if(!empty($set_data)){
                        $jfidarr=explode(',', $set_data['alljfid']);
                        if(strtotime($set_data['strdate'])<=strtotime($v['date']) && strtotime($set_data['enddate'])>=strtotime($v['date']) && in_array($v['cl_id'],$jfidarr)) $issetok=0;
                    }
                    if($issetok==1)$set_data=M('settlement_in')->where("find_in_set(".$v['cl_id'].",alljfid) && strdate<='".$v['date']."' && enddate>='".$v['date']."' && status!=6 && status!=0")->find();
                    if($set_data || $issetok==0){
                        $remark='对应计费标识当前时间已生成结算单，不可更改';
                    }
                    if(!empty($remark)){
                        $status=2;
                        $return[]=array('clid'=>$v['cl_id'],'msg'=>$remark,'status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                        continue;
                    }
                    $config_logores=D('Home/ChargingLogo')->getdatainfoforjfid($v['cl_id']);
                    if($config_logores['charging_mode']=='3'){
                        $price=$price/1000;
                    }
                    if($v['is_pass']==1)$d_status=1;
                    else $d_status=9;
                    
                    if($v['money']!==''){
                        $money=$v['money'];
                    }else{
                        $money=$v['price']*$v['datanum'];
                    }
                    $newdata=array('adddate'=>$v['date'],'jfid'=>$v['cl_id'],'datanum'=>$v['datanum'],'newdata'=>$v['datanum'],'remarks'=>$v['remarks'],'money'=>$money,'newmoney'=>$money,'status'=>$d_status,'comid'=>$config_logores['prot_id'],'adverid'=>$config_logores['ad_id'],'salerid'=>$config_logores['saler_id'],'price'=>$v['price'],'lineid'=>$config_logores['bl_id'],'ztid'=>$config_logores['sb_id'],'superid'=>$v['superid'],'type'=>1,'reason'=>$v['reason']);
                    $res=D('Home/Daydata')->field('id,status,newdata,newmoney,type')->getonedata('jfid='.$v['cl_id'].' && adddate="'.$v['date'].'"');
                    
                    if($res){
                        
                        if($res['status']>1 && $res['status']!=9){
                            $status=2;
                            $return[]=array('clid'=>$v['cl_id'],'id'=>$id,'msg'=>'此数据已不可修改','status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                            continue;
                        }
                        if($res['newmoney']==$money && $res['newdata']==$v['datanum'] && $res['type']==1 && $res['status']==$d_status){
                            $return[]=array('clid'=>$v['cl_id'],'id'=>$id,'msg'=>'与原数据无差异','status'=>1,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                                continue;
                        }
                        unset($newdata['datanum']);
                        unset($newdata['money']);
                        $bool_n=D("Home/Daydata")->edit("id=".$res['id'],$newdata);
                        if(!$bool_n){
                            $status=2;
                            $return[]=array('clid'=>$v['cl_id'],'id'=>$id,'msg'=>'数据库修改失败','status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                            continue;
                        }
                        $id=$res['id'];
                    }else{
                        $id=D('Home/Daydata')->adddata($newdata);
                    }
                    $allid[]=$id;
                    $logid=D('Home/DaydataLog')->adddata(array('dataid'=>$id,'remark'=>'数据同步','datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$v['username']));
                    if($id){
                        $status=1;
                        $remark='操作成功';
                    }else{
                        $status=2;
                        $remark='操作失败';
                    }
                    $return[]=array('clid'=>$v['cl_id'],'id'=>$id,'msg'=>$remark,'status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                }
            }
            $errcode=0;
            $allid_str=implode(',', $allid);
            if(count($allid)>0){
                $ssp_str=($v['lineid']==2)?",a.out_superid=b.superid":'';
                M()->execute("update boss_daydata_inandout a join boss_daydata b on a.adddate=b.adddate && a.jfid=b.jfid set a.in_datanum=b.datanum,a.in_newdata=b.newdata,a.in_remarks=b.remarks,a.in_money=b.money,a.in_newmoney=b.newmoney,a.in_status=b.status,a.in_comid=b.comid,a.in_adverid=b.adverid,a.in_salerid=b.salerid,a.in_price=b.price,a.in_lineid=b.lineid,a.in_ztid=b.ztid,a.in_id=b.id".$ssp_str." where b.id in ($allid_str)");
                $yyid=M('daydata_inandout')->field('in_id')->where("in_id in ($allid_str)")->select();
                $yyid_arr=array();
                foreach ($yyid as $k => $v) {
                    $yyid_arr[]=$v['in_id'];
                }
                $allid=array_diff($allid, $yyid_arr);

                if(count($allid)>0){
                    $allid_str=implode(',', $allid);
                    $ssp_str1=($v['lineid']==2)?",out_superid":'';
                    $ssp_str2=($v['lineid']==2)?",a.superid":'';
                    M()->execute("INSERT into boss_daydata_inandout(jfid,adddate,in_id,in_money,in_newmoney,in_datanum,in_newdata,in_comid,in_status,in_adverid,in_lineid,in_price,in_remarks,in_auditdate,in_salerid,in_banimgpath,in_ztid,in_ischeck".$ssp_str1.") select a.jfid, a.adddate,a.id,a.money,a.newmoney,a.datanum,a.newdata,a.comid,a.status,a.adverid,a.lineid,a.price,a.remarks,a.auditdate,a.salerid,a.banimgpath,a.ztid,a.is_check".$ssp_str2." from boss_daydata a where a.id in ($allid_str)");
                }
            }
            $this->response(array('status'=>$status,'data'=>$return,'errcode'=>$errcode));
    }
    public function addOneDayOutDataApi(){//同步成本数据接口
        //{datanum:数量，remarks：备注，price：单价，date：时间，jfid：计费标识ID，type:操作类型(1:添加，2：删除)，username:操作人,superid:供应商ID，businessid：商务ID，lineid：业务线id,'sbid':结算主体ID}
            $data=json_decode(htmlspecialchars_decode(I('post.datajson')),true);
            foreach ($data as $key => $v) {
                if((I('post.appid')=='101' && $v['lineid']!=2 && $v['lineid']!=44) || (I('post.appid')=='104' && $v['lineid']!=1 && $v['lineid']!=42 && $v['lineid']!=34) || (I('post.appid')=='103' && $v['lineid']!=31) || (I('post.appid')=='107' && $v['lineid']!=46)){
                    $status=2;
                    $remark='业务线不对应';
                }
                if($v['type']==2){
                    $res=D('Home/DaydataOut')->getonedata('jfid='.$v['cl_id'].' && adddate="'.$v['date'].'"');
                    //M('daydata_inandout')->where('jfid='.$v['cl_id'].' && adddate="'.$v['date'].'" && out_id>0')->find();
                    $id=$res['id'];
                    if($res && $res['status']<2){
                        D("Home/DaydataOut")->edit("jfid=".$v['cl_id']." && adddate='".$v['date']."'",array('newdata'=>0,'newmoney'=>0,'type'=>2));
                        M('daydata_inandout')->where('jfid='.$v['cl_id'].' && adddate="'.$v['date'].'"')->save(array('out_newdata'=>0,'out_newmoney'=>0));
                        $status=1;
                        $remark='操作成功';
                    }elseif($res['status']>=2){
                        $status=2;
                        $remark='状态错误';
                    }elseif(!$res){
                        $status=1;
                        $remark='没有发现此数据';
                    }else{
                        $status=2;
                        $remark='未知错误';
                    }
                    $return[]=array('clid'=>$v['cl_id'],'id'=>$id,'msg'=>$remark,'status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                }else{
                    $remark='';
                    if(empty($v['date']))$remark='缺少date参数';
                    if(empty($v['cl_id']))$remark='缺少cl_id参数';
                    if($v['datanum']==='')$remark='缺少datanum参数';
                    if(empty($v['superid']) || $v['superid']==0)$remark='缺少superid参数';
                    if(empty($v['businessid']) || $v['businessid']==0)$remark='缺少businessid参数';
                    if(empty($v['price']))$remark='缺少price参数';
                    if(empty($v['lineid']) || $v['lineid']==0)$remark='缺少lineid参数';
                    if(empty($v['sbid']) || $v['sbid']==0)$remark='缺少sbid参数';  
                    if(empty($v['is_pass']))$remark='缺少is_pass参数';   
                    $issetok=1;
                    if(!empty($set_data)){
                        $jfidarr=explode(',', $set_data['alljfid']);
                        if(strtotime($set_data['strdate'])<=strtotime($v['date']) && strtotime($set_data['enddate'])>=strtotime($v['date']) && in_array($v['cl_id'],$jfidarr)) $issetok=0;
                    }
                    if($issetok==1)$set_data=M('settlement_out')->where("find_in_set(".$v['cl_id'].",alljfid) && strdate<='".$v['date']."' && enddate>='".$v['date']."' && status!=6 && status!=0")->find();
                    if($set_data || $issetok==0){
                        $remark='对应计费标识当前时间已生成结算单，不可更改';
                    }
                    if(!empty($remark)){
                        $status=2;
                        $return[]=array('clid'=>$v['cl_id'],'id'=>$id,'msg'=>$remark,'status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                        continue;
                    }
                    $config_logores=D('Home/ChargingLogo')->getonedata("id=".$v['cl_id']);
                    if($config_logores['charging_mode']=='3'){
                        $price=$price/1000;
                    }
                    if($v['is_pass']==1)$d_status=1;
                    else $d_status=9;
                    if($v['money']!==''){
                    	$money=$v['money'];
                    }else{
                    	$money=$v['price']*$v['datanum'];
                    }
                    $newdata=array('adddate'=>$v['date'],'jfid'=>$v['cl_id'],'datanum'=>$v['datanum'],'newdata'=>$v['datanum'],'remarks'=>$v['remarks'],'money'=>$money,'newmoney'=>$money,'status'=>$d_status,'superid'=>$v['superid'],'businessid'=>$v['businessid'],'price'=>$v['price'],'lineid'=>$v['lineid'],'sbid'=>$v['sbid'],'type'=>1,'adduid'=>$v['username'],'reason'=>$v['reason']);
                    $res=D('Home/DaydataOut')->field('id,status,newmoney,newdata,businessid,sbid,superid,type')->getonedata('jfid='.$v['cl_id'].' && adddate="'.$v['date'].'"');
                    
                    if($res){

                        if($res['status']>1 && $res['status']!=9){
                            $status=2;
                            $return[]=array('clid'=>$v['cl_id'],'msg'=>'此数据已不可修改','status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                            continue;
                        }
                        if($res['newmoney']==$money && $res['newdata']==$v['datanum'] && $res['businessid']=$v['businessid'] && $res['sbid']==$v['sbid'] && $res['superid']==$v['superid'] && $res['type']==1 && $res['status']==$d_status){
                            $return[]=array('clid'=>$v['cl_id'],'id'=>$id,'msg'=>'与原数据无差异','status'=>1,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                                continue;
                        }
                        unset($newdata['datanum']);
                        unset($newdata['money']);
                        $bool_n=D("Home/DaydataOut")->edit("id=".$res['id'],$newdata);
                        if(!$bool_n){
                            $status=2;
                            $return[]=array('clid'=>$v['cl_id'],'msg'=>'数据库修改失败','status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                            continue;
                        }
                        $id=$res['id'];
                    }else{
                        $id=D('Home/DaydataOut')->adddata($newdata);
                        
                    }
                    $allid_arr[]=$id;
                   
                    $logid=D('Home/DaydataLog')->adddata(array('dataid'=>$id,'remark'=>'数据同步','datatype'=>2,'addtime'=>date('Y-m-d H:i:s'),'username'=>$v['username']));
                    if($id){
                        $status=1;
                        $remark='操作成功';
                    }else{
                        $status=2;
                        $remark='操作失败';
                    }
                    $return[]=array('clid'=>$v['cl_id'],'id'=>$id,'msg'=>$remark,'status'=>$status,'date'=>$v['date'],'senddata_id'=>$v['senddata_id']);
                }
            }
            $errcode=0;
            $allid_str=implode(',', $allid_arr);


            if(count($allid_arr)>0){
                $ssp_str=($v['lineid']==2)?",a.out_superid=b.superid":'';
                M()->execute("update boss_daydata_inandout a join boss_daydata_out b on a.adddate=b.adddate && a.jfid=b.jfid set a.out_datanum=b.datanum,a.out_newdata=b.newdata,a.out_remarks=b.remarks,a.out_money=b.money,a.out_newmoney=b.newmoney,a.out_status=b.status,a.out_superid=b.superid,a.out_businessid=b.businessid,a.out_price=b.price,a.out_lineid=b.lineid,a.out_sbid=b.sbid,a.out_id=b.id where b.id in ($allid_str)");
                $yyid=M('daydata_inandout')->field('out_id')->where("out_id in ($allid_str)")->select();
                $yyid_arr=array();
                foreach ($yyid as $k => $v) {
                    $yyid_arr[]=$v['out_id'];
                }
                $allid_arr=array_diff($allid_arr, $yyid_arr);

                if(count($allid_arr)>0){
                    $allid_str=implode(',', $allid_arr);
                    M()->execute("INSERT into boss_daydata_inandout(jfid,adddate,out_id,out_money,out_newmoney,out_datanum,out_newdata,out_status,out_superid,out_lineid,out_price,out_remarks,out_auditdate,out_businessid,out_sbid) select a.jfid, a.adddate,a.id,a.money,a.newmoney,a.datanum,a.newdata,a.status,a.superid,a.lineid,a.price,a.remarks,a.auditdate,a.businessid,a.sbid from boss_daydata_out a where a.id in ($allid_str)");
                }
            }
            $this->response(array('status'=>$status,'data'=>$return,'errcode'=>$errcode));
    }
    public function makeSettlementOutDoApi(){//生成成本结算单
        //{id:计费标识分配ID字符串（1,2,4,6,23），strtime:开始时间,endtime:结束时间}

        $strtime=I('post.strtime');
        $endtime=I('post.endtime');
        $jfidarr=I('post.cl_id');
        $superid=I('post.superid');
        $ztid=I('post.sb_id');
        if(!empty($superid))$w=" && superid in ($superid)";
        else $w='';
        if(!empty($ztid))$w2=" && sbid in ($ztid)";
        else $w2='';
        $res=M('daydata_out')->where("status!=1 && adddate>='$strtime' && adddate<='$endtime' && jfid in ($jfidarr)".$w.$w2)->find();
        if($res){
            $d=M('daydata_out')->field('jfid')->where("status!=1 && adddate>='$strtime' && adddate<='$endtime' && jfid in ($jfidarr)".$w.$w2)->group('jfid')->select();
            $remark='操作失败，部分数据已生成结算单';
            $jfid_str='';
            foreach ($d as $k => $v) {
                $jfid_str.=' '.$v['jfid'];
            }
            $status=2;
            $this->response(array('data'=>array(),'status'=>$status,'msg'=>$remark,'errcode'=>$errcode,'cliderror'=>$jfid_str,'clid_status'=>1));
            return;
        }
        $data=D('Home/DaydataOut')->getMakeSettlementOutData($strtime,$endtime,$jfidarr,$superid,$ztid);

        $settlementData=array();
        foreach ($data as $key => $value) {
            $userkey=$value['advid'].$value['lineid'];

            if(empty($settlementData[$userkey])){
                $settlementData[$userkey]=array(
                    'jfid'=>$value['jfid'],
                    'allcomname'=>$value['comname'],
                    'qdzt'=>$value['qdzt'],
                    'settlementmoney'=>$value['endmoney'],
                    'strdate'=>$value['strtime'],
                    'enddate'=>$value['endtime'],
                    'addresserid'=>'',
                    'saler'=>$value['username'],
                    'basispath'=>'',
                    'alljfid'=>$value['jfid'],
                    'status'=>0,
                    'lineid'=>$value['lineid'],
                    'superid'=>$value['advid'],
                    'jsztid'=>$value['qdztid'],
                    'sangwuid'=>$value['userid'],
                    'advername'=>$value['advername']
                    );
            }else{
                $settlementData[$userkey]['settlementmoney']=$settlementData[$userkey]['settlementmoney']+$value['endmoney'];
                $settlementData[$userkey]['allcomname']=$settlementData[$userkey]['allcomname'].','.$value['comname'];
                $settlementData[$userkey]['alljfid']=$settlementData[$userkey]['alljfid'].','.$value['jfid'];
                $s_arr=explode(',',$settlementData[$userkey]['sangwuid']);
                if(!in_array($value['userid'],$s_arr))$settlementData[$userkey]['sangwuid']=$settlementData[$userkey]['sangwuid'].','.$value['userid'];
                if($value['strtime']<$settlementData[$userkey]['strdate'])$settlementData[$userkey]['strdate']=$value['strtime'];
                if($value['endtime']>$settlementData[$userkey]['enddate'])$settlementData[$userkey]['enddate']=$value['endtime'];
            }

        }
        $strnum=D('Home/SettlementOut')->getnum();
        $strnum_forxunhuan=0;
        $oldnum=0;
        $myallmoney=0;
        M()->startTrans();
        foreach ($settlementData as $key => $value) {
            $value['settlementmoney']=twonum($value['settlementmoney']);
            $myallmoney+=twonum($value['settlementmoney']);
            if(count($settlementData)==1){
                $value['strdate']=I('post.strtime');
                $value['enddate']=I('post.endtime');
            }
            $res=M('settlement_out')->field('id')->where("strdate='{$value['strdate']}' && enddate='{$value['enddate']}' && alljfid='{$value['alljfid']}' && settlementmoney={$value['settlementmoney']} && status=0 && jsztid={$value['jsztid']} && sangwuid='{$value['sangwuid']}' && superid={$value['superid']}")->find();
            if($res){
                $id=$res['id'];
                $oldnum++;
            }else $id=D('Home/SettlementOut')->adddata($value);
            $newsettlementData[]=array('cl_id'=>$value['alljfid'],'money'=>$value['settlementmoney'],'id'=>$id,'supId'=>$value['superid'],'strdate'=>$value['strdate'],'enddate'=>$value['enddate']);
        }
        $endnum=D('Home/SettlementOut')->getnum();
        if($endnum+$oldnum-$strnum==count($settlementData) && count($newsettlementData)>0){
            if(I('post.allmoney')!==''){
                if($myallmoney!=I('post.allmoney')){
                    M()->rollback();
                    $status=2;
                    $remark='操作失败，金额不对应，BOS系统金额为'.$myallmoney;
                }else{
                    M()->commit();
                    $status=1;
                    $remark='操作成功';
                }
            }else{
                M()->commit();
                $status=1;
                $remark='操作成功';
            }
            
        }elseif(count($newsettlementData)==0){
            M()->rollback();
            $status=2;
            $remark='操作失败，没有找到数据';
        }else{
            M()->rollback(); 
            $status=2;
            $remark='操作失败';
        }
        $errcode=0;

        $this->response(array('data'=>$newsettlementData,'status'=>$status,'msg'=>$remark,'errcode'=>$errcode,'allmoney'=>$myallmoney));
    }
    public function makeSettlementOutDoOkApi(){//确认结算单
        if(empty(I('post.id')) || I('post.id')==0)$remark='缺少id参数';
        if(empty(I('post.advcontactsid')) || I('post.advcontactsid')==0)$remark='缺少advcontactsid参数';
        if(empty(I('post.username')) || I('post.username')==0)$remark='缺少username参数';
        if(!empty($remark)){
            $status=2;
            $this->response(array('status'=>$status,'msg'=>$remark,'errcode'=>$errcode));
            return;
        }   
        $data=D('Home/SettlementOut')->getonedata("id=".I('post.id'));
        
        if(!$data){
             $this->response(array('status'=>2,'msg'=>'结算单不存在','errcode'=>1));
             return;
        }else{
            $r=D('Home/SettlementOut')->getonedata("id!=".I('post.id')." && alljfid='".$data['alljfid']."' && strdate='".$data['strdate']."' && enddate='".$data['enddate']."' && status>0 && status!=6");
            if($r){
                $this->response(array('status'=>2,'msg'=>'此计费标识相同时间段数据已进入结算','errcode'=>1));
                return;
            }
        }
        $addressdata=M('supplier_finance')->where("sp_id=".I('post.advcontactsid')." && bl_id=".$data['lineid'])->find();
        $sup_data=M('supplier')->where("id=".I('post.advcontactsid'))->find();
        if(!$addressdata || $sup_data['type']==1){
            $this->response(array('status'=>2,'msg'=>'此供应商为个人或缺少收款信息','errcode'=>1));
            return;
        }
        $alldataid=D('Home/DaydataOut')->editdataforcom($data['superid'],$data['sangwuid'],$data['lineid'],$data['strdate'],$data['enddate'],$data['alljfid']);

        foreach ($alldataid as $key => $value) {
            $id_arr[]=$value['id'];
        }
        $id_str=implode(',',$id_arr);
        $check_allmoney=M('daydata_out')->field('sum(newmoney) as allmoney')->where("id in ($id_str)")->find();
        if($check_allmoney['allmoney']!=$data['settlementmoney']){
            $this->response(array('status'=>2,'msg'=>'金额已变更','errcode'=>0,'notaxmoney'=>0));
            exit();
        }
        $strnum_forxunhuan=0;
        while (1) {
            M()->startTrans();
            /*
            $r_t=D('Home/DaydataOut')->getonedata("id in ($id_str) && type!=1");
            if($r_t){
                M()->rollback(); 
                $status=2;
                $remark='部分数据不在已发布状态';
                break;
            }*/
            $r2=D('Home/DaydataOut')->getonedata("id in ($id_str) && status!=1");
            if($r2){
                M()->rollback(); 
                $status=2;
                $remark='部分数据状态不匹配';
                break;
            }
            $res=D('Home/SettlementOut')->edit("id=".I('post.id'),array('addresserid'=>I('post.advcontactsid'),'tax'=>$addressdata['financial_tax'],'addtime'=>date('Y-m-d H:i:s'),'notaxmoney'=>twonum($data['settlementmoney']*(1-$addressdata['financial_tax'])),'showkuanname'=>$addressdata['payee_name'],'bankname'=>$addressdata['opening_bank'],'banknum'=>$addressdata['bank_no'],'status'=>1,'struserid'=>I('post.username'),'supername'=>$sup_data['name']));
            
            $res1=D('Home/DaydataOut')->edit("id in ($id_str)",array('status'=>2,'addid'=>I('post.advcontactsid')));
            M('daydata_inandout')->where("out_id in ($id_str)")->save(array('out_status'=>2,'out_addid'=>I('post.advcontactsid')));
            $uinfo=M('user')->field('real_name')->where("id=".I('post.username'))->find();
            foreach ($id_arr as $key => $value) {
                $logid=D('Home/DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'生成结算单','datatype'=>2,'addtime'=>date('Y-m-d H:i:s'),'username'=>$uinfo['real_name']));
            }
            if($res && $res1){
                M()->commit();
                $status=1;
                $remark='操作成功';
                $tongziuserlist=M('auth_group_access')->where("group_id=9")->select();
                foreach ($tongziuserlist as $key => $value) {
                    $tongziuserid[]=$value['uid'];
                }
                $superinfo=M('supplier')->field('name')->where("id=".$data['superid'])->find();
                M('prompt_information')->add(array('send_user'=>implode(',',$tongziuserid),'date_time'=>date('Y-m-d H:i:s'),'content'=>$superinfo['name'].'  '.$data['strdate'].'~'.$data['enddate'].' 成本结算单【已生成】 点击跳转','a_link'=>'/Makesettlement/settlementOutList?id='.I('post.id')));
                if($alldataid[0]['lineid']==1)postDatatoorther($alldataid,2);
                break;
            }else{
                M()->rollback(); 
                $status=2;
                $remark='操作失败';
                break;
            }
        }
        $errcode=0;
        $this->response(array('status'=>$status,'msg'=>$remark,'errcode'=>$errcode,'notaxmoney'=>twonum($data['settlementmoney']*(1-$addressdata['financial_tax']))));
    }
    public function getSuperListApi(){//获取代理商列表
        $data=D('Home/Supplier')->getdata("type=3");
        if($data){
            $status=1;
            $remark='操作成功';
        }else{
            $status=2;
            $remark='操作失败';
        }
        $errcode=0;
        $this->response(array('status'=>$status,'msg'=>$remark,'data'=>$data,'errcode'=>$errcode));
    }
    public function getInDataApi(){//获取收入数据
        $clidarr=explode(',', I('param.cl_id'));
        $dy=array();
        foreach ($clidarr as $key => $value) {
            $cl_res=M('charging_logo_assign')->where("id=".$value)->find();
            if($cl_res && !empty($cl_res['add_time'])){
                $clidarr[$key]=$cl_res['cl_id'];
                $dy[$cl_res['cl_id']]=$value;
            }
        }
    	$data=D('Home/Daydata')->field('newmoney,newdata,money,datanum,comid,jfid as cl_id,adverid,status,price,adddate')->where("adddate>='".I('param.strtime')."' && adddate <='".I('param.endtime')."' && jfid in (".implode(',',$clidarr).")")->select();
        foreach ($data as $key => $value) {
            if(!empty($dy[$value['jfid']]))$data[$key]['jfid']=$dy[$value['jfid']];
        }
        if($data){
            $status=1;
            $remark='操作成功';
            $errcode=0;
        }else{
            $status=2;
            $remark='无数据';
            $errcode=0;
        }
        $this->response(array('status'=>$status,'msg'=>$remark,'data'=>$data,'errcode'=>$errcode));
    }
    public function getInDataApi2(){//获取收入数据
        $clidarr=explode(',', I('param.cl_id'));

        $data=D('Home/Daydata')->field('newmoney,newdata,money,datanum,comid,jfid as cl_id,adverid,status,price,adddate')->where("adddate>='".I('param.strtime')."' && adddate <='".I('param.endtime')."' && jfid in (".implode(',',$clidarr).")")->select();

        if($data){
            $status=1;
            $remark='操作成功';
            $errcode=0;
        }else{
            $status=2;
            $remark='无数据';
            $errcode=0;
        }
        $this->response(array('status'=>$status,'msg'=>$remark,'data'=>$data,'errcode'=>$errcode));
    }
    public function getOutSettlementStatus(){//获取成本结算单状态
        if(empty(I('post.strtime')))$remark='缺少advcontactsid参数';
            $wheres[]="strdate >= '".I('post.strtime')."'";
        if(empty(I('post.endtime')))$remark='缺少advcontactsid参数';
            $wheres[]="enddate <= '".I('post.endtime')."'";
        if(!empty($remark)){
            $status=2;
            $this->response(array('status'=>$status,'msg'=>$remark));
            return;
        } 
        if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where=''; 
        $data=D('Home/SettlementOut')->getOutSettlementStatus($where);
        if($data){
            $status=1;
            $remark='操作成功';
            $errcode=0;
        }else{
            $status=2;
            $remark='操作失败';
            $errcode=0;
        }
        $this->response(array('status'=>$status,'msg'=>$remark,'data'=>$data,'errcode'=>$errcode));
    }
    public function getFenfaOldOutdataApi(){
        $p=I('post.p');
        if($p<1)$p=1;
        $str=($p-1)*100;
        $data=M('daydata_out')->field('a.id,REPLACE(a.adddate,"-","") as date,a.jfid,a.price,a.newdata,a.newmoney,a.datanum,a.money,b.settlement_cycle,a.superid,c.prot_id,b.deduction_ratio,a.status')->join('a left join boss_charging_logo_assign b on a.jfid=b.cl_id && a.adddate>=b.promotion_stime && if(b.promotion_etime is null,1,b.promotion_etime>=a.adddate) left join boss_charging_logo c on a.jfid=c.id')->where("a.adddate>='2016-07-01' && a.adddate<'2016-11-25' && a.lineid=1")->limit($str.',100')->group('a.id')->select();
        $num=M('daydata_out')->where("adddate>='2016-07-01' && adddate<'2016-11-25' && lineid=1")->count();
        $this->response(array('data'=>$data,'num'=>$num));
    }
    public function getFenfaOldIndataApi(){
        $p=I('post.p');
        if($p<1)$p=1;
        $str=($p-1)*100;
        $data=M('daydata')->field('id,datanum,newdata,money,newmoney,status,price,jfid,REPLACE(adddate,"-","") as date')->where("adddate>='2016-07-01' && adddate<'2016-11-25' && lineid=1")->limit($str.',100')->select();
        $num=M('daydata')->where("adddate>='2016-07-01' && adddate<'2016-11-25' && lineid=1")->count();
        $this->response(array('data'=>$data,'num'=>$num));
    }
    public function getSettlementDetailInfo(){
        if(empty(I('post.id')))$this->response(array('data'=>$data,'msg'=>'没有ID'));
        $res=D('Home/SettlementOut')->getonedata("id=".I('post.id'));
        $data=M('daydata_out')->field("sum(newmoney) as money,jfid")->where("jfid in (".$res['alljfid'].") && superid=".$res['superid']." && lineid=".$res['lineid']." && adddate>='".$res['strdate']."' && adddate<='".$res['enddate']."'")->group("jfid")->select();
        foreach ($data as $k => $v) {
            $data[$k]['money']=twonum($v['money']);
        }
        $this->response(array('data'=>$data));
    }
    /*ouwenqiang
    *2016.12.23
    */

    public function getoutsetApi(){
        //获取成本结算单状态
        $superid = I('post.id');
        $data=M('settlement_out')->field('id,status')->where("id=".$superid)->find();
        if($data){
            $status=1;
            $remark='操作成功';
            $errcode=0;
        }else{
            $status=2;
            $remark='操作失败';
            $errcode=0;
        }
        $this->response(array('status'=>$status,'msg'=>$remark,'data'=>$data,'errcode'=>$errcode,'sql'=>M('settlement_out')->getLastSql()));
    }
}