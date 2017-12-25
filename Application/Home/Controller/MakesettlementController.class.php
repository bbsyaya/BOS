<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class MakesettlementController extends BaseController {
    /*
    *收入部分
    */
    public function makeSettlementIn(){//生成收入结算单
        $wheres[]= 'a.status=1';
        if(!empty(I('get.strtime'))){
            $strtime=I('get.strtime');
            $wheres[]="a.adddate >= '$strtime'";
        }else $strtime=date("Y-m-d");
        if(!empty(I('get.endtime'))){
            $endtime=I('get.endtime');
            $wheres[]="a.adddate <= '$endtime'";
        }else $endtime=date("Y-m-d");
        if(!empty(I('get.saler_id')))$wheres[]='g.real_name like "%'.I('get.saler_id').'%"';
        if(!empty(I('get.comname')))$wheres[]='c.name like "%'.I('get.comname').'%"';
        if(!empty(I('get.advername')))$wheres[]='d.name like "%'.I('get.advername').'%"';
        $wheres[]='a.status != 9';


        //判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
        $isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = 'g.real_name like "%{$spidStr}%"';
        }


        if(count($wheres)>0){
            //数据权限
            $arr_name=array();
            $arr_name['line']=array('a.lineid');
            $arr_name['user']=array('a.salerid');
            $ruleser=new Service\RuleService();
            $myrule_data=$ruleser->getmyrule_data($arr_name);
            $wheres[]= $myrule_data;


            $where=implode(' && ', $wheres);

            $allcount=D('Daydata')->getSetDataListcount($where);
            $this->getpagelist($allcount['num']);
            $data=D('Daydata')->getSetDataList($where,$p);
            foreach($data as $key=>$value){//2017.01.10 产品id和日期期间查询计费标识id字符串
                $jfData = M('Daydata')->field('jfid')->where("adddate>= '".$value['start_date']."' and adddate<='".$value['end_date']."' and comid=".$value['comid']."")->group('jfid')->select();
                $jf_id = "";
                foreach($jfData as $val){
                    $jf_id .=$val['jfid'].",";
                }
                $jfid = rtrim($jf_id,",");
                $data[$key]['jfid'] = $jfid;
            }
            $this->getpagelist($allcount['num']);
            $this->assign('data',$data);
        }
        $this->assign('strtime',$strtime);
        $this->assign('endtime',$endtime);
    	$this->display();
    }
    public function makejfApi(){//根据产品id和日期期间查询计费标识明细

        $ad_id = $_POST['ad_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $where = "a.adddate>= '".$start_date."' and a.adddate<='".$end_date."' and a.comid=".$ad_id."";
        $jfData = M('Daydata')->field('concat(min(a.adddate),"-",max(a.adddate)) as date,ROUND(sum(ifnull(a.newmoney,a.money)),2) as endmoney,b.id,b.code,b.name,ROUND(sum(a.datanum),2) as ysdata,ROUND(sum(ifnull(a.newdata,a.datanum)),2) as enddata,ROUND(sum(a.money),2) as ysmoney')->join('a join boss_charging_logo b on a.jfid=b.id')->where($where)->group('a.jfid')->select();
        echo json_encode(array('data'=>$jfData));
    }
    public function makeSettlementInDo(){//提交生成收入结算单
        $jfidarr=I('post.id');
        $strtime=I('post.strtime');
        $endtime=I('post.endtime');
        $jfid=I('post.jfid');
        if(empty($strtime))$strtime=date('Y-m-d',time()-90*3600*24);
        if(empty($endtime))$endtime=date('Y-m-d');
        $data=D('Daydata')->getMakeSettlementInData($strtime,$endtime,$jfidarr,$jfid);

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
        $strnum=D('SettlementIn')->getnum("status=0");
        $strnum_forxunhuan=0;
        while (1) {
            $oldnum=0;
            M()->startTrans();
            foreach ($settlementData as $key => $value) {
                $res=M('settlement_in')->field('id')->where("strdate='{$value['strdate']}' && enddate='{$value['enddate']}' && allcomid='{$value['allcomid']}' && settlementmoney={$value['settlementmoney']} && status=0 && jsztid={$value['jsztid']} && advid={$value['advid']} && salerid={$value['salerid']}")->find();
                if($res){
                    $id=$res['id'];
                    $oldnum++;
                }else $id=D('SettlementIn')->adddata($value);
                $newsettlementData[]=array_merge(array('id'=>$id),$value);
            }
            $endnum=D('SettlementIn')->getnum("status=0");
            if($endnum+$oldnum-$strnum==count($settlementData)){
                M()->commit();
                break;
            }else{
                M()->rollback(); 
                $strnum_forxunhuan++;
                if($strnum_forxunhuan>=5)$this->error('操作失败，请联系程序员');
            }
        }
        
        if(count($settlementData)>1){
            $list=array('type'=>'1','data'=>$newsettlementData);
            echo json_encode($list);
        }else{
            $list=array('type'=>'0','data'=>$newsettlementData);
            echo json_encode($list);
        }
    }

    public function detail(){
        $data=D('SettlementIn')->getonedata("id=".I('post.id'));
        $advcontacts=M('advertiser_fireceiver')->where("ad_id=".$data['advid'])->select();
        $advdata=D('Advertiser')->getonedata("id=".$data['advid']);
        $j=M('data_dic')->where("id=".$data['jsztid'])->find();
        $s=M('user')->where("id=".$data['salerid'])->find();
        $jszt=array('jszt'=>$j['name'],'sealname'=>$s['real_name']);
        echo json_encode(array('settlementdata'=>$data,'advcontaces'=>$advcontacts,'advdata'=>$advdata,'jszt'=>$jszt));
    }
    public function makeSettlementInDetail(){//明细
        $data=D('Daydata')->getSetDataList();
        $this->assign('data',$data);
        $this->display();
    }
    public function delSettlement(){//取消收入结算单//
        $res=D('SettlementIn')->deldata("id=".I('get.id'));
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    public function confirmSettlement(){//确认生成收入结算单   此处逻辑可优化
        if(empty(I('post.advcontactsid'))){
            $this->assign('data','没有选择收件人');
            $this->display('Public/alertpage');
            return;
        }

        for($filenum=0;$filenum<25;$filenum++){
            if(!empty($_FILES['file'.$filenum])){
                $info=$this->uplaodfile('file'.$filenum,UPLOAD_BASIS_IMG_PATH); 
                if(!is_array($info)){
                    $this->assign('data','上传依据失败');
                    $this->display('Public/alertpage');
                    return;
                }
                $file_name_arr[]=UPLOAD_BASIS_IMG_PATH.$info['file'.$filenum]['savepath'].$info['file'.$filenum]['savename'];
            }
        }
        
        $addressdata=M('advertiser_fireceiver')->where("id=".I('post.advcontactsid'))->find();
        $data=D('SettlementIn')->getonedata("id=".I('post.id'));
        $alldataid=D('Daydata')->editdataforcom($data['advid'],$data['salerid'],$data['lineid'],$data['strdate'],$data['enddate'],$data['allcomid'],$data['alljfid']);
        foreach ($alldataid as $key => $value) {
            $id_arr[]=$value['id'];
        }
        $id_str=implode(',',$id_arr);
        if(M('Daydata')->where("id in ($id_str) && status!=1 && status!=9")->find()){
            $this->assign('data','已有部分数据进入结算状态');
            $this->display('Public/alertpage');
            return;
        }
        M()->startTrans();
        $res=D('SettlementIn')->edit("id=".I('post.id'),array('basispath'=>implode(',',$file_name_arr),'addresseeid'=>I('post.advcontactsid'),'addresseename'=>$addressdata['name'],'addtime'=>date('Y-m-d H:i:s'),'addresseetel'=>$addressdata['mobile'],'address'=>$addressdata['address'],'status'=>1,'iskaipiao'=>I('post.iskaipiao'),'struserid'=>$_SESSION['userinfo']['uid']));

        $res1=D('Daydata')->edit("id in ($id_str)",array('status'=>2));//数据状态已确认
        M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>2));
        

        foreach ($id_arr as $key => $value) {
            $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'生成结算单 '.I('post.id'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
        }
        if($res && $res1){

            M()->commit();
            $result=D('Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
        }else{
            M()->rollback(); 
                $this->assign('data','操作失败，请联系程序员');
                $this->display('Public/alertpage');
                return;
        }
        
        $tongziuserlist=M('auth_group_access')->where("group_id=9")->select();
        foreach ($tongziuserlist as $key => $value) {
            $tongziuserid[]=$value['uid'];
        }
        $superinfo=M('Advertiser')->field('name')->where("id=".$data['advid'])->find();
        M('prompt_information')->add(array('send_user'=>implode(',',$tongziuserid),'date_time'=>date('Y-m-d H:i:s'),'content'=>$superinfo['name'].'  '.$data['strdate'].'~'.$data['enddate'].' 收入结算单【已生成】 点击跳转','a_link'=>'/Makesettlement/settlementInList?id='.I('post.id')));
        $this->assign('data','提交成功');
        $this->display('Public/alertpage2');
        return;
    }
    public function settlementInList(){//收入结算单列表
        $count=D('SettlementIn')->getlistcount();
        $this->getpagelist($count);
        $data=D('SettlementIn')->getlist();
        $getSumIn=D('SettlementIn')->getSumIn();
        foreach ($data as $key => $v) {
            $str='';
            $a=explode(',', $v['allcomid']);
            foreach ($a as $k => $val) {
                $str.="&comid[]=".$val;
            }
            $data[$key]['comstr']=$str;
            if($v['alljfid']!=''){
                $str='';
                $a=explode(',', $v['alljfid']);
                foreach ($a as $k => $val) {
                    $str.="&jfid[]=".$val;
                }
                $data[$key]['jfstr']=$str;
            }
            
            if($v['iskaipiao'] == 1){
                $data[$key]['iskaipiao']= '要';
            }else{
                $data[$key]['iskaipiao']= '不要';
            }
        }
        $this->assign('data',$data);
        $this->assign('getSumIn',$getSumIn);
        $dic=M('data_dic')->where("dic_type=12")->select();
        $this->assign('dic',$dic);
        $this->display();
    }
    public function downloadsetinlist(){
        $data=D('SettlementIn')->getlist(1);
        $arr=array(
            '0'=>'未审核',
            '1'=>'待审核',
            '2'=>'待开票',
            '3'=>'待认款/已开票',
            '4'=>'已认款',
            '5'=>'已回款未开票',
            '6'=>'已作废'
        );
        foreach ($data as $key => $value) {
            $data[$key]['date']=$value['strdate'].'至'.$value['enddate'];
            $data[$key]['status']=$arr[$value['status']];
        }
        $list=array(array('id','结算单编号'),array('advname','广告主名称'),array('comname','产品名称'),array('date','账单期间'),array('settlementmoney','结算金额（含税）'),array('jszt','结算主体'),array('status','状态'),array('bankname','收款账户'),array('real_name','所属销售'));
        $this->downloadlist($data,$list,'收入结算单列表');
    }
    public function settlementInOk(){//通过审核    此处逻辑可优化
        if (!$this->checkRule('/Home/Makesettlement/settlementInOk')) {
            $this->error('您没有访问权限,请联系管理员');
        }
        $data=D('SettlementIn')->getonedata("id=".I('get.id'));
        $alldataid=D('Daydata')->editdataforcom($data['advid'],$data['salerid'],$data['lineid'],$data['strdate'],$data['enddate'],$data['allcomid'],$data['alljfid']);
        foreach ($alldataid as $key => $value) {
            $id_arr[]=$value['id'];
        }
        $id_str=implode(',',$id_arr);
        $strnum_forxunhuan=0;
        while (1) {
            M()->startTrans();
            $res=D('SettlementIn')->edit("id=".I('get.id'),array('status'=>2,'auditer'=>$_SESSION['userinfo']['username'],'audittime'=>date('Y-m-d H:i:s')));
            $res1=D('Daydata')->edit("id in ($id_str)",array('status'=>3,'auditdate'=>date('Y-m-d')));//            数据进入待开票状态
            M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>3,'in_auditdate'=>date('Y-m-d')));
            $result=D('Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
            foreach ($id_arr as $key => $value) {
                $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'结算单通过审核 '.I('get.id'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
            }
            if($res && $res1){
                M()->commit();
                break;
            }else{
                M()->rollback(); 
                $strnum_forxunhuan++;
                if($strnum_forxunhuan>=2){
                    $this->assign('data','操作失败，请联系程序员');
                    $this->display('Public/alertpage');
                    exit();
                }
            }
        }
        $this->assign('data','操作成功');
        if(in_array($data['jsztid'],array(2,9)))$tongziuserlist=12;//,4,6,10
        else $tongziuserlist=19;
        $tongziuserlist=M('auth_group_access')->where("group_id=$tongziuserlist")->select();
        foreach ($tongziuserlist as $key => $value) {
            $t[]=$value['uid'];
        }
        $superinfo=M('Advertiser')->field('name')->where("id=".$data['advid'])->find();
        if($data['iskaipiao'] == 1){//修改于2017.01.11 1代表是要开发票，不开发票则不提示，杨娟提出
            M('prompt_information')->add(array('send_user'=>implode(',',$t),'date_time'=>date('Y-m-d H:i:s'),'content'=>$superinfo['name'].'  '.$data['strdate'].'~'.$data['enddate'].' 收入结算单【已审核】 点击跳转','a_link'=>'/Finance/financeIn?id='.I('get.id')));
        }

        $this->display('Public/alertpage');
    }
    public function settlementInNo(){//不通过审核
        // if (!$this->checkRule('/Home/Makesettlement/settlementInNo')) {
        //     $this->error('您没有访问权限,请联系管理员');
        // }
        $data=D('SettlementIn')->getonedata("id=".I('get.id'));
        $alldataid=D('Daydata')->editdataforcom($data['advid'],$data['salerid'],$data['lineid'],$data['strdate'],$data['enddate'],$data['allcomid'],$data['alljfid']);
        foreach ($alldataid as $key => $value) {
            $id_arr[]=$value['id'];
        }
        $id_str=implode(',',$id_arr);
        $strnum_forxunhuan=0;
        while (1) {
            M()->startTrans();
            $res=D('SettlementIn')->edit("id=".I('get.id'),array('status'=>6,'auditer'=>$_SESSION['userinfo']['username'],'audittime'=>date('Y-m-d H:i:s'),'remark'=>I('get.yy')));
            $res1=D('Daydata')->edit("id in ($id_str)",array('status'=>1));//数据回到未确认状态
            M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>1));
            $result=D('Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
            foreach ($id_arr as $key => $value) {
                $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'结算单未通过审核'.I('get.id').',原因：'.I('get.yy'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
            }
            //未编写提示功能代码
            //
            if($res && $res1){
                M()->commit();
                break;
            }else{
                M()->rollback(); 
                $strnum_forxunhuan++;
                if($strnum_forxunhuan>=2){
                    $this->assign('data','操作失败，请联系程序员');
                    $this->display('Public/alertpage');
                    exit();
                }
            }
        }
        $superinfo=M('Advertiser')->field('name')->where("id=".$data['advid'])->find();
        M('prompt_information')->add(array('send_user'=>$data['struserid'],'date_time'=>date('Y-m-d H:i:s'),'content'=>$superinfo['name'].'  '.$data['strdate'].'~'.$data['enddate'].' 收入结算单【已作废】 点击跳转','a_link'=>'/Makesettlement/settlementInList?id='.I('get.id')));


        if($res) {
            $data = D('SettlementIn')->getonedata("id=" . I('get.id'));
            if ($data['fp_status'] == 1) {
                //发票作废后同步到用友系统 2017.03.08
                mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
                $charid = strtoupper(md5(uniqid(rand(), true)));
                $hyphen = chr(45);// "-"
                $uuid = substr($charid, 0, 8) . $hyphen
                    . substr($charid, 8, 4) . $hyphen
                    . substr($charid, 12, 4) . $hyphen
                    . substr($charid, 16, 4) . $hyphen
                    . substr($charid, 20, 12);
                $gcm = "/finanInter";
                $key = "1qaz#EDC5tgb&UJM";
                $middle = base64_encode($gcm . $key);
                $date_time = date('YmdHi', time());
                $date_time = base64_encode($date_time . 'L');
                $token = $uuid . $middle . $date_time;
                $http_r = 'http://bos3api.yandui.com:16088';

                //发票收入 金额同步为负数
                $fp_f = '/rBillData/insertRBillData';
                $fpModel = M('settlement_in');
                $fp_data = array();
                $fpData = $fpModel->field('bs.enddate as dDate,bs.id as BillCode,bs.invoiceinfo,adv.ad_code as cuscode,adv.`name` as cusname,bl.id as itemcode,bl.`name` AS itemname,adv.invoice_type as taxrate,b_code.`code` AS accid,bs.settlementmoney as money,bs.comid,bs.strdate')
                    ->join('AS bs JOIN boss_advertiser AS adv ON adv.id=bs.advid
JOIN boss_business_line AS bl ON bl.id=bs.lineid
JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid')->where("bs.id=" . I('get.id') . " ")->select();
                $fp_url = $http_r . $gcm . $fp_f . '?token=' . $token;

                foreach ($fpData as $key => $value) {

                    if ($value['taxrate'] == 1) {
                        $fp_data[$key]['TaxRate'] = '0';
                    } elseif ($value['taxrate'] == 2) {
                        $fp_data[$key]['TaxRate'] = '0.03';
                    } elseif ($value['taxrate'] == 3) {
                        $fp_data[$key]['TaxRate'] = '0.06';
                    } elseif ($value['taxrate'] == 4) {
                        $fp_data[$key]['TaxRate'] = '0.09';
                    } elseif ($value['taxrate'] == 5) {
                        $fp_data[$key]['TaxRate'] = '0.17';
                    }
                    $invoiceinfo = json_decode($value['invoiceinfo'], true);
                    $fp_code = "";
                    foreach ($invoiceinfo as $val) {
                        $fp_code .= $val['code'] . ",";
                    }
                    $fp_code = rtrim($fp_code, ",");
                    $fp_data[$key]['BillNo'] = $fp_code;
                    $fp_data[$key]['dDate'] = $value['ddate'];
                    $fp_data[$key]['BillCode'] = $value['billcode'];
                    $fp_data[$key]['CusCode'] = $value['cuscode'];
                    $fp_data[$key]['CusName'] = $value['cusname'];
                    $fp_data[$key]['ItemCode'] = $value['itemcode'];
                    $fp_data[$key]['ItemName'] = $value['itemname'];
                    $fp_data[$key]['AccID'] = $value['accid'];
                    $fp_data[$key]['Money'] = '-' . $value['money'];
                    //结算单查询关账数据 通过产品ID和时间查询
                    $closData = M('closing')->field('sum(bclo.in_newmoney) as inmoney,blc.id,blc.`name`,b_t.`code`,adv.ad_code as cuscode,adv.`name` as cusname,adv.invoice_type as taxrate')->join('AS bclo JOIN boss_advertiser AS adv ON adv.id=bclo.in_adverid
JOIN boss_business_line AS blc ON blc.id=bclo.in_lineid
JOIN boss_data_dic AS b_t ON b_t.dic_type=4 and b_t.id=bclo.in_ztid')->where("bclo.in_comid=" . $value['comid'] . " and adddate>='" . $value['strdate'] . "' and adddate<='" . $value['dDate'] . "'")->find();
                    if ($closData['taxrate'] == 1) {
                        $fp_data[$key]['TTaxRate'] = '0';
                    } elseif ($closData['taxrate'] == 2) {
                        $fp_data[$key]['TTaxRate'] = '0.03';
                    } elseif ($closData['taxrate'] == 3) {
                        $fp_data[$key]['TTaxRate'] = '0.06';
                    } elseif ($closData['taxrate'] == 4) {
                        $fp_data[$key]['TTaxRate'] = '0.09';
                    } elseif ($closData['taxrate'] == 5) {
                        $fp_data[$key]['TTaxRate'] = '0.17';
                    }
                    $fp_data[$key]['TCusCode'] = $closData['cuscode'];
                    $fp_data[$key]['TCusName'] = $closData['cusname'];
                    $fp_data[$key]['TItemCode'] = $closData['id'];
                    $fp_data[$key]['TItemName'] = $closData['name'];
                    $fp_data[$key]['TAccID'] = $closData['code'];//冲销帐套号
                    $fp_data[$key]['TMoney'] = $closData['inmoney'];

                }
                $fp_data = json_encode($fp_data);
                $fp_res = bossPostData_json($fp_url, $fp_data);
                $fpRes = json_decode($fp_res, true);
                //end 2017.03.08
            }
        }

        $this->assign('data','操作成功');
        $this->display('Public/alertpage');
    }
    public function settlementIn(){//收入结算单明细
        $data=D('SettlementIn')->getonedatadetail();
        if(I('get.id')<=1224 && $data['alldataid']!=''){
            $logodata=D('Daydata')->getMakeSettlementInData3($data['alldataid']);
        }else $logodata=D('Daydata')->getMakeSettlementInData2($data['strdate'],$data['enddate'],$data['advid'],$data['salerid'],$data['lineid'],$data['allcomid'],$data['alljfid']);
        $comidarr=array();
        foreach ($logodata as $k => $v) {
            if(!in_array($v['comid'],$comidarr))$comidarr[]=$v['comid'];
        }
        $data_pro=M('product')->where("id in (".implode(',', $comidarr).")")->select();
        foreach ($data_pro as $key => $value) {
            $flfs=($value['package_return_email']!='')?$value['package_return_email']:$value['backstage_adress'];
            $comnamearr[$value['id']]=$flfs;
        }
        foreach ($logodata as $key => $value) {
            $logodata[$key]['url']=$comnamearr[$value['comid']];
        }
        $this->assign('data',$data);
        $this->assign('logodata',$logodata);
        $this->display();
    }
    public function settlementIn_down(){
        $data=D('SettlementIn')->getonedatadetail();

        $logodata=D('Daydata')->getMakeSettlementInData2($data['strdate'],$data['enddate'],$data['advid'],$data['salerid'],$data['lineid'],$data['allcomid'],$data['alljfid']);
        if(empty($logodata))exit('没有数据');
        foreach ($logodata as $key => $value) {
            $logodata[$key]['date']=$value['strtime'].' - '.$value['endtime'];
        }
        $list=array(array('linename','业务线'),array('comname','产品名称'),array('jfname','计费标识名称'),array('jfid','计费标识ID'),array('date','起止日期'),array('endmoney','结算金额'));
        $this->downloadlist($logodata,$list,'产品名细');
    }


        /*
    *成本部分
    */
   


    public function makeSettlementOut(){//生成成本结算单
        if(!empty(I('get.strtime'))){
            $strtime=I('get.strtime');
            $wheres[]="a.adddate >= '$strtime'";
        }else $strtime=date("Y-m-d");
        if(!empty(I('get.endtime'))){
            $endtime=I('get.endtime');
            $wheres[]="a.adddate <= '$endtime'";
        }else $endtime=date("Y-m-d");
        if(!empty(I('get.saler_id')))$wheres[]='g.real_name like "%'.I('get.saler_id').'%"';
        if(!empty(I('get.comname')))$wheres[]='c.name like "%'.I('get.comname').'%"';
        if(!empty(I('get.advername')))$wheres[]='d.name like "%'.I('get.advername').'%"';

        //判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
        $isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr  = $_SESSION["userinfo"]["realname"];
            $wheres[] = " g.real_name like '%".$spidStr."%'";
        }

        
        if(count($wheres)>0){
            //数据权限
            $arr_name=array();
            $arr_name['line']=array('a.lineid');
            $arr_name['user']=array('a.businessid');
            $ruleser=new Service\RuleService();
            $myrule_data=$ruleser->getmyrule_data($arr_name);
            $wheres[]= $myrule_data;

            
            $where=implode(' && ', $wheres);
            $data=D('DaydataOut')->getSetDataList($where,$p);
            $this->assign('data',$data);
        }
        $this->assign('strtime',$strtime);
        $this->assign('endtime',$endtime);
        $this->display();
    }
    public function makeSettlementOutDo(){//提交生成成本结算单
        $data['strtime']=I('post.strtime');
        $data['endtime']=I('post.endtime');
        $data['cl_id']=I('post.cl_id');
        $data_data=M('daydata_out')->field('superid')->where("adddate>='".$data['strtime']."' && adddate<='".$data['endtime']."' && jfid in (".$data['cl_id'].")")->group('superid')->select();
        if(count($data_data)>1){
            echo json_encode(array('msg'=>'你选择了多个供应商的计费标识','type'=>2));
            exit();
        }
        $data['superid']=$data_data[0]['superid'];
        $timestamp=time();
        $data['sign']=md5('b#a$b%s@v&*'.$timestamp);
        $data['ts']=$timestamp;
        $data['appid']='103';

        $ch = curl_init();
        $host = array("Host: it.yandui.com");
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/api/data/makeSettlementOutDoApi');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$host);
        $response = curl_exec($ch);
        if($error=curl_error($ch)){
            die($error);
        }
        curl_close($ch);    

        //useApiLog($url,json_encode($data),$response);

        echo $response;
        
    }
    public function outdetail(){
        $data=D('SettlementOut')->getonedata("id=".I('post.id'));
        $buer=M('user')->where("id in (".$data['sangwuid'].")")->find();
        $jszt=M('data_dic')->field("name as jszt")->where("id=".$data['jsztid'])->find();
        
        $jszt['sealname']=$buer['real_name'];
        $advcontacts=D('Supplier')->getonedata("id=".$data['superid']);
        if($advcontacts['type']==1){
            $advarr=D('Supplier')->getdata("type=3");
            $idarr=array();
            foreach ($advarr as $key => $value) {
                $idarr[]=$value['id'];
            }
            $advbankdata=D('SupplierFinance')->getdata("sp_id in (".implode(',',$idarr).")");
            foreach ($advbankdata as $key => $value) {
                $advbankdata[$key]['invoice_type']=C('option.invoice_type')[$value['invoice_type']];
            }
        }else{
            $advbankdata=D('SupplierFinance')->getonedata("sp_id=".$data['superid']." && bl_id=".$data['lineid']);
            $advbankdata['invoice_type']=C('option.invoice_type')[$advbankdata['invoice_type']];
        }
        if(empty($advbankdata))exit(json_encode(array('msg'=>'没有找到相关财务信息','status'=>0)));

        echo json_encode(array('settlementdata'=>$data,'advcontacts'=>$advcontacts,'advbankdata'=>$advbankdata,'jszt'=>$jszt,'status'=>1));
    }
    public function delSettlementOut(){//取消成本结算单//
        $res=D('SettlementOut')->deldata("id=".I('get.id'));
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    public function confirmSettlementOut(){//确认生成成本结算单
        $timestamp=time();
        $data['sign']=md5('b#a$b%s@v&*'.$timestamp);
        $data['ts']=$timestamp;
        $data['appid']='103';
        $data['username']=$_SESSION['userinfo']['uid'];
        $data['advcontactsid']=I('post.advcontactsid');
        $data['id']=I('post.id');

        /*$res=bossPostData('http://'.$_SERVER['HTTP_HOST'].'/api/data/makeSettlementOutDoOkApi',$data);
        $data_res=json_decode($res,true);*/
        $ch = curl_init();
        $host = array("Host: it.yandui.com");
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/api/data/makeSettlementOutDoOkApi');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$host);
        $response = curl_exec($ch);
        if($error=curl_error($ch)){
            die($error);
        }
        curl_close($ch);

        //useApiLog($url,json_encode($data),$response);

        $this->assign('data',$response['msg']);

        $this->display('Public/alertpage2');
    }
    public function settlementOutList(){//成本结算单列表
        if($_SESSION['userinfo']['uid']==128){
            $w=" && a.lineid=2";
        }else{
            $w="";
        }
        $count=D('SettlementOut')->getlistcount("(a.status=1 || a.status=6 || a.status=2 || a.status=4)$w");
        $this->getpagelist($count);
        $data=D('SettlementOut')->getlist("(a.status=1 || a.status=6 || a.status=2 || a.status=4)$w");
        $this->assign('data',$data);
        $getSum = D('SettlementOut')->getSum("(a.status=1 || a.status=6 || a.status=2 || a.status=4)$w");//获取求和数据
        $this->assign('getSum',$getSum);
        $dic=M('data_dic')->where("dic_type=12")->select();
        $this->assign('dic',$dic);

        $this->display();
    }
    public function downloadsetoutlist(){
        $data=D('SettlementOut')->getlist();
        foreach ($data as $key => $value) {
            $data[$key]['date']=$value['strdate'].'至'.$value['enddate'];
        }
        $list=array(array('id','结算单编号'),array('advname','供应商名称'),array('comname','产品名称'),array('date','账单期间'),array('settlementmoney','结算金额（含税）'),array('status','状态'),array('real_name','所属商务'));
        $this->downloadlist($data,$list,'成本结算单列表');
    }
    public function settlementOutOk(){//通过审核
        if (!$this->checkRule('/Home/Makesettlement/settlementOutOk')) {
            $this->error('您没有访问权限,请联系管理员');
        }
        $type=I('get.type');//结算单状态
        $datatype=$type+1;//数据状态
        if($type==3)$datatype=5;//通过负责人审核
        $data=D('SettlementOut')->getonedata("id=".I('get.id'));
        $alldataid=D('DaydataOut')->editdataforcom($data['superid'],$data['sangwuid'],$data['lineid'],$data['strdate'],$data['enddate'],$data['alljfid']);
        foreach ($alldataid as $key => $value) {
            $id_arr[]=$value['id'];
        }
        $id_str=implode(',',$id_arr);
        $strnum_forxunhuan=0;
        while (1) {
            M()->startTrans();
            $auditer='auditer';
            if($datatype==5)$auditer='bauditer';
            if($datatype==6)$auditer='cauditer';
            $res=D('SettlementOut')->edit("id=".I('get.id'),array('status'=>$type,$auditer=>$_SESSION['userinfo']['username'],'audittime'=>date('Y-m-d H:i:s')));
            $res1=D('DaydataOut')->edit("id in ($id_str)",array('status'=>$datatype,'auditdate'=>date('Y-m-d')));
            M('daydata_inandout')->where("out_id in ($id_str)")->save(array('out_status'=>$datatype,'out_auditdate'=>date('Y-m-d')));
            foreach ($id_arr as $key => $value) {
                $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'结算单通过审核 '.I('get.id'),'datatype'=>2,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
            }
            if($res && $res1){
                $postres=postDatatoorther($alldataid,3,I('get.id'),2);
                if($postres->status==2){

                    $this->assign('data','ssp同步失败：'.$postres->msg);
                    $this->display('Public/alertpage');
                    exit();
                }
                M()->commit();
                
                break;
            }else{
                M()->rollback(); 
                $strnum_forxunhuan++;
                if($strnum_forxunhuan>=2){
                    $this->assign('data','操作失败，请联系程序员'.$id_str);
                    $this->display('Public/alertpage');
                    exit();
                }
            }
        }
        $this->assign('data','操作成功');
        $superinfo=M('supplier')->field('name')->where("id=".$data['superid'])->find();
        M('prompt_information')->add(array('send_user'=>$data['struserid'],'date_time'=>date('Y-m-d H:i:s'),'content'=>$superinfo['name'].'  '.$data['strdate'].'~'.$data['enddate'].' 成本结算单【已通过运营审核】 点击跳转','a_link'=>'/Finance/payment?id='.I('get.id')));
        $this->display('Public/alertpage');
    }
    public function settlementOutNo(){//不通过审核
        $setid=I('get.id');
        $liuchen_data=M('oa_66')->field('b.liuchenid')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=66')->where("find_in_set($setid,a.x739c8a_13) && b.status!=0 && b.nowsort!=0")->find();
        if($liuchen_data){
            $this->assign('data','有相关流程正在执行，不能作废，流程号：'.$liuchen_data['liuchenid']);
            $this->display('Public/alertpage');
            exit();
        }
        $liuchen_data2=M('oa_67')->field('b.liuchenid')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=67')->where("find_in_set($setid,a.x29cc4e_17)")->find();
        if($liuchen_data2){
            $this->assign('data','有相关流程正在执行，不能作废，流程号：'.$liuchen_data2['liuchenid']);
            $this->display('Public/alertpage');
            exit();
        }
        $data=D('SettlementOut')->getonedata("id=".I('get.id'));
        $alldataid=D('DaydataOut')->editdataforcom($data['superid'],$data['sangwuid'],$data['lineid'],$data['strdate'],$data['enddate'],$data['alljfid']);
        foreach ($alldataid as $key => $value) {
            $id_arr[]=$value['id'];
        }
        $id_str=implode(',',$id_arr);
        $strnum_forxunhuan=0;
        while (1) {
            M()->startTrans();
            $res=D('SettlementOut')->edit("id=".I('get.id'),array('status'=>6,'auditer'=>$_SESSION['userinfo']['username'],'audittime'=>date('Y-m-d H:i:s'),'remark'=>I('get.yy')));
            $res1=D('DaydataOut')->edit("id in ($id_str)",array('status'=>1));
            M('daydata_inandout')->where("out_id in ($id_str)")->save(array('out_status'=>1));
            foreach ($id_arr as $key => $value) {
                $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'结算单未通过审核 '.I('get.id').',原因：'.I('get.yy'),'datatype'=>2,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
            }
            //未编写提示功能代码
            //
            if($res && $res1){
                $postres=postDatatoorther($alldataid,1,I('get.id'),6);
                if($postres->status==2){

                    $this->assign('data','ssp同步失败：'.$postres->msg);
                    $this->display('Public/alertpage');
                    exit();
                }
                M()->commit();
                
                break;
            }else{
                M()->rollback(); 
                $strnum_forxunhuan++;
                if($strnum_forxunhuan>=5){
                    $this->assign('data','操作失败，请联系程序员');
                    $this->display('Public/alertpage');
                    exit();
                }
            }
        }
        $this->assign('data','操作成功');
        $superinfo=M('supplier')->field('name')->where("id=".$data['superid'])->find();
        M('prompt_information')->add(array('send_user'=>$data['struserid'],'date_time'=>date('Y-m-d H:i:s'),'content'=>$superinfo['name'].'  '.$data['strdate'].'~'.$data['enddate'].' 成本结算单【已作废】 点击跳转','a_link'=>'/Makesettlement/settlementOutList?id='.I('get.id')));
        $this->display('Public/alertpage');
    }
    public function settlementOut(){//成本结算单明细
        $data=D('SettlementOut')->getonedatadetail();
        if(I('get.id')>1346){
            $logodata=D('DaydataOut')->getMakeSettlementOutData3($data['strdate'],$data['enddate'],$data['superid'],$data['sangwuid'],$data['jsztid'],$data['alljfid']);
        }else{
            $logodata=D('DaydataOut')->getMakeSettlementOutData2($data['strdate'],$data['enddate'],$data['alljfid'],$data['alldataid']);
        }
        $this->assign('data',$data);
        $this->assign('logodata',$logodata);
        $this->display();
    }
    public function settlementOut_down(){
        $data=D('SettlementOut')->getonedatadetail();
        if(I('get.id')>1346){
            $logodata=D('DaydataOut')->getMakeSettlementOutData3($data['strdate'],$data['enddate'],$data['superid'],$data['sangwuid'],$data['jsztid'],$data['alljfid']);
        }else{
            $logodata=D('DaydataOut')->getMakeSettlementOutData2($data['strdate'],$data['enddate'],$data['alljfid'],$data['alldataid']);
        }
        if(empty($logodata))exit('没有数据');
        foreach ($logodata as $key => $value) {
            $logodata[$key]['date']=$value['strtime'].' - '.$value['endtime'];
        }
        $list=array(array('linename','业务线'),array('comname','产品名称'),array('jfname','计费标识名称'),array('jfid','计费标识ID'),array('date','起止日期'),array('endmoney','结算金额'),array('inmoney','收入金额'),array('lirun','毛利'),array('instatus','收入状态'));
        $this->downloadlist($logodata,$list,'产品名细');
    }

    /*2016.12.21
     * 同步采购发票信息到财务系统
     * */
    public function PbillData(){
        //$t_strtime = $_POST['t_strtime'];

        $sid = I('post.sid');
        $outdata = I('post.outdata');
        $sid = implode(',',$sid);//结算单ID

        if($sid){
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            $gcm  = "/finanInter";
            $key = "1qaz#EDC5tgb&UJM";
            $middle = base64_encode($gcm.$key);
            $date_time = date('YmdHi',time());
            $date_time = base64_encode($date_time.'L');
            $token = $uuid.$middle.$date_time;
            $http_r = 'http://bos3api.yandui.com:16088';

            //采购发票(成本结算单审核通过的记录)
            $cg_f = '/pBillData/insertPBillData';
            $cgModel = M('settlement_out');
            $cg_url = $http_r.$gcm.$cg_f.'?token='.$token;
            $cg_data = array();

            $where = 'bs.`status` IN (1,2,3,4,5)';
            $where .=" and bs.id in (".$sid.")";

            $cgData = $cgModel->field("DATE_FORMAT(bs.addtime,'%Y-%m-%d') as ddate,bs.id as BillCode,sup.`code` as cVenCode,bs.showkuanname as cVenName,b_code.`code` AS AccID,bs.tax as taxrate,bs.settlementmoney as money,bs.notaxmoney,bs.strdate,alljfid,bl.id as bl_code,bl.name as bl_name,sup_fin.invoice_type,bs.tbmoney")->join('AS bs
LEFT JOIN boss_supplier AS sup ON sup.id=bs.addresserid
LEFT JOIN boss_supplier_finance sup_fin on sup_fin.sp_id=sup.id and sup_fin.bl_id=bs.lineid
LEFT JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid
LEFT JOIN boss_business_line as bl on bl.id=bs.lineid
')->where($where)->order('bs.id desc')->select();
            $out_id = '';
            foreach($cgData as $key=>$val) {
                    $cg_data[$key]['BillCode'] = $val['billcode'];
                    $cg_data[$key]['cVenCode'] = $val['cvencode'];
                    $cg_data[$key]['cVenName'] = $val['cvenname'];
                    $cg_data[$key]['AccID'] = $val['accid'];
                    $cg_data[$key]['ItemCode'] = $val['bl_code'];
                    $cg_data[$key]['ItemName'] = $val['bl_name'];
                    $closModel = M('closing');
                    $jfData = $closModel->field('b_t.`code` AS ztid,sum(bclo.out_newmoney) as outmoney,bsfi.financial_tax AS t_fin,blt.id as blt_code,blt.name as blt_name,sup.`code` as cvencode,sup.`name` as cvenname')->join('AS bclo
LEFT JOIN boss_supplier AS sup ON sup.id=bclo.out_superid
LEFT JOIN boss_business_line as blt on blt.id=bclo.out_lineid
LEFT JOIN boss_data_dic AS b_t ON b_t.dic_type=4 and b_t.id=bclo.out_sbid
LEFT JOIN boss_supplier_finance AS bsfi ON bsfi.bl_id=bclo.out_lineid AND bsfi.sp_id=bclo.out_superid')->where("bclo.adddate>='" . $val['strdate'] . "' and bclo.adddate<='" . $val['ddate'] . "' and jfid in (" . $val['alljfid'] . ")")->find();
                    $cg_data[$key]['TVenCode'] = $jfData['cvencode'];
                    $cg_data[$key]['TVenName'] = $jfData['cvenname'];
                    $cg_data[$key]['TAccID'] = $jfData['ztid'];
                    $cg_data[$key]['TTaxRate'] = $jfData['t_fin'];
                    $cg_data[$key]['TMoney'] = $jfData['outmoney'];
                    $cg_data[$key]['TItemCode'] = $jfData['blt_code'];
                    $cg_data[$key]['TItemName'] = $jfData['blt_name'];
                foreach ($outdata as $val2) {

                    if($val['billcode'] == $val2[0]){//结算单id相同
                        if($val['taxrate']>0){//税点大于0 传不含税金额

                            if($val2[1] == $val['notaxmoney']){//一次同步完

                                $cg_data[$key]['Money'] = $val['notaxmoney'];
                            }elseif($val2[1]<$val['notaxmoney']){//分多次同步

                                $cg_data[$key]['Money'] = $val2[1];
                            }else{

                                $this->ajaxReturn("金额有误，请重新输入");exit;
                            }
                            $data['tbmoney'] = $val['tbmoney'] + $val2[1];
                            $cgModel->where("id=".$val['billcode'])->save($data);

                        }else{
                            $cg_data[$key]['Money'] = $val2[1];
                        }

                        if($val['invoice_type'] == 3){
                            $cg_data[$key]['TaxRate'] = '0.06';
                        }elseif($val['invoice_type'] == 4){
                            $cg_data[$key]['TaxRate'] = '0.09';
                        }elseif($val['invoice_type'] == 5){
                            $cg_data[$key]['TaxRate'] = '0.17';
                        }elseif($val['invoice_type'] == 7){
                            $cg_data[$key]['TaxRate'] = '0.03';
                        }else{
                            $cg_data[$key]['TaxRate'] = '0';
                        }

                        $cg_data[$key]['dDate'] = $val2[3];
                        if( ($val2[1] == $val['notaxmoney']) or ( ($val['notaxmoney'] - $data['tbmoney']) ==0)){//
                            $out_id .= $val2[0].",";
                        }
                    }

                }
            }
            $out_id = rtrim($out_id,",");
            $cg_data = json_encode($cg_data);
            $cg_res = bossPostData_json($cg_url,$cg_data);
            $cgRes = json_decode($cg_res,true);
            if($cgRes['message']== "success"){
                //修改成本结算单状态 fp_status
                $data['fp_status'] = 1;
                if($out_id){
                    $cgModel->where("id in (".$out_id.")")->save($data);
                }

                $this->ajaxReturn("TRUE");exit;
            }else{
                $this->ajaxReturn("同步采购发票数据失败");exit;
            }
        }else{
            $this->ajaxReturn("日期不能为空，请重新选择");exit;
        }
    }

    /*>query("select b_t.`code` AS ztid,bclo.outmoney,bsfi.financial_tax AS t_fin,bl.id as bl_code,bl.name as bl_name,blt.id as blt_code,blt.name as blt_name from boss_closing AS bclo
    LEFT JOIN boss_supplier AS sup ON sup.id=bclo.out_superid
    LEFT JOIN boss_business_line as blt on blt.id=bclo.lineid
    LEFT JOIN boss_product AS bp ON bp.id=bclo.comid
    LEFT JOIN boss_data_dic AS b_t ON b_t.dic_type=4 and b_t.id=bp.sb_id
    LEFT JOIN boss_supplier_finance AS bsfi ON bsfi.bl_id=bclo.lineid AND bsfi.sp_id=bclo.superid WHERE bclo.adddate>='".$val['strdate']."' and bclo.adddate<='".$val['dDate']."' and jfid in (".$val['alljfid'].")");*/

    /*去开票
     * 2016.12.27*/
    public function change_one(){
        $sid = $_POST['sid'];
        if(!empty($sid)){

            //修改状态
            $cm = M('settlement_in');;
            $map = array();
            $map['id']= $sid;
            $map['iskaipiao'] = 1;
            if ($cm->save($map) === false) {
                $this->ajaxReturn($cm->getError());
            }else{
                $this->ajaxReturn("TRUE");
            }

        }
    }

    /*成本-负责人审核*/
    public function Check_b(){
        $count=D('SettlementOut')->getlistcount("(a.status=3 || a.status=6 || a.status=2)",1);
        $this->getpagelist($count);
        $data=D('SettlementOut')->getlist("(a.status=3 || a.status=6 || a.status=2)",1);
        $this->assign('data',$data);
        $getSum = D('SettlementOut')->getSum("(a.status=3 || a.status=6 || a.status=2)",1);//获取求和数据
        $this->assign('getSum',$getSum);
        $dic=M('data_dic')->where("dic_type=12")->select();
        $this->assign('dic',$dic);

        $this->display();
    }

    /*成本-财务审核*/
    public function Check_c(){
        
        $count=D('SettlementOut')->getlistcount("(a.status=3  || a.status=5)");
        $this->getpagelist($count);
        $data=D('SettlementOut')->getlist("(a.status=3  || a.status=5)");
        foreach ($data as $k => $v) {
            $day_data=M('daydata')->where("adddate>='".$v['strdate']."' && adddate<='".$v['enddate']."' && jfid in (".$v['alljfid'].") && status not in (5,8)")->find();
            if($day_data)unset($data[$k]);
        }
        $this->assign('data',$data);
        $this->display();
    }

    public function index(){
        $where = array();
        $this->assign('outsettlement_status', C('OPTION.outsettlement_status'));
        $list = $this->lists($this, $where);
        $this->assign('date',date('Y-m-d'));
        $this->assign('data',$list);
        $this->display();
    }

    public function getList($where) {
        $where  = 'a.status in (1,2,3,4,5) and a.fp_status=0';
        if(!empty(I('get.ggzname')))$where .=" and g.fukuanname like '%".I('get.ggzname')."%'";
        if(!empty(I('get.comname')))$where .=" and a.allcomname like '%".I('get.comname')."%'";
        if(!empty(I('get.jfname')))$where .=" and e.real_name like '%".I('get.jfname')."%'";
        if(!empty(I('get.strtime')))$where .="and a.strdate >= '".I('get.strtime')."'";
        if(!empty(I('get.endtime')))$where .=" and a.enddate <= '".I('get.endtime')."'";
        if(!empty(I('get.sid')))$where .=" and a.id in  (".I('get.sid').")";
        $ml = M('settlement_out');
        $white_list = $ml->field('a.id,a.showkuanname as advname,a.allcomname as comname,a.strdate,a.enddate,a.settlementmoney,a.status,a.sangwuid,a.jsztid,a.superid,a.tax,a.notaxmoney,a.alljfid,e.real_name,a.tbmoney')->join('a left join boss_supplier g on a.addresserid=g.id left join boss_charging_logo c on a.jfid=c.id left join boss_product d on c.prot_id=d.id left join boss_business_line b on d.bl_id=b.id left join boss_user e on a.sangwuid=e.id')->where($where)->order('a.id desc')->page($_GET['p'],10)->select();
        foreach($white_list as $key=>$val){
            if($val['tax']>0){
                $white_list[$key]['settlementmoney'] = $val['notaxmoney'] - $val['tbmoney'];
                $white_list[$key]['tax'] = 0;
            }
        }
        $subQuery = $ml
            ->field('a.id')
            ->join('
				  a left join boss_supplier g on a.addresserid=g.id left join boss_charging_logo c on a.jfid=c.id left join boss_product d on c.prot_id=d.id left join boss_business_line b on d.bl_id=b.id left join boss_user e on a.sangwuid=e.id')
            ->where($where)
            ->buildSql();
        $this->totalPage = $ml->table($subQuery.' aa')->where()->count();

        //$Row = $ml->where($where)->count();
        //$this->totalPage =$Row;
        return $white_list;
    }
    public function makeliuchen(){
        $count=D('SettlementOut')->getlistcount("(a.status=1 || a.status=6 || a.status=2)");
        $this->getpagelist($count);
        $data=D('SettlementOut')->getlist("(a.status=1 || a.status=6 || a.status=2)");
        $this->assign('data',$data);
        $getSum = D('SettlementOut')->getSum("(a.status=1 || a.status=6 || a.status=2)");//获取求和数据
        $this->assign('getSum',$getSum);
        $dic=M('data_dic')->where("dic_type=12")->select();
        $this->assign('dic',$dic);
        $this->display();
    }
}