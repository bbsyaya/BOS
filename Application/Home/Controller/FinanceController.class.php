<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Org\Util\PHPEXCEL;
use Common\Service;
class FinanceController extends BaseController {//财务管理
    public function financeIn(){//应收管理
        $total=D('SettlementIn')->getFcount();
        $this->getpagelist($total);
    	$data=D('SettlementIn')->getFinancelistdata();
        $this->assign('data',$data);
        $sum_data=D('SettlementIn')->getSum();
        $this->assign('getSum',$sum_data);
        $jszt=M('data_dic')->where("dic_type=4")->select();
        $this->assign('dic',$jszt);
        //id
        $this->assign('sid',$_GET['id']);
    	$this->display();
    }
    public function downloadfinanceinlist(){
        $data=D('SettlementIn')->getFinancelistdata(1);
        foreach ($data as $key => $value) {
            $data[$key]['date']=$value['strdate'].'至'.$value['enddate'];
            $data[$key]['dskmoney']=$value['settlementmoney']-$value['yskmoney'];
        }
        $list=array(array('id','结算单编号'),array('advname','广告主名称'),array('comname','产品名称'),array('date','账单期间'),array('settlementmoney','结算金额（含税）'),array('jszt','结算主体'),array('status','状态'),array('bankname','收款账户'),array('yskmoney','已回款金额'),array('dskmoney','待回款金额'),array('real_name','所属销售'),array('iskaipiao','是否开票'));
        $this->downloadlist($data,$list,'应收列表');
    }
    public function makeInvoiceApi(){//开发票接口
    	$settlementindata=D('SettlementIn')->getonedata("id=".I('post.id'));
        if($settlementindata['auditer']!=''){
            $auditername=M('user')->where("username='".$settlementindata['auditer']."'")->find();
            $settlementindata['auditer']=$auditername['real_name'];
        }
    	$adverinfo=D('Advertiser')->getonedata("id=".$settlementindata['advid']);
    	$jszt=M('data_dic')->where("id=".$settlementindata['jsztid'])->find();
        $saler=M('user')->where("id=".$settlementindata['salerid'])->find();
    	$daydatainfo=D('Daydata')->getMakeSettlementInData2($settlementindata['strdate'],$settlementindata['enddate'],$settlementindata['advid'],$settlementindata['salerid'],$settlementindata['lineid'],$settlementindata['allcomid'],$settlementindata['alljfid']);
        foreach ($daydatainfo as $key => $value) {
            $daydatainfo[$key]['enddata']=twonum($value['enddata']);
            $daydatainfo[$key]['endmoney']=twonum($value['endmoney']);            
        }
    	$sjrinfo=M('advertiser_fireceiver')->where("id=".$settlementindata['addresseeid'])->find();
    	echo json_encode(array('data'=>$settlementindata,'jszt'=>array('jszt'=>$jszt['name'],'sealname'=>$saler['real_name']),'daydatainfo'=>$daydatainfo,'adverinfo'=>$adverinfo,'sjrinfo'=>$sjrinfo));
    }
    public function makeInvoiceDo(){//开发票

    	$expresscode=(int)I('post.expresscode');
    	$i='';
    	while (1) {
    		if(!empty(I('post.money'.$i)) && !empty(I('post.financeCode'.$i))){
    			$data[]=array('money'=>(float)str_replace(',','',I('post.money'.$i)),'code'=>(float)I('post.financeCode'.$i));
    			$i++;
    		}else{
    			break;
    		}
    	}
    	$invoiceinfo=json_encode($data);
    	$id=(int)I('post.id');
        $olddata=D('SettlementIn')->getonedata("id=".$id);
        $alldataid=D('Daydata')->editdataforcom($olddata['advid'],$olddata['salerid'],$olddata['lineid'],$olddata['strdate'],$olddata['enddate'],$olddata['allcomid'],$olddata['alljfid']);
        $id_arr=array();
        foreach ($alldataid as $key => $val) {
            $id_arr[]=$val['id'];
        }
        $id_str=implode(',',$id_arr);
        if($olddata['status']==2){
            $res=D('SettlementIn')->edit("id=$id",array('expresscode'=>$expresscode,'invoiceer'=>$_SESSION['userinfo']['username'],'invoicetime'=>date('Y-m-d H:i:s'),'invoiceinfo'=>$invoiceinfo,'status'=>3));

            //添加发票表 tgd 2017-03-15
            $inData["id"]          = $id;
            $inData["expresscode"] = $expresscode;
            $inData["invoiceinfo"] = $invoiceinfo;
            $this->addInvoiceData($inData);

            $res1=D('Daydata')->edit("id in ($id_str)",array('status'=>4));
            M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>4));
            $result=D('Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
            foreach ($id_arr as $key => $value) {
                $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'开发票 '.I('get.id').',原因：'.I('get.yy'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
            }
        }elseif($olddata['status']==5){
            $res=D('SettlementIn')->edit("id=$id",array('expresscode'=>$expresscode,'invoiceer'=>$_SESSION['userinfo']['username'],'invoicetime'=>date('Y-m-d H:i:s'),'invoiceinfo'=>$invoiceinfo,'status'=>4));

            //添加发票表 tgd 2017-03-15
            $inData["id"]          = $id;
            $inData["expresscode"] = $expresscode;
            $inData["invoiceinfo"] = $invoiceinfo;
            $this->addInvoiceData($inData);

            $res1=D('Daydata')->edit("id in ($id_str)",array('status'=>5));
            M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>5));
            $result=D('Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
            foreach ($id_arr as $key => $value) {
                $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'开发票 '.I('get.id').',原因：'.I('get.yy'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
            }
        }
		if($res){
            $this->assign('data','操作成功！');
            $this->display('Public/alertpage');
            return;
		}else{
            $this->assign('data','操作失败');
            $this->display('Public/alertpage');
            return;
		}
    }

    /**
     * 添加发票数据 tgd 2017-03-15 17:32
     * @param [type] $invoiData [description]
     */
    private function addInvoiceData($invoiData){
        $invo_json = json_decode($invoiData["invoiceinfo"],true);
        foreach ($invo_json as $k => $v) {
            $datas = array();
            $datas["money"]        = $v["money"];
            $datas["invoice_no"]   = $v["code"];
            $datas["income_st_id"] = $invoiData["id"];
            $datas["track_no"]     = $invoiData["expresscode"];
            $row                   = M("invoice")->add($datas);
        }
        //修改导出
        $row = M("settlement_in")->where(array("id"=>$invoiData["id"]))->save(array("is_export"=>0));
    }


    public function financeInDetail(){//应收记录明细
    	$data=D('SettlementIn')->getonedatadetail();
        if(I('get.id')<=1224 && $data['alldataid']!=''){
            $logodata=D('Daydata')->getMakeSettlementInData3($data['alldataid']);
        }else $logodata=D('Daydata')->getMakeSettlementInData2($data['strdate'],$data['enddate'],$data['advid'],$data['salerid'],$data['lineid'],$data['allcomid'],$data['alljfid']);
        $this->assign('data',$data);
        $this->assign('logodata',$logodata);
        $invoiceinfo=json_decode($data['invoiceinfo']);
        foreach ($invoiceinfo as $key => $value) {
            $thisinc=get_object_vars($value);
            $inc_data=M('invoice')->where("income_st_id=".I('get.id')." && invoice_no='".$thisinc['code']."' && money='".$thisinc['money']."'")->find();
            $thisinc['dataid']=$inc_data['id'];
        	$invoiceinfo[$key]=$thisinc;
        }
        $this->assign('invoiceinfo',$invoiceinfo);
        $rkinfo=D('Rkrecord')->getdataforsettlementid(I('get.id'));
        $this->assign('rkinfo',$rkinfo);
        $dkinfo=D('Dkrecord')->getdataforsettlementid(I('get.id'));
        $this->assign('dkinfo',$dkinfo);
        $this->display();
    }

