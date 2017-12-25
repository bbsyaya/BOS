<?php
namespace Api\Controller;
//供应商
class DspDataController extends ApiController {
    public function addOneDayInDataApi(){//同步收入数据接口
        //{datanum:数量，remarks：备注，price：单价，date：时间，jfid：计费标识ID，type:操作类型(1:添加，2：删除)，username:操作人}
            $data=json_decode(htmlspecialchars_decode(I('post.datajson')),true);
            $allid=array();
            foreach ($data as $key => $v) {
                if((I('post.appid')=='101' && $v['lineid']!=2 && $v['lineid']!=44) || (I('post.appid')=='104' && $v['lineid']!=1 && $v['lineid']!=42 && $v['lineid']!=34) || (I('post.appid')=='103' && $v['lineid']!=31) || (I('post.appid')=='107' && $v['lineid']!=46)){
                    $status=2;
                    $remark='业务线不对应';
                }
                    $remark='';
                    if(empty($v['adddate']))$remark='缺少adddate参数';
                    if(empty($v['jfid']))$remark='缺少jfid参数';
                    if($v['newmoney']==='')$remark='缺少newmoney参数';
                    if(empty($v['price']))$remark='缺少price参数'; 
                    $issetok=1;
                    if(!empty($set_data)){
                        $jfidarr=explode(',', $set_data['alljfid']);
                        if(strtotime($set_data['strdate'])<=strtotime($v['adddate']) && strtotime($set_data['enddate'])>=strtotime($v['adddate']) && in_array($v['jfid'],$jfidarr)) $issetok=0;
                    }
                    if($issetok==1)$set_data=M('settlement_in')->where("find_in_set(".$v['jfid'].",alljfid) && strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && status!=6 && status!=0")->find();
                    if($set_data || $issetok==0){
                        $remark='对应计费标识当前时间已生成结算单，不可更改';
                    }
                    if(!empty($remark)){
                        $status=2;
                        $return[]=array('jfid'=>$v['jfid'],'msg'=>$remark,'status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                        continue;
                    }
                    $config_logores=D('Home/ChargingLogo')->getdatainfoforjfid($v['jfid']);
                    if($config_logores['charging_mode']=='3'){
                        $price=$price/1000;
                    }
                    $datanum=$v['newmoney']/$v['price'];
                    
                    $newdata=array('adddate'=>$v['adddate'],'jfid'=>$v['jfid'],'datanum'=>$datanum,'newdata'=>$datanum,'remarks'=>$v['remarks'],'money'=>$v['newmoney'],'newmoney'=>$v['newmoney'],'status'=>1,'comid'=>$v['comid'],'adverid'=>$v['adverid'],'salerid'=>$v['salerid'],'price'=>$v['price'],'lineid'=>$v['lineid'],'ztid'=>$config_logores['sb_id'],'superid'=>$v['superid'],'type'=>1,'reason'=>$v['reason']);
                    $res=D('Home/Daydata')->field('id,status,newdata,newmoney,type')->getonedata('jfid='.$v['jfid'].' && adddate="'.$v['adddate'].'"');
                    
                    if($res){
                        
                        if($res['status']>1 && $res['status']!=9){
                            $status=2;
                            $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>'此数据已不可修改','status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                            continue;
                        }
                        if($res['newmoney']==$v['newmoney'] && $res['newdata']==$v['datanum'] && $res['type']==1 && $res['status']==$d_status){
                            $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>'与原数据无差异','status'=>1,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                                continue;
                        }
                        unset($newdata['datanum']);
                        unset($newdata['money']);
                        $bool_n=D("Home/Daydata")->edit("id=".$res['id'],$newdata);
                        if(!$bool_n){
                            $status=2;
                            $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>'数据库修改失败','status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
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
                    $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>$remark,'status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                
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

                    $remark='';
                    if(empty($v['adddate']))$remark='缺少adddate参数';
                    if(empty($v['jfid']))$remark='缺少jfid参数';
                    if($v['newmoney']==='')$remark='缺少newmoney参数';
                    if(empty($v['superid']) || $v['superid']==0)$remark='缺少superid参数';
                    if(empty($v['businessid']) || $v['businessid']==0)$remark='缺少businessid参数';
                    if(empty($v['price']))$remark='缺少price参数';
                    if(empty($v['lineid']) || $v['lineid']==0)$remark='缺少lineid参数';
                    if(empty($v['sbid']) || $v['sbid']==0)$remark='缺少sbid参数';   
                    $issetok=1;
                    if(!empty($set_data)){
                        $jfidarr=explode(',', $set_data['alljfid']);
                        if(strtotime($set_data['strdate'])<=strtotime($v['adddate']) && strtotime($set_data['enddate'])>=strtotime($v['adddate']) && in_array($v['jfid'],$jfidarr)) $issetok=0;
                    }
                    if($issetok==1)$set_data=M('settlement_out')->where("find_in_set(".$v['jfid'].",alljfid) && strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && status!=6 && status!=0")->find();
                    if($set_data || $issetok==0){
                        $remark='对应计费标识当前时间已生成结算单，不可更改';
                    }
                    if(!empty($remark)){
                        $status=2;
                        $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>$remark,'status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                        continue;
                    }
                    $config_logores=D('Home/ChargingLogo')->getonedata("id=".$v['jfid']);
                    if($config_logores['charging_mode']=='3'){
                        $price=$price/1000;
                    }
                    if($v['is_pass']==1)$d_status=1;
                    else $d_status=9;
                    $datanum=$v['newmoney']/$v['price'];
                    $newdata=array('adddate'=>$v['adddate'],'jfid'=>$v['jfid'],'datanum'=>$datanum,'newdata'=>$datanum,'remarks'=>$v['remarks'],'money'=>$v['newmoney'],'newmoney'=>$v['newmoney'],'status'=>1,'superid'=>$v['superid'],'businessid'=>$v['businessid'],'price'=>$v['price'],'lineid'=>$v['lineid'],'sbid'=>$v['sbid'],'type'=>1,'adduid'=>$v['username'],'reason'=>$v['reason']);
                    $res=D('Home/DaydataOut')->field('id,status,newmoney,newdata,businessid,sbid,superid,type')->getonedata('jfid='.$v['jfid'].' && adddate="'.$v['adddate'].'"');
                    
                    if($res){

                        if($res['status']>1 && $res['status']!=9){
                            $status=2;
                            $return[]=array('jfid'=>$v['jfid'],'msg'=>'此数据已不可修改','status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                            continue;
                        }
                        if($res['newmoney']==$v['newmoney'] && $res['newdata']==$v['datanum'] && $res['businessid']=$v['businessid'] && $res['sbid']==$v['sbid'] && $res['superid']==$v['superid'] && $res['type']==1 && $res['status']==$d_status){
                            $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>'与原数据无差异','status'=>1,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                                continue;
                        }
                        unset($newdata['datanum']);
                        unset($newdata['money']);
                        $bool_n=D("Home/DaydataOut")->edit("id=".$res['id'],$newdata);
                        if(!$bool_n){
                            $status=2;
                            $return[]=array('jfid'=>$v['jfid'],'msg'=>'数据库修改失败','status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
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
                    $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>$remark,'status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                
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
    public function makeSettlementInDoApi(){//生成收入结算单
        //{id:计费标识分配ID字符串（1,2,4,6,23），strtime:开始时间,endtime:结束时间}

        $strtime=I('post.strtime');
        $endtime=I('post.endtime');
        $jfid=I('post.jfid');
        $adverid=I('post.adverid');
        $ztid=I('post.sbid');
        if(!empty($adverid))$w=" && adverid in ($adverid)";
        else $w='';
        if(!empty($ztid))$w2=" && ztid in ($ztid)";
        else $w2='';
        $res=M('daydata')->where("status!=1 && adddate>='$strtime' && adddate<='$endtime' && jfid in ($jfid)".$w.$w2)->find();
        if($res){
            $d=M('daydata')->field('jfid')->where("status!=1 && adddate>='$strtime' && adddate<='$endtime' && jfid in ($jfid)".$w.$w2)->group('jfid')->select();
            $remark='操作失败，部分数据已生成结算单';
            $jfid_str='';
            foreach ($d as $k => $v) {
                $jfid_str.=' '.$v['jfid'];
            }
            $status=2;
            $this->response(array('data'=>array(),'status'=>$status,'msg'=>$remark,'errcode'=>$errcode,'cliderror'=>$jfid_str,'clid_status'=>1));
            return;
        }
        $data_jf=M('charging_logo')->where("id in ($jfid)")->group('prot_id')->select();
        foreach ($data_jf as $key => $value) {
            $comid[]=$value['prot_id'];
        }
        $data=D('Home/Daydata')->getMakeSettlementInData($strtime,$endtime,implode(',', $comid),$jfid);
        
        $settlementData=array();
        foreach ($data as $key => $value) {
            
            $userkey=$value['advid'].$value['lineid'].$value['userid'];
            if(empty($settlementData[$userkey])){
                $settlementData[$userkey]=array(
                    'comid'=>$value['comid'],
                    'allcomid'=>$value['comid'],
                    'alljfid'=>$value['jfid'],
                    'advername'=>$value['advername'],
                    'advid'=>$value['advid'],
                    'invoicetype'=>'',
                    'invoicecontent'=>'',
                    'taxpayer'=>'',
                    'bankname'=>'',
                    'bankcode'=>'',//开户行账号
                    'qdzt'=>$value['qdzt'],
                    'settlementmoney'=>$value['endmoney'],
                    'strdate'=>$value['strtime'],
                    'enddate'=>$value['endtime'],
                    'addresseename'=>'',
                    'addresseetel'=>'',
                    'address'=>'',
                    'saler'=>$value['username'],
                    'basispath'=>'',
                    'status'=>0,
                    'jsztid'=>$value['qdztid'],
                    'salerid'=>$value['userid'],
                    'lineid'=>$value['lineid']
                    );
            }else{
                $settlementData[$userkey]['settlementmoney']=$settlementData[$userkey]['settlementmoney']+$value['endmoney'];
                $settlementData[$userkey]['allcomid']=$settlementData[$userkey]['allcomid'].','.$value['comid'];
                $settlementData[$userkey]['alljfid']=$settlementData[$userkey]['alljfid'].','.$value['jfid'];
                if($value['strtime']<$settlementData[$userkey]['strdate'])$settlementData[$userkey]['strdate']=$value['strtime'];
                if($value['endtime']>$settlementData[$userkey]['enddate'])$settlementData[$userkey]['enddate']=$value['endtime'];
            }
        }
        $strnum=M('settlement_in')->where("status=0")->count();
        $strnum_forxunhuan=0;
        
        $oldnum=0;
        M()->startTrans();
        $myallmoney=0;
        foreach ($settlementData as $key => $value) {
            $myallmoney+=twonum($value['settlementmoney']);
            $res=M('settlement_in')->field('id')->where("strdate='{$value['strdate']}' && enddate='{$value['enddate']}' && allcomid='{$value['allcomid']}' && settlementmoney={$value['settlementmoney']} && status=0 && jsztid={$value['jsztid']} && advid={$value['advid']} && salerid={$value['salerid']}")->find();
            $sql=M()->getLastSql();
            if($res){
                $id=$res['id'];
                $oldnum++;
            }else $id=D('Home/SettlementIn')->adddata($value);
            $newsettlementData[]=array_merge(array('id'=>$id),$value);
        }

        $endnum=M('settlement_in')->where("status=0")->count();
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

        $this->response(array('data'=>$newsettlementData,'status'=>$status,'msg'=>$remark,'errcode'=>$errcode,'allmoney'=>$myallmoney,'sql'=>$sql));
    }
    public function makeSettlementOutDoOkApi(){//确认结算单
    	if(empty(I('post.advcontactsid'))){
    		$this->response(array('status'=>2,'msg'=>'没有选择收件人','errcode'=>1));
             return;
        }
        $addressdata=M('advertiser_fireceiver')->where("id=".I('post.advcontactsid'))->find();
        $data=D('Home/SettlementIn')->getonedata("id=".I('post.id'));
        $alldataid=D('Home/Daydata')->editdataforcom($data['advid'],$data['salerid'],$data['lineid'],$data['strdate'],$data['enddate'],$data['allcomid'],$data['alljfid']);
        foreach ($alldataid as $key => $value) {
            $id_arr[]=$value['id'];
        }
        $id_str=implode(',',$id_arr);
        if(M('Daydata')->where("id in ($id_str) && status!=1 && status!=9")->find()){
            $this->response(array('status'=>2,'msg'=>'已有部分数据进入结算状态','errcode'=>1));
            return;
        }
        M()->startTrans();
        $res=D('Home/SettlementIn')->edit("id=".I('post.id'),array('basispath'=>implode(',',$file_name_arr),'addresseeid'=>I('post.advcontactsid'),'addresseename'=>$addressdata['name'],'addtime'=>date('Y-m-d H:i:s'),'addresseetel'=>$addressdata['mobile'],'address'=>$addressdata['address'],'status'=>1,'iskaipiao'=>I('post.iskaipiao'),'struserid'=>$_SESSION['userinfo']['uid']));

        $res1=D('Home/Daydata')->edit("id in ($id_str)",array('status'=>2));//数据状态已确认
        M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>2));
        
        foreach ($id_arr as $key => $value) {
            $logid=D('Home/DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'生成结算单 '.I('post.id'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
        }
        if($res && $res1){

            M()->commit();
            $result=D('Home/Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
        }else{
            M()->rollback(); 
            $this->response(array('status'=>2,'msg'=>'操作失败，请联系程序员','errcode'=>1));
            return;
        }
        
        $tongziuserlist=M('auth_group_access')->where("group_id=9")->select();
        foreach ($tongziuserlist as $key => $value) {
            $tongziuserid[]=$value['uid'];
        }
        $superinfo=M('Advertiser')->field('name')->where("id=".$data['advid'])->find();
        M('prompt_information')->add(array('send_user'=>implode(',',$tongziuserid),'date_time'=>date('Y-m-d H:i:s'),'content'=>$superinfo['name'].'  '.$data['strdate'].'~'.$data['enddate'].' 收入结算单【已生成】 点击跳转','a_link'=>'/Makesettlement/settlementInList?id='.I('post.id')));
        $status=1;
        $errcode=0;
        $remark='操作成功';
        $this->response(array('status'=>$status,'msg'=>$remark,'errcode'=>$errcode));
    }
    public function DelDspData(){
        $data=json_decode(htmlspecialchars_decode(I('post.datajson')),true);
        $allid=array();
        foreach ($data as $key => $v) {
            $remark='';
            if(empty($v['adddate']))$remark='缺少adddate参数';
            if(empty($v['jfid']))$remark='缺少jfid参数';
            if(empty($v['type']))$remark='缺少type参数'; 
            $issetok=1;
            if(!empty($set_data)){
                $jfidarr=explode(',', $set_data['alljfid']);
                if(strtotime($set_data['strdate'])<=strtotime($v['adddate']) && strtotime($set_data['enddate'])>=strtotime($v['adddate']) && in_array($v['jfid'],$jfidarr)) $issetok=0;
            }
            if($issetok==1)$set_data=M('settlement_in')->where("find_in_set(".$v['jfid'].",alljfid) && strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && status!=6 && status!=0")->find();
            if($set_data || $issetok==0){
                $remark='对应计费标识当前时间已生成结算单，不可更改';
            }
            if(!empty($remark)){
                $status=2;
                $return[]=array('jfid'=>$v['jfid'],'msg'=>$remark,'status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                continue;
            }
            if($v['type']==1){
                $table='daydata';
                $res=M($table)->where("jfid=".$v['jfid']." && adddate='".$v['adddate']."'")->find();
                if($res['status']>1 && $res['status']!=9){
                    $status=2;
                    $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>'此数据已不可修改','status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                    continue;
                }
                M('daydata_inandout')->where("jfid=".$v['jfid']." && adddate='".$v['adddate']."'")->save(array('in_id'=>'','in_money'=>'','in_newmoney'=>'','in_datanum'=>'','in_newdata'=>'','in_comid'=>'','in_status'=>'','in_adverid'=>'','in_lineid'=>'','in_price'=>'','in_remarks'=>'','in_auditdate'=>'','in_salerid'=>'','in_banimgpath'=>'','in_ztid'=>'','in_ischeck'=>''));
            }else{
                $table='daydata_out';
                $res=M($table)->where("jfid=".$v['jfid']." && adddate='".$v['adddate']."'")->find();
                if($res['status']>1 && $res['status']!=9){
                    $status=2;
                    $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>'此数据已不可修改','status'=>$status,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
                    continue;
                }
                M('daydata_inandout')->where("jfid=".$v['jfid']." && adddate='".$v['adddate']."'")->save(array('out_id'=>'','out_money'=>'','out_newmoney'=>'','out_datanum'=>'','out_newdata'=>'','out_status'=>'','out_superid'=>'','out_businessid'=>'','out_auditdate'=>'','out_price'=>'','out_lineid'=>'','out_sbid'=>'','out_remarks'=>'','out_addid'=>''));
            }
            M($table)->where("jfid=".$v['jfid']." && adddate='".$v['adddate']."'")->delete();
            $res2=M('daydata_inandout')->where("jfid=".$v['jfid']." && adddate='".$v['adddate']."'")->find();
            if($res2['in_id']=='0' && $res2['out_id']=='0'){
                M('daydata_inandout')->where("jfid=".$v['jfid']." && adddate='".$v['adddate']."'")->delete();
            }
            $return[]=array('jfid'=>$v['jfid'],'id'=>$id,'msg'=>'删除成功','status'=>1,'adddate'=>$v['adddate'],'senddata_id'=>$v['senddata_id']);
        }
        $this->response(array('status'=>$status,'data'=>$return,'errcode'=>$errcode));
    }
    public function getAdvFire(){
        $advid=I('param.id');
        $data=M('advertiser_fireceiver')->where("ad_id=$advid")->select();
        $this->response(array('status'=>1,'data'=>$data,'errcode'=>0));
    }
    public function getSuperListApi(){//获取供应商列表
        $data=D('Home/Supplier')->getdata("type=2");
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
        //添加修改供应商财务信息
    public function editSuperFinance() {

        /*$_GET = array(
            'id'=>6,
            'sp_id' => 1,
            'bl_id' => '1',
            'invoice_type' => 1,
            'object_type' => 1,
            'payee_name' => '重庆市华宇科技11',
            'opening_bank' => '重庆市渝中区',
            'bank_no' => '234234234234',
        );*/

        $id = I('param.id');
        $spId = I('param.sp_id','');
        $_meta = '';
        $addData = I('param.');

        if (empty($spId)) {
            $this->responseExit(array('errcode'=>'4004','msg'=>'供应商id不能为空'));
        }

        $supplierId = D('Home/Supplier')->where("id='{$spId}'")->getField('id');
        if ((int)$supplierId<=0) {
            $this->responseExit(array('errcode'=>'5000','msg'=>'无对应供应商信息'));
        }

        if ($id > 0) {
            if (D('Home/SupplierFinance')->where('id='.$id)->count()<=0) {
                $this->responseExit(array('errcode'=>'5000','msg'=>'无对应供应商财务信息'));
            }
        }

        //如果有重复数据，覆盖
        //$repMap['payee_name'] = I('param.payee_name');
        $repMap['bl_id'] = I('param.bl_id');
        $repMap['sp_id'] = I('param.sp_id');
        $exist = D('Home/SupplierFinance')->where($repMap)->find();
        if (!empty($exist)) {
            $id=$exist['id'];
            $addData['id']=$exist['id'];
        }

        $addData['sp_id'] = $supplierId;
        $supFinModel = D('Home/SupplierFinance');
        if($supFinModel->create($addData) === false ) {
            $this->actionLog(SEASLOG_ERROR, '供应商财务数据错误:'.$supFinModel->getError());//日志
            $this->responseExit(array('errcode'=>'5000','msg'=>$supFinModel->getError()));
        } else {
            if ($id > 0) {
                $r = $supFinModel->save($addData);
                $_meta = '修改';
            } else {
                unset($addData['id']);
                $id = $r = $supFinModel->add($addData);
                $_meta = '添加';
            }
            if ($r === false) {
                $this->actionLog(SEASLOG_ERROR, '供应商财务'.$_meta.'失败:'.$supFinModel->getError());//日志
                $this->responseExit(array('errcode'=>'5000','msg'=>$supFinModel->getError()));
            }
        }
        $supinfo=M('supplier')->where("id='{$spId}'")->find();
        if($supinfo['type']!=1){
            M('supplier')->where("id='{$spId}'")->save(array('fukuanname'=>I('post.payee_name')));
        }
        $retData = array(
            'errcode'=>'0',
            'data'=>array('spfid'=>$id),
            'msg'=>'供应商财务信息'.$_meta.'成功',
        );
        $this->actionLog('info', '供应商财务信息'.$_meta.'成功'.'spfid>'.$id);//日志
        $this->responseExit($retData);
    }
    public function editAdverData(){
        $isRedirect = false;
        $editId = $adid = I('post.id', 0, 'intval');

        //基本信息
        $advModel = D('Advertiser');
        /*if(!empty($_FILES['qualif_img']['tmp_name'])){ //是否上传操作
            $qualiInfo = $this->uplaodfile('qualif_img', UPLOAD_INMONEY_ADVERTISER_PATH);
            if (!is_array($qualiInfo)) {
                $this->ajaxReturn(array('msg'=>$qualiInfo));
            }
            $filepath = UPLOAD_INMONEY_ADVERTISER_PATH .$qualiInfo['qualif_img']['savepath'].$qualiInfo['qualif_img']['savename'];
            $_POST['qualif_img'] = $filepath;
        }*/

        if ($advModel->create() === false) {
            $this->ajaxReturn(array('msg'=>$advModel->getError(),'status'=>0));
        }

        if(empty($_POST['contacts'])) {
            $this->ajaxReturn(array('msg'=>'广告主对接人信息不能为空','status'=>0));
        }

        //修改
        if ($adid > 0) {
            if ($advModel->save() === false) {
                $this->ajaxReturn(array('msg'=>$advModel->getError(),'status'=>0));
            }
        } else { //新增
            $editId = $insertId = $advModel->add();
            if ($insertId === false) {
                $this->ajaxReturn(array('msg'=>$advModel->getError(),'status'=>0));
            } else {
                //更新广告主编码
                $_map['id'] = $insertId;
                $_map['ad_code'] = $advModel->generalCode($insertId);
                if ($advModel->save($_map) === false) { //更新失败删除刚添加的广告主
                    $advModel->delete($insertId);
                    $this->ajaxReturn(array('msg'=>$advModel->getError(),'status'=>0));
                }

            }
            $isRedirect = true;
        }
        //判断财务信息是否在白名单中 start 2017.06.02
        /*if($_POST['name'] && $_POST['opening_bank'] && $_POST['bank_no']) {
            $white_list = M('white_list')->field('opening_bank')->where("name='" . $_POST['name'] . "' && opening_bank='" . $_POST['opening_bank'] . "' && bank_no='" . $_POST['bank_no'] . "'")->find();
            if (empty($white_list)) {//为空则添加
                $add = array();
                $add['name'] = $_POST['name'];
                $add['opening_bank'] = $_POST['opening_bank'];
                $add['bank_no'] = $_POST['bank_no'];
                $add['type'] = 1;
                M('white_list')->add($add);
            }
        }*/
        //end

        $redirectUrl = $isRedirect ? U('edit?id='.$editId) : ''; //如果基本信息添加成功，后面的错误信息后跳转到已添加的编辑页

        //财务接受人信息
        $financeReceiver = (array)$_POST['finance_receiver'];
        if (!empty($financeReceiver)) {
            $frModel = M('advertiser_fireceiver');
            foreach ($financeReceiver as $item) {
                $item['ad_id'] = $editId;

                if ($frModel->validate($advModel->receiverRule)->create($item) === false) {
                    $this->ajaxReturn(array('msg'=>$frModel->getError(),'status'=>0));
                }
                if ($item['id'] > 0) {
                    $r = $frModel->save();
                } else {
                    $r = $frModel->add();
                }
                if ($r === false) {
                    $this->ajaxReturn(array('msg'=>$frModel->getError(),'status'=>0));
                }
            }
        }

        //联系人数据
        $contactsModel = D('AdvertiserContacts');
        foreach ($_POST['contacts'] as $val) {
            $val['ad_id'] = $editId;
            if ($contactsModel->create($val) === false) {
                $this->ajaxReturn(array('msg'=>$contactsModel->getError(),'status'=>0));
            }
            $isExist = $contactsModel->where('id='.intval($val['id']))->count();
            if ($isExist > 0) {
                $r = $contactsModel->save();
            } else {
                $r = $contactsModel->add();
            }
            if ($r === false) {
                $this->ajaxReturn(array('msg'=>$contactsModel->getError(),'status'=>0));
            }

        }

        $retMsg = $adid > 0 ? '广告主修改成功' : '广告主添加成功';
        $goUrl = $adid > 0 ? Cookie('__forward__') : U('index');
        action_log('partner', 'info', $_SESSION['userinfo']['realname'], $retMsg.'adid='.$editId, CONTROLLER_NAME.'/'.ACTION_NAME);//日志
        $this->ajaxReturn(array('msg'=>$retMsg,'status'=>1));
    }
}