    public function beforepaylist(){//预收管理
        $total=D('BeforepayGgzAll')->getBcount();
        $this->getpagelist($total);
    	$data=D('BeforepayGgzAll')->getlist();
    	$this->assign('data',$data);
        $y=0;
        $d=0;
        $a=0;
        foreach ($data as $key => $value) {
            $a+=$value['allmoney'];
            $y+=$value['yhxmoney'];
            $d+=$value['dhxmoney'];
        }
        $this->assign('a',$a);
        $this->assign('y',$y);
        $this->assign('d',$d);
    	$this->display();
    }
    public function downloadbeforepaylist(){//预收导出
        $data=D('BeforepayGgzAll')->getlist(1);
        foreach ($data as $key => $value) {
            if($value['dhxmoney']==0)$data[$key]['kxzt']='已核销';
            else $data[$key]['kxzt']='未核销';
        }
        $list=array(array('id','编号'),array('name','广告主名称'),array('allmoney','预收金额'),array('yhxmoney','已核销金额'),array('dhxmoney','待核销金额'),array('kxzt','款项状态'));
        $this->downloadlist($data,$list,'预收列表');
    }
    public function beforepayApi(){//预收抵应收数据接口
    	$adverid=(int)I('post.id');
    	$data=D('SettlementIn')->getlistForAdverid($adverid,' && (a.status=3 || a.status=2)');
    	echo json_encode($data);
    }
    public function moneyOkDo(){//确认抵扣预付款
        $id=(int)I('post.adverid');
    	$Beforepayinfo=D('BeforepayGgzAll')->getonedata("adverid=$id");
    	$num=0;
    	while (1) {
    		$bool=true;
    		$allmoney=0;
    		M()->startTrans();
	    	foreach (I('post.financeid') as $key => $value) {
	    		$data=D('SettlementIn')->getonedata("id=".$value);
	    		$res=D('SettlementIn')->edit("id=".$value,array('yskmoney'=>I('post.Financemoney'.$value)+$data['yskmoney']));
	    		$dkid=D('Dkrecord')->adddata(array('dkerid'=>$_SESSION['userinfo']['uid'],'skjsdid'=>$value,'money'=>I('post.Financemoney'.$value),'time'=>date('Y-m-d H:i:s'),'adverid'=>$id));
	    		$allmoney+=I('post.Financemoney'.$value);
	    		if(!$res){
	    			M()->rollback(); 
	    			$num++;
	    			if($num>=2){
                        $this->assign('data','操作失败');
                        $this->display('Public/alertpage');
                        return;
	    				$bool=false;
	    			}
	    			break;
	    		}
	    	}
	    	if(!$bool)continue;
	    	$res=D('BeforepayGgzAll')->edit("adverid=$id",array('yhxmoney'=>$Beforepayinfo['yhxmoney']+$allmoney,'dhxmoney'=>$Beforepayinfo['dhxmoney']-$allmoney));

	    	if(!$res){
	    		M()->rollback();
	    		$num++;
    			if($num>=2){
    				$this->assign('data','操作失败');
                    $this->display('Public/alertpage');
                    return;
    				$bool=false;
    				break;
    			}
	    	}else{
	    		$data_SI=D("SettlementIn")->getdata("settlementmoney=yskmoney && (status=3 || status=2) && advid=$id");

	    		foreach ($data_SI as $key => $value) {
	    			$alldataid=D('Daydata')->editdataforcom($value['advid'],$value['salerid'],$value['lineid'],$value['strdate'],$value['enddate'],$value['allcomid'],$value['alljfid']);
	    			$id_arr=array();
	    			foreach ($alldataid as $key => $val) {
			            $id_arr[]=$val['id'];
			        }
			        $id_str=implode(',',$id_arr);
                    if($value['status']==3){
                        $res1=D('Daydata')->edit("id in ($id_str)",array('status'=>5));
                        M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>5));
                        $result=D('Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
                        $res2=D('SettlementIn')->edit("id=".$value['id'],array('status'=>4));
                        foreach ($id_arr as $key => $value) {
                            $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'认款完成 '.I('get.id').',原因：'.I('get.yy'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
                        }
                    }elseif($value['status']==2){
                        $res1=D('Daydata')->edit("id in ($id_str)",array('status'=>8));
                        M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>8));
                        $result=D('Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
                        $res2=D('SettlementIn')->edit("id=".$value['id'],array('status'=>5));
                        foreach ($id_arr as $key => $value) {
                            $logid=D('DaydataLog')->adddata(array('dataid'=>$value,'remark'=>'认款完成 '.I('get.id').',原因：'.I('get.yy'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
                        }
                    }
			        
	    			if(!$res1 || !$res2){
	    				M()->rollback();
	    				$num++; 
	    				if($num>=2){
		    				$this->assign('data','操作失败');
                            $this->display('Public/alertpage');
                            return;
		    			}
		    			$bool=false;
		    			break;
	    			}
	    		}
	    		if(!$bool)continue;
	    		M()->commit();
	    		$this->assign('data','操作成功');
                $this->display('Public/alertpage');
                return;
	    		break;
	    	}
    	}
    }
    public function beforepaydetail(){
    	$data=D('BeforepayGgzAll')->getonedata("id=".I('get.id'));
    	if(!$data)$this->error('数据不存在');
    	$advinfo=D('Advertiser')->getonedata("id=".$data['adverid']);
    	$rkinfo=D('Rkrecord')->getdataforadvid($data['adverid']);
    	$dkinfo=D('Dkrecord')->getdataforadvid($data['adverid']);
    	$this->assign('data',$data);
    	$this->assign('advinfo',$advinfo);
    	$this->assign('rkinfo',$rkinfo);
    	$this->assign('dkinfo',$dkinfo);
    	$this->display();
    }
    public function adminMoney(){//到款管理
        $total=D('Pay')->getPcount();
        $this->getpagelist($total);
    	$data=D('Pay')->getMoneyListData();
    	$this->assign('data',$data);
        $sum_data=D('Pay')->getSum();
    	$this->assign('getSum',$sum_data);
        $res=M('business_line')->select();
        $this->assign('linelist',$res);
    	$this->display();
    }
    public function downloadadminmoneylist(){//认款导出
        $data=D('Pay')->getMoneyListData(1);
        foreach ($data as $key => $value) {
            if($value['dhxmoney']==0)$data[$key]['kxzt']='已核销';
            else $data[$key]['kxzt']='未核销';
        }
        $list=array(array('id','编号'),array('adddate','到款日期'),array('remarks2','摘要'),array('paymentname','付款方名称'),array('money','到账金额'),array('receivablesname','收款账户'),array('remarks','备注'),array('yrkmoney','已认款金额'),array('wrkmoney','待认款金额'),array('status','状态'));
        $this->downloadlist($data,$list,'认款列表');
    }
    public function mondyOkApi(){//认款数据接口
    	$data=D('Pay')->getonedata("id=".I('post.id'));

    	echo json_encode($data);
    }
    public function getOneAdverDataInfoApi(){//获取广告主相关信息接口
    	$data=D('SettlementIn')->getdata("advid=".I('post.id'));
    	echo json_encode($data);
    }
    public function getSettlementInListApi(){//根据广告主ID取应收列表接口
    	$adverid=(int)I('post.adverid');
    	$data=D('SettlementIn')->getlistForAdverid($adverid,"&& (a.status=3 || a.status=2)");
    	echo json_encode($data);
    }
    public function changedata($payinfo,$advinfo,$money){//修改用友广告主
        $token     = $this->token();
        $url       = C("AUDIT_API_HTTP").C("AUDIT_API_URL.adjustAccount_Url").$token;
        $data['dDate']=$payinfo['adddate'];
        $data['BankCode']=$payinfo['id'];
        if($payinfo['receivablesname']=='上海趣比'){
            $data['BankAccID'] = '上海趣比9710';
            $data['AccID'] = '022';
        }elseif($payinfo['receivablesname']=='重庆趣玩'){
            $data['BankAccID'] = '重庆趣玩8284';
            $data['AccID'] = '020';
        }elseif($payinfo['receivablesname']=='上饶网聚'){
            $data['BankAccID'] = '上饶网聚农行6796';
            $data['AccID'] = '026';
        }
        $data['CusCode']=$advinfo['ad_code'];
        $data['CusName']=$advinfo['name'];
        $data['TCusName']=$payinfo['paymentname'];
        $data['Money']=$money;
        $postArray = array("fileName"=>json_encode($data));
        if(I('get.xq')=='gasidegg'){
            var_dump($postArray);
            exit();
            $skRes=json_decode(bossPostData($url,$postArray),true);
            if($skRes['message'] =="success"){
                return true;
            }else{
                return false;
            }
        }
    }
    public function RKmoneyOkDo(){//确认认款
    	if(I('post.actiontype')==1){//结算款
    		if(!empty($_FILES['file']['tmp_name'])){
    			$info=$this->uplaodfile('file',UPLOAD_BASIS_IMG_PATH);
		        if(!is_array($info)){
                    $this->assign('data','上传依据失败');
                    $this->display('Public/alertpage');
                    return;
                }
		        $file_name=UPLOAD_BASIS_IMG_PATH.$info['file']['savepath'].$info['file']['savename'];
		    }
	        $id=(int)I('post.id');
	    	
	    	$num=0;
	    	while (1) {
	    		$bool=true;
	    		$allrkmoney=0;
                $payinfo=D('Pay')->getonedata("id=$id");
                
	    		M()->startTrans();
		    	foreach (I('post.financeid') as $key => $value) {
		    		$olddata=D('SettlementIn')->getonedata("id=".$value);
                    $res_advname=M('advertiser')->where("id=".$olddata['advid'])->find();
                    if($res_advname['name']!=$payinfo['paymentname']){
                        $this->changedata($payinfo,$res_advname,I('post.Financemoney'.$value));
                    }
                    $this_wfkmoney=$olddata['settlementmoney']-$olddata['yskmoney']-I('post.Financemoney'.$value);//待认款
                    if($this_wfkmoney*$this_wfkmoney<25){
                        //此单已结清
                        $alldataid=D('Daydata')->editdataforcom($olddata['advid'],$olddata['salerid'],$olddata['lineid'],$olddata['strdate'],$olddata['enddate'],$olddata['allcomid'],$olddata['alljfid']);
                        $id_arr=array();
                        foreach ($alldataid as $key => $val) {
                            $id_arr[]=$val['id'];
                        }
                        $id_str=implode(',',$id_arr);
                        if($olddata['status']==3){
                            $res1=D('Daydata')->edit("id in ($id_str)",array('status'=>5));
                            M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>5));
                            $result=D('Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
                            $res=D('SettlementIn')->edit("id=".$value,array('yskmoney'=>$olddata['settlementmoney'],'status'=>4,'nowskmoneytime'=>date('Y-m-d H:i:s')));
                            foreach ($id_arr as $k => $v) {
                                $logid=D('DaydataLog')->adddata(array('dataid'=>$v,'remark'=>'认款完成 '.I('get.id').',原因：'.I('get.yy'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
                            }
                        }elseif($olddata['status']==2){
                            $res1=D('Daydata')->edit("id in ($id_str)",array('status'=>8));
                            M('daydata_inandout')->where("in_id in ($id_str)")->save(array('in_status'=>8));
                            $result=D('Daydata')->postStatustoFenfafroid($id_str);//向分发同步收入状态
                            $res=D('SettlementIn')->edit("id=".$value,array('yskmoney'=>$olddata['settlementmoney'],'status'=>5,'nowskmoneytime'=>date('Y-m-d H:i:s')));
                            foreach ($id_arr as $k => $v) {
                                $logid=D('DaydataLog')->adddata(array('dataid'=>$v,'remark'=>'认款完成 '.I('get.id').',原因：'.I('get.yy'),'datatype'=>1,'addtime'=>date('Y-m-d H:i:s'),'username'=>$_SESSION['userinfo']['username']));
                            }
                        }
                        
                    }else{
                        $res=D('SettlementIn')->edit("id=".$value,array('yskmoney'=>$olddata['yskmoney']+I('post.Financemoney'.$value)));
                    }
		    		$rkid=D('Rkrecord')->adddata(array('rkerid'=>$_SESSION['userinfo']['uid'],'type'=>1,'payid'=>$id,'money'=>I('post.Financemoney'.$value),'time'=>date('Y-m-d H:i:s'),'skjsdid'=>$value));
		    		$allrkmoney+=I('post.Financemoney'.$value);
		    		if(!$res){
		    			M()->rollback(); 
		    			$num++;
		    			if($num>=2){
                            $this->assign('data','操作失败');
                            $this->display('Public/alertpage');
                            return;
		    				$bool=false;
		    			}
		    			break;
		    		}
		    	}

		    	if(!$bool)continue;
                
		    	if(!empty($payinfo['imgpath']))$imgpatharr=explode('$$',$payinfo['imgpath']);
                $imgpatharr[]=$file_name;
                if(twonum($payinfo['yrkmoney']+$allrkmoney)>$payinfo['money']){
                    M()->rollback();
                    $num++;
                    if($num>=2){
                        $this->assign('data','金额不足，是否重复认款？');
                        $this->display('Public/alertpage');
                        return;
                    }
                     break;
                }
		    	$payinfo2=array('yrkmoney'=>twonum($payinfo['yrkmoney']+$allrkmoney),'wrkmoney'=>twonum($payinfo['money']-$payinfo['yrkmoney']-$allrkmoney),'imgpath'=>implode('$$',$imgpatharr));
		    	if($payinfo2['wrkmoney']==0)$payinfo2['status']=2;
		    	$res=D('Pay')->edit("id=$id",$payinfo2);
		    	if(!$res){
		    		M()->rollback();
		    		$num++;
	    			if($num>=2){
	    				$this->assign('data','操作失败');
                        $this->display('Public/alertpage');
                        return;
	    			}
                    break;
		    	}else{
		    		M()->commit();
                    $this->assign('data','操作成功');
                    $this->display('Public/alertpage');
                    return;
		    		break;
		    	}
	    	}
    	}else{//预付款
    		if(!empty($_FILES['file']['tmp_name'])){
    			$info=$this->uplaodfile('file',UPLOAD_BASIS_IMG_PATH);
		        if(!is_array($info))$this->error('上传失败');
		        $file_name=UPLOAD_BASIS_IMG_PATH.$info['file']['savepath'].$info['file']['savename'];
    		}
	        $id=(int)I('post.id');
	        $advid=(int)I('post.advid');
	    	$beforepayinfoall=D('BeforepayGgzAll')->getonedata("adverid=$advid");
	    	if(!$beforepayinfoall){
	    		$r=D('BeforepayGgzAll')->adddata(array('adverid'=>$advid));
                $beforepayinfoall=array('allmoney'=>0,'dhxmoney'=>0);
	    	}

	    	$payinfo=D('Pay')->getonedata("id=$id");
            $res_advname=M('advertiser')->where("id=".$advid)->find();
            if($res_advname['name']!=$payinfo['paymentname']){
                $this->changedata($payinfo,$res_advname);
            }
	    	$money=I('post.yfmoney');
	    	$num=0;
	    	while (1) {
	    		M()->startTrans();
	    		$res=D('BeforepayGgzAll')->edit("adverid=$advid",array('allmoney'=>$beforepayinfoall['allmoney']+$money,'dhxmoney'=>$beforepayinfoall['dhxmoney']+$money));
	    		$res1=D('BeforepayGgz')->adddata(array('adverid'=>$advid,'rkdate'=>date('Y-m-d H:i:s'),'operator'=>$_SESSION['userinfo']['username'],'payid'=>$id));
	    		$rkid=D('Rkrecord')->adddata(array('rkerid'=>$_SESSION['userinfo']['uid'],'type'=>2,'payid'=>$id,'money'=>$money,'time'=>date('Y-m-d H:i:s'),'lineid'=>I('post.lineid'),'remark'=>I('post.is_remark'),'skjsdid'=>$advid));
	    		$payinfo2=array('yrkmoney'=>$payinfo['yrkmoney']+$money,'wrkmoney'=>$payinfo['money']-$payinfo['yrkmoney']-$money,'imgpath'=>$file_name);
		    	if($payinfo2['wrkmoney']==0)$payinfo2['status']=2;
		    	$res2=D('Pay')->edit("id=$id",$payinfo2);
	    		if($res && $res1 && $res2){
	    			M()->commit();
                    $this->assign('data','操作成功');
                    $this->display('Public/alertpage');
                    return;
	    			break;
	    		}else{
	    			M()->rollback();
	    			$num++;
	    			if($num>=2){
                        $this->assign('data','操作失败');
                        $this->display('Public/alertpage');
                        return;
	    			}
	    			break;
	    		}
	    	}
    	}
    }
    public function paydetail_r(){
    	$data=M('pay')->where("id=".I('get.id'))->find();
    	$this->assign('data',$data);
    	$jldata=M('rkrecord')->field('a.*,b.*,c.name as advername,e.name as jszt,f.real_name as salername,g.real_name as rkname,h.name as linename,b.allcomid')->join('a join boss_settlement_in b on a.skjsdid=b.id join boss_advertiser c on b.advid=c.id join boss_product d on b.comid=d.id join boss_data_dic e on b.jsztid=e.id join boss_user f on b.salerid=f.id join boss_user g on a.rkerid=g.id join boss_business_line h on b.lineid=h.id')->where("a.type=1 && a.payid=".I('get.id'))->select();
        foreach ($jldata as $k => $v) {
            $r=M('product')->field('group_concat(name) as name')->where("id in (".$v['allcomid'].")")->find();
            $jldata[$k]['comname']=$r['name'];
        }
        $jldata2=M('rkrecord')->field('a.money,c.name as advername,g.real_name as rkname')->join('a join boss_advertiser c on a.skjsdid=c.id   join boss_user g on a.rkerid=g.id')->where("a.type=2 && a.payid=".I('get.id'))->select();
        foreach ($jldata2 as $key => $value) {
            $jldata[]=$value;
        }
    	$this->assign('jldata',$jldata);
    	foreach ($jldata as $key => $value) {
    		$this->alldhk+=$value['settlementmoney'];
    		$this->allrk+=$value['money'];
    	}
    	$this->display();
    }
    public function paydetail(){//认款详情
    	$this->display();
    }
    public function addMoneyForExcel(){//导入流水
    	$info=$this->uplaodfile('file',UPLOAD_INMONEY_EXCEL_PATH);

        if(!is_array($info)){
            $this->assign('data','上传文件失败');
            $this->display('Public/alertpage');
            return;
        }
    	$file_name=UPLOAD_INMONEY_EXCEL_PATH.$info['file']['savepath'].$info['file']['savename'];
        if(substr($info['file']['savename'],-4)=='xlsx')$exceltype='Excel2007';
        else $exceltype='Excel5';
        $data=$this->exceltoarray($file_name,$exceltype);
        $keyvaluearray=array('日期'=>'adddate','摘要'=>'remarks2','付款方名称'=>'paymentname','金额'=>'money','备注'=>'remarks','收款账户'=>'receivablesname','开户行'=>'opening_bank','银行账号'=>'bank_no');
        $jfid=$data[1]['计费标识ID'];
        $beginnum=D('Pay')->getnum();
        M()->startTrans();
        foreach ($data as $key => $value) {
            $newdata=array();
            $outdata=array();
            foreach($value as $k => $v){
            	if($keyvaluearray[$k]=='adddate'){
            		$date=$v>25568?$v:25569;
			        /*There was a bug if Converting date before 1-1-1970 (tstamp 0)*/
			        $ofs=(70 * 365 + 17+2) * 86400;
			        $v = date("Y-m-d",($date * 86400) - $ofs).($time ? " 00:00:00" : '');
            	}
                $newdata['money']=twonum($newdata['money']);
                $newdata[$keyvaluearray[$k]]=trim($v);
                $newdata['wrkmoney']=twonum($newdata['money']);
                $newdata['status']=1;
                $newdata['ischeck']=0;
            }
            if((float)$newdata['money']<=0){
                $beginnum--;
                continue;
            }
            $id=D('Pay')->adddata($newdata);

        }
        $endnum=D('Pay')->getnum();
        if($endnum-$beginnum==count($data)){
            M()->commit();
            $this->assign('data','导入成功');
            $this->display('Public/alertpage');
        }else{
            M()->rollback(); 
            $this->assign('data',$beginnum.'->'.$endnum.'导入失败');
            $this->display('Public/alertpage');
            exit();
        }
    }

    public function payment(){//应付管理
		$data = $this->lists('Handle');
    	//$data=D('SettlementOut')->getFinanceListData();
		//收款账户
		$sbModel = M('data_dic');
		$sbData = $sbModel->field('name')->where("dic_type=4")->select();

        $sum_data = D('Handle')->getSum();
        if(I('get.strtime')){
            $this->assign('strtime',I('get.strtime'));
        }else{
            $this->assign('strtime',date('Y-m'));
        }
    	$this->assign('data',$data);
    	$this->assign('sbData',$sbData);
    	$this->assign('getSum',$sum_data);
    	$this->display();
    }
    public function paymentDetail(){//应付详情
		$rid = $_GET['id'];
		$sp_uid = $_GET['sp_uid'];

		$detailModel = M('flow_data_432');
		$detailData = $detailModel->where("id=".$rid."")->select();

		$this->assign('detailData',$detailData);

		$data_150 = "";//结算单ID(字符串)
		foreach($detailData as $val){
			$data_150 = $val['data_150'];
		}
		$data_150 = rtrim($data_150, ",");

		if(!empty($data_150)) {
			$outModel = M('settlement_out');
			$ourData = $outModel->field("strdate,enddate,alljfid")->where("id in (" . $data_150 . ")")->select();//应付产品明细
			$strdate = "";
			$enddate = "";
			$alljfid = "";
			foreach ($ourData as $our_val) {
				$strdate = $our_val['strdate'];
				$enddate = $our_val['enddate'];
				$alljfid .= $our_val['alljfid'] . ",";
			}
			$alljfid = rtrim($alljfid, ",");
			//echo $alljfid;exit;
			if(!empty($alljfid)) {
				$dayModel = M('daydata_out');
				$mxData = $dayModel->field("SUM(bdo.newmoney) AS moneysum,bp.`name` as bp_name,bcl.`name`")->join("AS bdo
JOIN boss_charging_logo AS bcl ON bdo.jfid=bcl.id
JOIN boss_product as bp ON bcl.prot_id=bp.id")->where("bdo.jfid IN (" . $alljfid . ") AND bdo.adddate>='" . $strdate . "' and bdo.adddate>='" . $enddate . "'")->group("bdo.jfid")->select();
				//echo $dayModel->getLastSql();exit;

				$this->assign('ourData', $mxData);
				$this->assign('strdate', $strdate);
				$this->assign('enddate', $enddate);
			}
		}
		$moreModel = M('flow_data_432_detail');
		$moreData = $moreModel->field("de.*,bu.real_name")->join("as de left join boss_user as bu on bu.id=de.uid ")->where("rid=".$rid." ")->select();
		$this->assign('moreData',$moreData);
		//print_r($moreData);exit;
		$this->display();
    }

	public function actually_pay(){//应付管理中实付金额修改
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

            //付款(应付管理 已付清)
            $fk_f = '/payMent/insertPayment';
            $fkModel = M('oa_66');
            $fk_url = $http_r.$gcm.$fk_f.'?token='.$token;
            $fkData = $fkModel->field("DATE_FORMAT(b.overtime,'%Y-%m-%d') as ddate,bf.x739c8a_13 as BillCode,sup.`code` as cVenCode,sup.`name` as cVenName,b_code.`code` AS AccID,fin.financial_tax as TaxRate,bf.x739c8a_9 as Money,bf.x739c8a_1 as OaCode,bf.id,bf.pay_money")->join('AS bf
        LEFT JOIN boss_oa_tixing b ON bf.x739c8a_1=b.liuchenid AND b.jiedianid=791
        JOIN boss_oa_liuchen c on c.liuchenid=bf.x739c8a_1 AND c.`status`=2
        LEFT JOIN boss_settlement_out AS bs ON bs.id = left(bf.x739c8a_13,char_length(bf.x739c8a_13)-1)
        LEFT JOIN boss_supplier AS sup ON sup.id=bs.addresserid
        LEFT JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid
        LEFT JOIN boss_supplier_finance AS fin ON fin.sp_id=bs.superid AND fin.`status`=1 AND bs.lineid=fin.bl_id')->where("yy_status=0 and bf.id in (".$sid.") and bf.x739c8a_9>0")->order('bf.id desc')->limit(0,50)->select();

            $aid = "";
            foreach($fkData as $key=>$val){

                $fkData[$key]['dDate'] = $val['ddate'];
                $fkData[$key]['BillCode'] = $val['billcode'];
                $fkData[$key]['cVenCode'] = $val['cvencode'];
                $fkData[$key]['cVenName'] = $val['cvenname'];
                $fkData[$key]['AccID'] = $val['accid'];
                $fkData[$key]['TaxRate'] = $val['taxrate'];
                $fkData[$key]['OaCode'] = $val['oacode'];

                foreach ($outdata as $val2) {

                    if($val['id'] == $val2[0]) {//id相同

                        if($val2[1]<$val['money']){
                            $fkData[$key]['money'] = $val2[1];
                        }else{
                            $fkData[$key]['money'] = $val['money'];
                            $aid .=$val['id'].",";
                        }
                        $data['pay_money'] = $val['pay_money'] + $val2[1];
                        $fkModel->where("id=".$val['id'])->save($data);
                    }
                    if( ($val2[1] == $val['money']) or ( ($val['money'] - $data['pay_money']) ==0)){//
                        $aid .=$val['id'].",";
                    }
                    //添加到明细表中
                    $detailModel = M('flow_data_432_detail');
                    $map = array();
                    $map['rid']= $val2[0];
                    $map['pay_money'] = $val2[1];
                    $map['pay_num'] = $val2[2];
                    $map['pay_date'] = $val2[3];
                    $map['uid'] = UID;
                    $map['addtime'] = date('y-m-d H:i:s',time());;
                    $detailModel->add($map);
                }
            }
            $aid = rtrim($aid, ",");
            $fkData = json_encode($fkData);
            $fk_res = bossPostData_json($fk_url,$fkData);
            $fkRes = json_decode($fk_res,true);
            $res = $fkRes['message'];
            if($res == "success"){
                $change = array();
                $change['id'] = array('in',$aid);
                $change['yy_status'] = 1;
                $fkModel->save($change);
                $this->ajaxReturn("TRUE");exit;
            }else{
                echo $res."-成本付款数据同步到用友系统失败";
            }

        }

    }

	/*public function actually_num(){//应付管理中支付银行账户修改
		$rid = $_POST['rid'];
		$pay_num = $_POST['pay_num'];
		$actuallyModel = M('flow_data_432');
		$map = array();
		$map['id']= $rid;
		$map['pay_num'] = $pay_num;
		if ($actuallyModel->save($map) === false) {
			$this->ajaxReturn($actuallyModel->getError());
		}else{
			$this->ajaxReturn("TRUE");
		}
	}*/

    public function downloadpaymentlist(){//应付导出

        $data=D('Handle')->getListData();

        foreach ($data as $key => $value) {
			if($value['data_30'] =='已支付'){

				$data[$key]['status'] = '已审批';

			}elseif($value['data_30'] =='不予支付'){

				$data[$key]['status'] = '已退回';
			}else{
				$data[$key]['status'] = '审批中';
			}

			$data[$key]['djs'] =$value['data_22'] - $value['pay_money'];
			if($value['data_22'] - $value['pay_money'] == 0){
				$data[$key]['js_status'] ='已结清';//结算状态
			}else{
				$data[$key]['js_status'] ='未结清';
			}

        }
        $list=array(array('x739c8a_1','OA支付流水号'),array('x739c8a_13','结算单编号'),array('x739c8a_3','供应商名称'),array('x739c8a_14','产品名称'),array('x739c8a_16','账单期间'),array('x739c8a_9','结算金额（含税）'),array('x739c8a_9','同步金额'),array('x739c8a_0','结算主体'),array('pay_date','支付时间'),array('adduser','申请人'),array('addtime','申请时间'));

        $this->downloadlist($data,$list,'应付管理列表'.date('Y-m-d',time()));
    }
    public function applyFinance(){//申请结算
    	$id=I('post.id');
    	$res=D('SettlementOut')->edit("id in ($id)",array('status'=>3));
    	if($res){
    		$this->success('进入结算');
    	}else{
    		$this->error('提交失败');
    	}
    }
    public function beforepayment(){//预付管理

		$data = $this->lists('Advance');
		//根据供应商id查询供应商已核销的金额
		$data_40 = "";
		$data_21 = "";
		$data_433 = array();
		foreach($data as $val){
			$data_40 .=$val['data_40'].",";
			$data_21 .="'".$val['data_21']."',";
			$data_433[] = M()->query("select data_54,data_60,sum(data_48) AS data_48 from boss_flow_data_433 where data_60 IN (".$val['data_40'].") and data_54 in ('".$val['data_21']."') and data_30='已备案' group by data_60");

		}
		$data_40 = rtrim($data_40, ",");
		$data_21 = rtrim($data_21, ",");

		if(!empty($val['data_40'])){
			$data_433 = M()->query("select data_54,data_60,sum(data_48) AS data_48 from boss_flow_data_433 where data_60 IN (".$data_40.") and data_54 in (".$data_21.") and data_30='已备案' group by data_60");
		}

		$sbModel = M('data_dic');
		$sbData = $sbModel->field('name')->where("dic_type=4")->select();
		//print_r($data_433);exit;
		$this->assign('sbData',$sbData);//结算主体
		$this->assign('data_433',$data_433);
		$this->assign('data',$data);
    	$this->display();
    }

	public function beforepayment_look(){//预付管理详情

		$rid = $_GET['rid'];//流程id
		$sup_id = $_GET['sup_id'];//供应商id
		$name = $_GET['name'];
		$yh = $_GET['yh'];
		$yy = $_GET['yy'];
		$dh = $_GET['dh'];
		$zy = $_GET['zy'];
		$plname = $_GET['plname'];//业务线名称

		//查询台账支付明细
		$payModel = M('flow_data_432_detail');
		$payData = $payModel->field("a.*,g.real_name")->join('a left join boss_user g on a.uid=g.id')->where("a.rid=".$rid."")->select();

		$hxModel = M('flow_data_433');
		$prefix = C('DB_PREFIX')."supplier";
		$hxData = $hxModel->field('hx.run_id,sup.name,hx.data_151,hx.data_152,hx.data_154,hx.data_155,hx.DATA_153,hx.data_1,hx.data_3,hx.data_48')->join("as hx left join {$prefix} as sup on sup.id=hx.data_60")->where("hx.data_60=$sup_id and hx.data_54='".$plname."' AND hx.data_30='已备案'")->select();
		//echo $hxModel->getLastSql();exit;

		$this->assign('name',$name);
		$this->assign('yh',$yh);
		$this->assign('yy',$yy);
		$this->assign('dh',$dh);
		$this->assign('zy',$zy);
		$this->assign('payData',$payData);
		$this->assign('hxData',$hxData);
		$this->display();
	}

	function  beforePaymentExport(){//预付管理导出

		$data = D('Advance')->getDataList();

		foreach ($data as $key => $value) {

			$data_433 = M()->query("select data_54,data_60,sum(data_48) AS data_48 from boss_flow_data_433 where data_60 IN (".$value['data_40'].") and data_54 in ('".$value['data_21']."') and data_30='已备案' group by data_60");
			foreach($data_433 as $key2=>$value2){
				$data[$key]['yhx'] = $value2['data_48'];
				$data[$key]['dhx'] = $value['data_22'] - $value2['data_48'];
				if($value['data_22'] - $value2['data_48'] == 0){
					$data[$key]['status'] = '已核销';
				}else{
					$data[$key]['status'] = '待核销';
				}
			}

		}
		//print_r($data);exit;
		$list=array(array('run_id','支付流水号'),array('name','供应商名称'),array('data_22','预付金额'),array('yhx','已核销金额'),array('dhx','待核销金额'),array('data_21','业务线'),array('data_1','申请人'),array('data_3','申请日期'),array('data_19','支付日期'),array('status','状态'));

		$this->downloadlist($data,$list,'预付管理列表'.date('Y-m-d',time()));
	}

	public function before_pay(){//预付管理中实付金额修改
		$rid = $_POST['rid'];
		$pay_money = $_POST['pay_money'];
		$pay_num = $_POST['pay_num'];
		$pay_date= $_POST['pay_date'];
		$uid= UID;
		$date_time = date('y-m-d H:i:s',time());

		//插入到明细表中
		$detailModel = M('flow_data_432_detail');
		$map = array();
		$map['rid']= $rid;
		$map['pay_money'] = $pay_money;
		$map['pay_num'] = $pay_num;
		$map['pay_date'] = $pay_date;
		$map['uid'] = $uid;
		$map['addtime'] = $date_time;
		if ($detailModel->add($map) === false) {
			$this->ajaxReturn($detailModel->getError());
		}else {
			//$this->ajaxReturn("上传成功");

			$actuallyModel = M('flow_data_432');
			$pmData = $actuallyModel->field("data_22,pay_money")->where("id=" . $rid . " ")->select();
			$out_pm = "";
			$data_22 = "";
			foreach ($pmData as $val) {
				$out_pm = $val['pay_money'];
				$data_22 = $val['data_22'];
			}
			$map = array();
			$map['id'] = $rid;
			$map['pay_num'] = $pay_num;
			$map['pay_date'] = $pay_date;
			$map['pay_money'] = $pay_money + $out_pm;
			if ($data_22 < $pay_money + $out_pm) {

				$this->ajaxReturn("实付金额不能大于预付金额");
				exit;
			} else {

				if ($actuallyModel->save($map) === false) {
					$this->ajaxReturn($actuallyModel->getError());
				} else {
					$this->ajaxReturn("TRUE");
				}
			}
		}
	}

    public function badfinance(){//坏账管理
		$data = $this->lists('BadFinance');
		$this->assign('data',$data);
    	$this->display();
    }

	public function  badExport(){//坏账导出

		$data = D('BadFinance')->getBadList();

		foreach ($data as $key => $value) {
			if(empty($value['data_50'])){
				$data[$key]['status']='未办理';
			}else{
				$data[$key]['status']=$value['data_50'];
			}

		}
		$list=array(array('run_id','编号'),array('data_6','客户名称'),array('data_38','客户类型'),array('data_40','坏账金额'),array('data_1','申请人'),array('data_151','所属销售/商务'),array('data_3','申请时间'),array('data_21','业务线'),array('status','状态'));
		$this->downloadlist($data,$list,'坏账管理列表');

	}

    public function getadverlist(){//查找广告主
        if(!empty(I('post.name')))$wheres[]="name like '%".I('post.name')."%'";
        if(!empty(I('post.code')))$wheres[]="ad_code like '%".I('post.code')."%'";
    	$res=M('Advertiser')->field('id,name,ad_code as code,address')->where(implode(' && ',$wheres))->select();
    	echo json_encode($res);
    }
    public function getcomlist(){//查找产品
        if(!empty(I('post.name')))$wheres[]="name like '%".I('post.name')."%'";
        if(!empty(I('post.code')))$wheres[]="code like '%".I('post.code')."%'";
        $res=M('Product')->field('id,name,code,"" as address')->where(implode(' && ',$wheres))->select();
        echo json_encode($res);
    }
    public function getsalerlist(){//查找销售
        $res=M('User')->field('id,real_name as name,"" as code,"" as address')->where("real_name like '%".I('post.name')."%'")->select();
        echo json_encode($res);
    }
    public function getlinelist(){//查找业务线
        if(!empty(I('post.name')))$wheres[]="name like '%".I('post.name')."%'";
        if(!empty(I('post.code')))$wheres[]="bl_code like '%".I('post.code')."%'";
        $res=M('business_line')->field('id,name,bl_code as code,"" as address')->where(implode(' && ',$wheres))->select();
        echo json_encode($res);
    }
    public function getjsztlist(){//查找结算主体
        if(!empty(I('post.name')))$wheres[]="name like '%".I('post.name')."%'";
        if(!empty(I('post.code')))$wheres[]="code like '%".I('post.code')."%'";
        $wheres[]="dic_type=4";
        $res=M('data_dic')->field('id,name,code,"" as address')->where(implode(' && ',$wheres))->select();
        echo json_encode($res);
    }

    /*2016.12.21
     * 同步收款信息到财务系统
     * */
    public function Rdata()
    {

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

            //收款数据（认款明细）
            $sk_url = '/rData/insertRdata';
            $sk_sj = $http_r.$gcm.$sk_url.'?token='.$token;
            $sk_data=D('Pay')->gettongbudata();
            foreach ($sk_data as $key => $value) {
                $id_arr[]=$value['id'];
                unset($sk_data[$key]['id']);
            }
            $sk_data = json_encode($sk_data);
            $sk_res = bossPostData_json($sk_sj,$sk_data);
            $skRes = json_decode($sk_res,true);
            if($skRes['message'] =="success"){
                M('Pay')->where("id in (".implode(',', $id_arr).")")->save(array('ischeck'=>1));
                $this->ajaxReturn("TRUE");exit;
            }else{
                $this->ajaxReturn("同步收款数据失败".$skRes['message']);exit;
            }

    }
    /*
        查询即将同步的流水信息
    */
    public function gettongbudata(){
        $sk_data=D('Pay')->gettongbudata();
        echo json_encode($sk_data);
    }

    /*预览发票信息 2017.03.16*/
    public function getFp(){
        $t_strtime = $_POST['rb_date'];
        if (!empty($t_strtime)) {
            $firstday = date('Y-m-01', strtotime($t_strtime));
            $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));

            $fpModel = M('settlement_in');
            $where = 'bs.`status` in (3,4)';
            $where .=" and bs.addtime like '%".$t_strtime."%' ";
            $fp_data = array();
            $fpData = $fpModel
                ->field('bs.enddate as dDate,bs.id as BillCode,bs.invoiceinfo,adv.ad_code as cuscode,adv.`name` as cusname,bl.id as itemcode,bl.`name` AS itemname,adv.invoice_type as taxrate,b_code.`code` AS accid,bs.settlementmoney as money,bs.comid,bs.strdate')                 ->join('AS bs JOIN boss_advertiser AS adv ON adv.id=bs.advid
JOIN boss_business_line AS bl ON bl.id=bs.lineid
JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid')
                ->where($where)
                ->order('bs.id desc')->select();
            foreach($fpData as $key => $value) {
                if ($value['taxrate'] == 1) {
                    $fp_data[$key]['taxrate'] = '0';
                } elseif ($value['taxrate'] == 2) {
                    $fp_data[$key]['taxrate'] = '0.03';
                } elseif ($value['taxrate'] == 3) {
                    $fp_data[$key]['taxrate'] = '0.06';
                } elseif ($value['taxrate'] == 4) {
                    $fp_data[$key]['taxrate'] = '0.09';
                } elseif ($value['taxrate'] == 5) {
                    $fp_data[$key]['taxrate'] = '0.17';
                }
                $invoiceinfo = json_decode($value['invoiceinfo'],true);
                $fp_code = "";
                foreach($invoiceinfo as $val){
                    $fp_code .= $val['code'].",";
                }
                $fp_code = rtrim($fp_code,",");
                $fp_data[$key]['BillNo'] = $fp_code;
                $fp_data[$key]['dDate'] = $value['ddate'];
                $fp_data[$key]['BillCode'] = $value['billcode'];
                $fp_data[$key]['CusCode'] = $value['cuscode'];
                $fp_data[$key]['CusName'] = $value['cusname'];
                $fp_data[$key]['ItemCode'] = $value['itemcode'];
                $fp_data[$key]['ItemName'] = $value['itemname'];
                $fp_data[$key]['AccID'] = $value['accid'];
                $fp_data[$key]['Money'] = $value['money'];
            }

            echo json_encode($fp_data);exit;
        }
    }

    /*2016.12.21
     * 同步发票收入到财务系统
     * */
    public function  RBillData()
    {
        $sid = $_POST['sid'];
        if ($sid) {
            $sid = implode(',',$sid);//结算单ID
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

            //发票收入
            $fp_f = '/rBillData/insertRBillData';
            $fpModel = M('settlement_in');
            //$where = " (bs.`status` IN (3,4) and bs.invoicetime like'%".$t_strtime."%' ) or (DATE_ADD(bs.addtime,INTERVAL 2 DAY) like'%".$t_strtime."%' and iskaipiao=2 )";//同步数据有2种状态(开票和不开票)
            $where ="bs.id in (".$sid.")";
            $fp_data = array();
            $fpData = $fpModel
                ->field(' DATE_ADD(bs.addtime,INTERVAL 2 DAY) as addtime,DATE_FORMAT(bs.invoicetime,"%Y-%m-%d") as dDate,bs.id as BillCode,bs.invoiceinfo,adv.ad_code as cuscode,adv.`name` as cusname,bl.id as itemcode,bl.`name` AS itemname,adv.invoice_type as taxrate,b_code.`code` AS accid,bs.settlementmoney as money,bs.comid,bs.strdate')->join('AS bs left JOIN boss_advertiser AS adv ON adv.id=bs.advid
left JOIN boss_business_line AS bl ON bl.id=bs.lineid
left JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid')
                ->where($where)
                ->order('bs.id desc')->select();

            $fp_url = $http_r.$gcm.$fp_f.'?token='.$token;
            foreach($fpData as $key => $value){
                if($value['taxrate'] == 1){
                    $fp_data[$key]['TaxRate'] ='0';
                }elseif($value['taxrate'] == 2){
                    $fp_data[$key]['TaxRate'] ='0.03';
                }elseif($value['taxrate'] == 3){
                    $fp_data[$key]['TaxRate'] ='0.06';
                }elseif($value['taxrate'] == 4){
                    $fp_data[$key]['TaxRate'] ='0.09';
                }elseif($value['taxrate'] == 5){
                    $fp_data[$key]['TaxRate'] ='0.17';
                }
                $invoiceinfo = json_decode($value['invoiceinfo'],true);
                $fp_code = "";
                $money = 0;
                foreach($invoiceinfo as $val){
                    $fp_code .= $val['code'].",";
                    $money = $val['money'];
                }
                $fp_code = rtrim($fp_code,",");
                $fp_data[$key]['BillNo'] = $fp_code;

                if($value['ddate']){
                    $fp_data[$key]['dDate'] = $value['ddate'];
                }else{
                    $fp_data[$key]['dDate'] = $value['addtime'];
                }

                $fp_data[$key]['BillCode'] = $value['billcode'];
                $fp_data[$key]['CusCode'] = $value['cuscode'];
                $fp_data[$key]['CusName'] = $value['cusname'];
                $fp_data[$key]['ItemCode'] = $value['itemcode'];
                $fp_data[$key]['ItemName'] = $value['itemname'];
                $fp_data[$key]['AccID'] = $value['accid'];
                $fp_data[$key]['Money'] = $value['money'];//$money 2017.08.18
                //结算单查询关账数据 通过产品ID和时间查询
                $closData = M('closing')->field('sum(bclo.in_newmoney) as inmoney,blc.id,blc.`name`,b_t.`code`,adv.ad_code as cuscode,adv.`name` as cusname,adv.invoice_type as taxrate')->join('AS bclo left JOIN boss_advertiser AS adv ON adv.id=bclo.in_adverid
left JOIN boss_business_line AS blc ON blc.id=bclo.in_lineid
left JOIN boss_data_dic AS b_t ON b_t.dic_type=4 and b_t.id=bclo.in_ztid')->where("bclo.in_comid=".$value['comid']." and adddate>='".$value['strdate']."' and adddate<='".$value['ddate']."'")->find();

                if($closData['taxrate'] == 1){
                    $fp_data[$key]['TTaxRate'] ='0';
                }elseif($closData['taxrate'] == 2){
                    $fp_data[$key]['TTaxRate'] ='0.03';
                }elseif($closData['taxrate'] == 3){
                    $fp_data[$key]['TTaxRate'] ='0.06';
                }elseif($closData['taxrate'] == 4){
                    $fp_data[$key]['TTaxRate'] ='0.09';
                }elseif($closData['taxrate'] == 5){
                    $fp_data[$key]['TTaxRate'] ='0.17';
                }else{
                    $fp_data[$key]['TTaxRate'] ='0';
                }
                if($closData['cuscode']){
                    $fp_data[$key]['TCusCode'] = $closData['cuscode'];
                }else{
                    $fp_data[$key]['TCusCode'] = '0';
                }
                if($closData['cusname']){
                    $fp_data[$key]['TCusName'] = $closData['cusname'];
                }else{
                    $fp_data[$key]['TCusName'] = '0';
                }
                if($closData['id']){
                    $fp_data[$key]['TItemCode'] = $closData['id'];
                }else{
                    $fp_data[$key]['TItemCode'] = '0';
                }
                if($closData['name']){
                    $fp_data[$key]['TItemName'] = $closData['name'];
                }else{
                    $fp_data[$key]['TItemName'] = '0';
                }
                if($closData['code']){
                    $fp_data[$key]['TAccID'] = $closData['code'];
                }else{
                    $fp_data[$key]['TAccID'] = '0';
                }
                if($closData['inmoney']){
                    $fp_data[$key]['TMoney'] = $closData['inmoney'];
                }else{
                    $fp_data[$key]['TMoney'] = '0';
                }
            }
            $fp_data = json_encode($fp_data);
            $fp_res = bossPostData_json($fp_url,$fp_data);
            $fpRes = json_decode($fp_res,true);
            //print_r($fpRes);exit;
            if($fpRes['message'] == "success"){

                $data['fp_status'] = 1;
                $fpModel->where("id in (".$sid.")")->save($data);

                $this->ajaxReturn("TRUE");exit;
            }else{
                $this->ajaxReturn($fpRes['message']." 同步发票收入失败");exit;
            }
        }
    }
    
    public function outpay(){
        //退款

        $thisdata=array();

        $pay_data=M('pay')->where("id=".I('get.id'))->find();
        if($pay_data['receivablesname']=='上饶网聚')$thisdata['x2a1540_0']='上饶市网聚天下科技有限公司';
        elseif ($pay_data['receivablesname']=='上海趣比') $thisdata['x2a1540_0']='上海趣比科技有限公司';
        else $thisdata['x2a1540_0']='重庆趣玩科技有限公司';
        $thisdata['x2a1540_1']=$_SESSION['userinfo']['realname'];
        $thisdata['x2a1540_2']=$_SESSION['userinfo']['depart_name'];
        $oaid=M('oa_oanum')->add(array('userid'=>$_SESSION['userinfo']['uid']));
        $thisdata['x2a1540_3']=$oaid;
        $thisdata['x2a1540_4']=$pay_data['adddate'];
        $thisdata['x2a1540_5']=$pay_data['receivablesname'];
        $thisdata['x2a1540_6']=$pay_data['money'];
        $adv_data=M('advertiser')->where("name='{$pay_data['paymentname']}'")->find();
        $thisdata['x2a1540_7']=0;
        $thisdata['x2a1540_8']=$pay_data['money'];
        $thisdata['x2a1540_10']=$adv_data['account_name'];
        $thisdata['x2a1540_12']=$adv_data['bank_no'];
        $thisdata['x2a1540_13']=$adv_data['opening_bank'];
        $thisdata['x2a1540_14']=I('get.id');
        $dataid=M('oa_63')->add($thisdata);
        $data=array();
        $data['alldata']=$dataid;//所有数据
        $data['adduser']=$_SESSION['userinfo']['uid'];
        $data['liuchenid']=$oaid;
        $data['status']=1;
        $data['addtime']=date('Y-m-d H:i:s');
        $data['nowsort']=0;
        $data['isok']=0;
        $res=M('oa_jiedian')->where("pid=63")->select();
        $alluser=array();
        foreach ($res as $key => $value) {
            $alluser[]=$value['userid'];
        }
        $data['alluser']=implode(',',$alluser);
        $data['mid']=63;
        $nowid=M('oa_liuchen')->add($data);
        $data['id']=$nowid;
        $nowsort=0;
        $this->actionlog('发起流程',$oaid,0);
        $nextid=$this->addtixing($oaid,$nowsort);
        M('oa_tixing')->where("id=$nextid")->save(array('userid'=>$_SESSION['userinfo']['uid']));
        $this->success('为你跳转到流程发起页面','/OA/Index/main.html?mainurl=/OA/Index/useing?lcid='.$oaid.'?_jdid=769?_txid='.$nextid);
    }
    public function outpayfrombe(){
        $thisdata=array();
        $befor_data=M('beforepay_ggz_all')->where("id=".I('get.id'))->find();
        $befor_info=M('beforepay_ggz')->where("adverid=".$befor_data['adverid'])->find();
        $pay_data=M('pay')->where("id=".$befor_info['payid'])->find();
        $adv_data=M('advertiser')->where("id=".$befor_data['adverid'])->find();
        if($pay_data['receivablesname']=='上饶网聚')$thisdata['x2a1540_0']='上饶市网聚天下科技有限公司';
        elseif ($pay_data['receivablesname']=='上海趣比') $thisdata['x2a1540_0']='上海趣比科技有限公司';
        else $thisdata['x2a1540_0']='重庆趣玩科技有限公司';
        $thisdata['x2a1540_1']=$_SESSION['userinfo']['realname'];
        $thisdata['x2a1540_2']=$_SESSION['userinfo']['depart_name'];
        $oaid=M('oa_oanum')->add(array('userid'=>$_SESSION['userinfo']['uid']));
        $thisdata['x2a1540_3']=$oaid;
        $thisdata['x2a1540_6']=$befor_data['allmoney'];
        $allmoney=$pay_data['yrkmoney'];
        $thisdata['x2a1540_7']=$befor_data['yhxmoney'];
        $thisdata['x2a1540_8']=$befor_data['dhxmoney'];
        $thisdata['x2a1540_10']=$adv_data['account_name'];
        $thisdata['x2a1540_12']=$adv_data['bank_no'];
        $thisdata['x2a1540_13']=$adv_data['opening_bank'];
        $thisdata['x2a1540_14']='adv'.$adv_data['id'];
        $dataid=M('oa_63')->add($thisdata);
        $data=array();
        $data['alldata']=$dataid;//所有数据
        $data['adduser']=$_SESSION['userinfo']['uid'];
        $data['liuchenid']=$oaid;
        $data['status']=1;
        $data['addtime']=date('Y-m-d H:i:s');
        $data['nowsort']=0;
        $data['isok']=0;
        $res=M('oa_jiedian')->where("pid=63")->select();
        $alluser=array();
        foreach ($res as $key => $value) {
            $alluser[]=$value['userid'];
        }
        $data['alluser']=implode(',',$alluser);
        $data['mid']=63;
        $nowid=M('oa_liuchen')->add($data);
        $data['id']=$nowid;
        $nowsort=0;
        $this->actionlog('发起流程',$oaid,0);
        $nextid=$this->addtixing($oaid,$nowsort);
        M('oa_tixing')->where("id=$nextid")->save(array('userid'=>$_SESSION['userinfo']['uid']));
        $this->success('为你跳转到流程发起页面','/OA/Index/main.html?mainurl=/OA/Index/useing?lcid='.$oaid.'?_jdid=769?_txid='.$nextid);
    }

    //2017.08.08 同步收入发票，财务要求可以选择、查询、导出等
    public function index(){
        $where = array();
        $this->assign('data_dic', M('data_dic')->field("id,code,name")->where("dic_type=4 and id in (1,2,3,4,5,6)")->select());
        $list = $this->lists($this, $where);
        $this->assign('data',$list);
        $this->display();
    }

    public function getList($where) {

        $fpModel = M('settlement_in');

        $where = "bs.`status` in (3,4,5) and bs.fp_status=0 and bs.invoiceinfo !=''";
        $t_strtime = $_GET['strtime'];

        if($t_strtime){
            $this->assign('strtime',$t_strtime);
            //$where .=" and bs.addtime like '%".$t_strtime."%' ";
            $where .=" and ((bs.invoicetime like'%".$t_strtime."%' ) or (DATE_ADD(bs.addtime,INTERVAL 2 DAY) like'%".$t_strtime."%' and iskaipiao=2 ))";//包含开票和不开票
        }else{

            //$where .=" and bs.addtime like '%".date('Y-m')."%' ";
            $where .=" and ((bs.invoicetime like'%".date('Y-m')."%' ) or (DATE_ADD(bs.addtime,INTERVAL 2 DAY) like'%".date('Y-m')."%' and iskaipiao=2 ))";//包含开票和不开票
            $this->assign('strtime',date('Y-m'));
        }
        $sid = $_GET['sid'];
        if($sid){
            $where .=" and bs.id ='".$sid."' ";
        }

        //公司账户
        $ggzname = I('get.ggzname', '');
        if($ggzname){
            $ggzname = implode(',',$ggzname);
            $where .= " and b_code.id in ($ggzname)";
        }

        $fp_data = $fpModel
            ->field('bs.enddate as dDate,bs.id as BillCode,bs.invoiceinfo,adv.ad_code as cuscode,adv.`name` as cusname,bl.id as itemcode,bl.`name` AS itemname,adv.invoice_type as taxrate,b_code.`code` AS accid,bs.settlementmoney as money,bs.comid,bs.strdate')                 ->join('AS bs JOIN boss_advertiser AS adv ON adv.id=bs.advid
JOIN boss_business_line AS bl ON bl.id=bs.lineid
JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid')
            ->where($where)
            ->page($_GET['p'],C('LIST_ROWS'))
            ->order('bs.id desc')->select();

        foreach($fp_data as $key => $value) {
            if ($value['taxrate'] == 1) {
                $fp_data[$key]['taxrate'] = '0';
            } elseif ($value['taxrate'] == 2) {
                $fp_data[$key]['taxrate'] = '0.03';
            } elseif ($value['taxrate'] == 3) {
                $fp_data[$key]['taxrate'] = '0.06';
            } elseif ($value['taxrate'] == 4) {
                $fp_data[$key]['taxrate'] = '0.09';
            } elseif ($value['taxrate'] == 5) {
                $fp_data[$key]['taxrate'] = '0.17';
            }
            $invoiceinfo = json_decode($value['invoiceinfo'],true);
            $fp_code = "";
            foreach($invoiceinfo as $val){
                $fp_code .= $val['code'].",";
            }

            $fp_code = rtrim($fp_code,",");
            $fp_data[$key]['billno'] = $fp_code;
            $fp_data[$key]['dDate'] = $value['ddate'];
            $fp_data[$key]['BillCode'] = $value['billcode'];
            $fp_data[$key]['CusCode'] = $value['cuscode'];
            $fp_data[$key]['CusName'] = $value['cusname'];
            $fp_data[$key]['ItemCode'] = $value['itemcode'];
            $fp_data[$key]['ItemName'] = $value['itemname'];
            $fp_data[$key]['AccID'] = $value['accid'];
            $fp_data[$key]['Money'] = $value['money'];
        }
        $subQuery = $fpModel
            ->field('bs.id')
            ->join('AS bs JOIN boss_advertiser AS adv ON adv.id=bs.advid
JOIN boss_business_line AS bl ON bl.id=bs.lineid
JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid')
            ->where($where)
            ->buildSql();
        $this->totalPage = $fpModel->table($subQuery.' aa')->where()->count();
        return $fp_data;
    }

    public function export(){
        $where = array();
        C('LIST_ROWS', ''); //不分页
        $data = $this->lists($this, $where);

        $list=array(array('billcode','单据编号'),array('billno','发票号'),array('cuscode','客户编码'),array('cusname','客户名称'),array('itemcode','业务线编码'),array('itemname','业务线名称'),array('taxrate','税率'),array('accid','U8帐套号'),array('money','金额'));
        $this->downloadlist($data,$list,'同步收入发票数据表');
    }
}