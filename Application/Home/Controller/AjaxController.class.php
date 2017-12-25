<?php
namespace Home\Controller;
use Think\Controller;
class AjaxController extends Controller {
    public function getConfig(){
    	$GLOBALS['islog']=1;
    	//print_r(C('OPTION.invoice_type_scales'));
	    //echo '<pre>';print_r($this->getMenus());
    	echo json_encode(C('OPTION'));
    }
    public function Getdatalog(){
    	//获取数据操作日志
        import("Org.Util.Hgmongodb");
        $mongo          = new \Hgmongodb(); 

        //------------查询
        $filter = [
             // "dataid" => I('post.id'),
                "dataid" => I('post.id'),
             "datatype" => (int)I('post.type'),
        ];
        $queryWriteOps = [
            "projection" => ["_id"   => 0,'dataid' =>1 , 'remark'=>1 , 'datatype'=>1,'addtime'=>1,'username'=>1,'olddata'=>1],
            "sort"       => ["id" => -1],
            "limit"      => 200,
        ];
        $mongo->connect();//连接mongodb，这是一个触发式的连接
        $params = array(
            "table"=>"bos_daydatalog",
            "limit"=>200,
            "db_table"=>"boss3_www.bos_daydatalog",
            "filter"=>$filter,
            "queryWriteOps"=>$queryWriteOps
            );
        $user = $mongo->select($params);
    	echo json_encode($user);
    }
    public function save_list_session(){
        //记录当前显示列
        $info=parse_url(I('post.url'));
        $path       =   explode('/',$info['path']);
        $arr=explode('.', $path[2]);
        $action=$arr[0];
        $str=$path[1].'/'.$action;
        $str=str_replace('amp;', '' , $str);
        $_SESSION['showtablestr'][$str]=array('url'=>$info['path'].'?'.$info['query'],'str'=>I('post.str'));
    }
    public function mutiQuery() {
	    $q = I('post.q','');
    	$type = I('post.type','');
	    $model = '';
	    $_field = 'id,name';
	    $where = '';
	    $ret = array();
	    switch ($type) {
		    case 'ad':
			    $model = 'advertiser';
			    !empty($q) && $where['name'] = array('like',"%$q%");
			    break;
		    case 'pro':
			    $model = 'product';
			    !empty($q) && $where['name'] = array('like',"%$q%");
			    break;
		    case 'bl':
			    $model = 'business_line';
			    !empty($q) && $where['name'] = array('like',"%$q%");
			    break;
		    case 'saler':
			    $ret = D('AuthGroup')->getGroupUser(4);
			    break;
		    case 'busman':
			    $ret = D('AuthGroup')->getGroupUser(6);
			    break;
		    case 'cl':
			    $model = 'charging_logo';
			    !empty($q) && $where['name'] = array('like',"%$q%");
			    break;
		    case 'sup':
			    $model = 'supplier';
			    !empty($q) && $where['name'] = array('like',"%$q%");
			    break;
		    default:
			    ;
			    break;
	    }

	    if (!empty($model)) {
		    $ret = M($model)->field($_field)->where($where)->limit(20)->select();
	    }
    	$this->ajaxReturn($ret);
    }
    public function getbumentichendata(){
    	$time=date('Y-m-',time()-3600*24*365).'01';

			$bumenarr=array();
			$bumendata=M('user_department')->field('sum(if(c.out_id is null,if(c.in_status is null || c.in_status = 0 || c.in_status = 9,0,c.in_newmoney),if(c.in_status is null || c.in_status = 0 || c.in_status = 9,0,c.in_newmoney)*(1-e.in_settlement_prate))) as money,a.id,a.name,left(c.adddate,7) as date')->join('a join boss_user b on a.id=b.dept_id join boss_daydata_inandout c on c.in_salerid=b.id join boss_charging_logo_assign e on e.cl_id=c.jfid && e.promotion_stime<=c.adddate && e.promotion_etime>=c.adddate')->where("c.adddate >= '$time' && a.id in (150,151,155)")->group('a.id,left(c.adddate,7)')->order('left(c.adddate,7)')->select();
			$bumendata2=M('user_department')->field('sum(if(f.in_status is null || f.in_status = 0 || f.in_status = 9,0,f.in_newmoney)*g.in_settlement_prate)-sum(if(f.out_status is null || f.out_status = 0 || f.out_status = 9,0,f.out_newmoney)) as money,a.id,a.name,left(f.adddate,7) as date')->join('a join boss_user b on a.id=b.dept_id join boss_daydata_inandout f on f.out_businessid=b.id join boss_charging_logo_assign g on g.cl_id=f.jfid && g.promotion_stime<=f.adddate && g.promotion_etime>=f.adddate')->where("f.adddate >= '$time' && a.id in (150,151,155)")->group('a.id,left(f.adddate,7)')->order('left(f.adddate,7)')->select();
			$allmoney_bumen=array();
			foreach ($bumendata as $key => $value) {
				if(empty($allmoney_bumen[$value['date']]))$allmoney_bumen[$value['date']]=array('all'=>0);
				$allmoney_bumen[$value['date']][$value['id']]=$value;
				$allmoney_bumen[$value['date']]['all']=$value['money']+$allmoney_bumen[$value['date']]['all'];
			}

			foreach ($bumendata2 as $key => $value) {
				if(empty($allmoney_bumen[$value['date']]))$allmoney_bumen[$value['date']]=array('all'=>0);
				if(!empty($allmoney_bumen[$value['date']][$value['id']])){
					$allmoney_bumen[$value['date']][$value['id']]['money']=$value['money']+$allmoney_bumen[$value['date']][$value['id']]['money'];
					$allmoney_bumen[$value['date']]['all']=$value['money']+$allmoney_bumen[$value['date']]['all'];
				}
				else {
					$allmoney_bumen[$value['date']][$value['id']]=$value;
					$allmoney_bumen[$value['date']]['all']=$value['money']+$allmoney_bumen[$value['date']]['all'];
				}
			}

			$allgxdata=array();
			$num=0;
			foreach ($allmoney_bumen as $k => $v) {
				foreach ($v as $key => $val) {
					if($key=='all')continue;
					$allgxdata[$val['name']][$num]=round(($val['money']/$v['all'])*10000)/100;
				}
				$num++;
			}
		echo json_encode($allgxdata);
    }
    /*可视化报表开始*/
    //总览
    public function getshowdataimgtable(){
    	$_GET['inandout']=1;
    	$_GET['strtime']=date("Y-m-01");
    	$_GET['endtime']=date("Y-m-d");
    	$res_thismonth=D("ChargingLogo")->getalldata(1);
    	$_GET['strtime']=date("Y-m-01",strtotime(date('Y').'-'.(date('m')-1).'-01'));
    	$_GET['endtime']=date("Y-m-d",strtotime(date('Y').'-'.(date('m')-1).'-'.date('d')));
		if(substr($_GET['endtime'],0,7)!=substr($_GET['strtime'],0,7)){
			$_GET['endtime']=date('Y-m-d',strtotime($_GET['strtime']." +1 month -1 day"));
			//$res_lastmonthallday=$res_lastmonththisday=D("ChargingLogo")->getalldata(1);
            $res_lastmonthallday=D("ChargingLogo")->getalldata(1);
		}else{
			//$res_lastmonththisday=D("ChargingLogo")->getalldata(1);
			$_GET['endtime']=date('Y-m-d',strtotime($_GET['strtime']." +1 month -1 day"));
			$res_lastmonthallday=D("ChargingLogo")->getalldata(1);
		}
		$_GET['strtime']=date("Y-01-01");
		$_GET['endtime']=date("Y-m-d");
		$res_allyearday=D("ChargingLogo")->getalldata(1);
        //echo json_encode(array('thismonth'=>$res_thismonth,'lastmonthall'=>$res_lastmonthallday,'lastmonththis'=>$res_lastmonththisday,'allyear'=>$res_allyearday));
        echo json_encode(array('thismonth'=>$res_thismonth,'lastmonthall'=>$res_lastmonthallday,'allyear'=>$res_allyearday));
    }
    //收益占比
    public function getlinelv(){
    	$res=D('DaydataInandout')->getlinelv();
    	$res_line=M('business_line')->select();
    	$data=array();
    	foreach ($res_line as $key => $value) {
    		$data[$value['id']]=array('name'=>$value['name'],'indata'=>0,'outdata'=>0);
    		foreach ($res['in_no'] as $k => $v) {
    			if($v['in_lineid']==$value['id']){
    				$data[$value['id']]['indata']+=$v['indata'];
    				unset($res['in_no'][$k]);
    			}
    		}
    		foreach ($res['out_no'] as $k => $v) {
    			if($v['out_lineid']==$value['id']){
    				$data[$value['id']]['outdata']+=$v['outdata'];
    				unset($res['out_no'][$k]);
    			}
    		}
    		foreach ($res['in_yes'] as $k => $v) {
    			if($v['out_lineid']==$value['id']){
    				$data[$value['id']]['outdata']+=$v['outdata'];
    			}
    			if($v['in_lineid']==$value['id']){
    				$data[$value['id']]['indata']+=$v['indata'];
    			}
    		}
    	}
        $data_in=$data_out=array();
    	foreach ($data as $k => $v) {
            if($v['indata']!=0)$data_in[]=array('name'=>$v['name'],'value'=>twonum($v['indata']/10000));
            if($v['outdata']!=0 || $v['indata']!=0)$data_out[]=array('name'=>$v['name'],'value'=>twonum(($v['indata']-$v['outdata'])/10000));
        }
        foreach ($data_out as $k => $v) {
            if($v['value']<0){
                $data_out[$k]['value']=0;
                $re[]=$v['name'].'实际利润为'.$v['value'];
            }
        }
        echo json_encode(array('in'=>$data_in,'out'=>$data_out,'re'=>$re));
    }
    //收益趋势
    public function getqushidata(){
        $data=D('DaydataInandout')->getqushidata();
        foreach ($data as $k => $v) {
            $data[$k]['fit']=$v['indata']-$v['outdata'];
        }
        foreach ($data as $k => $v) {
            $date[]=$v['date'];
            $in[]=array('value'=>twonum($v['indata']/10000),'itemStyle'=>array('normal'=>array('label'=>array('show'=>true,'textStyle'=>array('fontWeight'=>'bold','fontSize'=>14)))));
            $out[]=array('value'=>twonum($v['outdata']/10000),'itemStyle'=>array('normal'=>array('label'=>array('show'=>true,'textStyle'=>array('fontWeight'=>'bold','fontSize'=>14)))));
            $fit[]=array('value'=>twonum($v['fit']/10000),'itemStyle'=>array('normal'=>array('label'=>array('show'=>true,'textStyle'=>array('fontWeight'=>'bold','fontSize'=>14)))));
        }
        echo json_encode(array('date'=>$date,'in'=>$in,'out'=>$out,'fit'=>$fit));
    }
    //广告主收入top10
    public function getadvintop10(){
        $data=D('DaydataInandout')->getadvintop10();
        $num=0;
        foreach ($data as $k => $v) {
            $num++;
            $html.="<tr>
                                    <td>$num</td>
                                    <td><a href='/Advertiser/detail/id/{$v['id']}.html' target='_blank'>{$v['name']}</a></td>
                                    <td>{$v['money']}</td>
                                </tr>";
        }
        echo json_encode(array('html'=>$html));
    }
    //广告主利润top10
    public function getadvlrtop10(){
        $data=D('DaydataInandout')->getadvlrtop10();
        $num=0;
        foreach ($data as $k => $v) {
            $num++;
            $html.="<tr>
                                    <td>$num</td>
                                    <td><a href='/Advertiser/detail/id/{$v['id']}.html' target='_blank'>{$v['name']}</a></td>
                                    <td>{$v['money']}</td>
                                </tr>";
        }
        echo json_encode(array('html'=>$html));
    }
    //广告主增长率top10
    public function getadvzztop10(){
        $data=D('DaydataInandout')->getadvzztop10();
        $num=0;
        foreach ($data as $k => $v) {
            $num++;
            $html.="<tr>
                                    <td>$num</td>
                                    <td><a href='/Advertiser/detail/id/{$v['id']}.html' target='_blank'>{$v['name']}</a></td>
                                    <td>{$v['data']}</td>
                                </tr>";
        }
        echo json_encode(array('html'=>$html));
    }
    //供应商成本top10
    public function getsupouttop10(){
        $data=D('DaydataInandout')->getsupouttop10();
        $num=0;
        foreach ($data as $k => $v) {
            $num++;
            $html.="<tr>
                                    <td>$num</td>
                                    <td><a href='/Supplier/detail/id/{$v['id']}.html' target='_blank'>{$v['name']}</a></td>
                                    <td>{$v['money']}</td>
                                </tr>";
        }
        echo json_encode(array('html'=>$html));
    }
    //供应商利润top10
    public function getsuplrtop10(){
        $data=D('DaydataInandout')->getsuplrtop10();
        $num=0;
        foreach ($data as $k => $v) {
            $num++;
            $html.="<tr>
                                    <td>$num</td>
                                    <td><a href='/Supplier/detail/id/{$v['id']}.html' target='_blank'>{$v['name']}</a></td>
                                    <td>{$v['money']}</td>
                                </tr>";
        }
        echo json_encode(array('html'=>$html));
    }
    //供应商增长率top10
    public function getsupzztop10(){
        $data=D('DaydataInandout')->getsupzztop10();
        $num=0;
        foreach ($data as $k => $v) {
            $num++;
            $html.="<tr>
                                    <td>$num</td>
                                    <td><a href='/Supplier/detail/id/{$v['id']}.html' target='_blank'>{$v['name']}</a></td>
                                    <td>{$v['data']}</td>
                                </tr>";
        }
        echo json_encode(array('html'=>$html));
    }
    
    /*可视化报表结束*/

    public function changeinc(){
        //修改发票信息
        $inc_data=M('invoice')->where("id=".I('get.id'))->find();
        M('invoice')->where("id=".I('get.id'))->save(array('invoice_no'=>I('get.code'),'money'=>I('get.money')));
        $set_data=M('settlement_in')->where("id=".$inc_data['income_st_id'])->find();
        $inc_js=json_decode($set_data['invoiceinfo'],true);
        foreach ($inc_js as $key => $value) {
            if($value['money']==$inc_data['money'] && $value['code']==$inc_data['invoice_no']){
                $arr[]=array('code'=>I('get.code'),'money'=>I('get.money'));

                //判断是否同步到用友 已同步则传负数 start
                if($set_data['fp_status'] == 1){

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
JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid')->where("bs.id=".$set_data['id']." ")->select();
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
                        $fp_data[$key]['BillNo'] = $value['code'];
                        $fp_data[$key]['dDate'] = $value['ddate'];
                        $fp_data[$key]['BillCode'] = $value['billcode'];
                        $fp_data[$key]['CusCode'] = $value['cuscode'];
                        $fp_data[$key]['CusName'] = $value['cusname'];
                        $fp_data[$key]['ItemCode'] = $value['itemcode'];
                        $fp_data[$key]['ItemName'] = $value['itemname'];
                        $fp_data[$key]['AccID'] = $value['accid'];
                        $fp_data[$key]['Money'] = '-'.$value['money'];
                        //结算单查询关账数据 通过产品ID和时间查询
                        $closData = M('closing')->field('sum(bclo.in_newmoney) as inmoney,blc.id,blc.`name`,b_t.`code`,adv.ad_code as cuscode,adv.`name` as cusname,adv.invoice_type as taxrate')->join('AS bclo JOIN boss_advertiser AS adv ON adv.id=bclo.in_adverid
JOIN boss_business_line AS blc ON blc.id=bclo.in_lineid
JOIN boss_data_dic AS b_t ON b_t.dic_type=4 and b_t.id=bclo.in_ztid')->where("bclo.in_comid=".$value['comid']." and adddate>='".$value['strdate']."' and adddate<='".$value['dDate']."'")->find();
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
                        }
                        $fp_data[$key]['TCusCode'] = $closData['cuscode'];
                        $fp_data[$key]['TCusName'] = $closData['cusname'];
                        $fp_data[$key]['TItemCode'] = $closData['id'];
                        $fp_data[$key]['TItemName'] = $closData['name'];
                        $fp_data[$key]['TAccID'] = $closData['code'];//冲销帐套号
                        $fp_data[$key]['TMoney'] = $closData['inmoney'];
                    }
                    $fp_data = json_encode($fp_data);
                    $fp_res = bossPostData_json($fp_url,$fp_data);
                    $fpRes = json_decode($fp_res,true);
                }
                //同步到用友end

            }else{
                $arr[]=$value;
            }
        }
        M('settlement_in')->where("id=".$inc_data['income_st_id'])->save(array('invoiceinfo'=>json_encode($arr)));

        //发票修改后重新同步一次数据到用友 start
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

        $fp_f = '/rBillData/insertRBillData';
        $fpModel = M('settlement_in');
        $fp_data = array();
        $fpData = $fpModel->field('bs.enddate as dDate,bs.id as BillCode,bs.invoiceinfo,adv.ad_code as cuscode,adv.`name` as cusname,bl.id as itemcode,bl.`name` AS itemname,adv.invoice_type as taxrate,b_code.`code` AS accid,bs.settlementmoney as money,bs.comid,bs.strdate')
            ->join('AS bs JOIN boss_advertiser AS adv ON adv.id=bs.advid
JOIN boss_business_line AS bl ON bl.id=bs.lineid
JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid')->where("bs.id='".$inc_data['income_st_id']."'")->select();
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

            $fp_data[$key]['BillNo'] = I('get.code');
            $fp_data[$key]['dDate'] = $value['ddate'];
            $fp_data[$key]['BillCode'] = $value['billcode'];
            $fp_data[$key]['CusCode'] = $value['cuscode'];
            $fp_data[$key]['CusName'] = $value['cusname'];
            $fp_data[$key]['ItemCode'] = $value['itemcode'];
            $fp_data[$key]['ItemName'] = $value['itemname'];
            $fp_data[$key]['AccID'] = $value['accid'];
            $fp_data[$key]['Money'] = I('get.money');
            //结算单查询关账数据 通过产品ID和时间查询
            $closData = M('closing')->field('sum(bclo.in_newmoney) as inmoney,blc.id,blc.`name`,b_t.`code`,adv.ad_code as cuscode,adv.`name` as cusname,adv.invoice_type as taxrate')->join('AS bclo JOIN boss_advertiser AS adv ON adv.id=bclo.in_adverid
JOIN boss_business_line AS blc ON blc.id=bclo.in_lineid
JOIN boss_data_dic AS b_t ON b_t.dic_type=4 and b_t.id=bclo.in_ztid')->where("bclo.in_comid=".$value['comid']." and adddate>='".$value['strdate']."' and adddate<='".$value['dDate']."'")->find();
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
            }
            $fp_data[$key]['TCusCode'] = $closData['cuscode'];
            $fp_data[$key]['TCusName'] = $closData['cusname'];
            $fp_data[$key]['TItemCode'] = $closData['id'];
            $fp_data[$key]['TItemName'] = $closData['name'];
            $fp_data[$key]['TAccID'] = $closData['code'];//冲销帐套号
            $fp_data[$key]['TMoney'] = $closData['inmoney'];
        }
        $fp_data = json_encode($fp_data);
        $fp_res = bossPostData_json($fp_url,$fp_data);
        $fpRes = json_decode($fp_res,true);
        //修改后数据(发票)同步到用友end

        echo '1';
    }
    public function changequerylink(){
        //编辑快捷功能
        $addid=I('post.addid',0);//添加的ID
        $delid=I('post.delid',0);//删除的ID
        $data_user=M('user')->where("id=".$_SESSION['userinfo']['uid'])->find();
        if($data_user['bos_link']!='')$arr_link=explode(',', $data_user['bos_link']);
        else $arr_link=array();
        if($addid){
            $arr_link[]=$addid;
        }
        if($delid){
            foreach ($arr_link as $key => $value) {
                if($value==$delid){
                    unset($arr_link[$key]);
                }
            }
        }
        M('user')->where("id=".$_SESSION['userinfo']['uid'])->save(array('bos_link'=>implode(',', $arr_link)));
    }
    public function readtixing(){

        $Data = M('prompt_information')->field('end_time')->where("id=".I('get.id'))->find();
        if($Data['end_time']){//end_time不为空则是会议室申请
            if(time() > strtotime($Data['end_time'])){
                M('prompt_information')->where("id=".I('get.id'))->save(array('status'=>1));
            }
        }else{
            M('prompt_information')->where("id=".I('get.id'))->save(array('status'=>1));
        }

        $data_p = M('prompt_information')->where("id=".I('get.id'))->find();
        $type   = trim(I("type"));
        if($type=="oa"){
            $data_p['a_link'] = "/OA/Index/main.html?mainurl=".str_replace("&", "?_",$data_p['a_link']);
        }elseif($type=="bos"){
            $data_p['a_link'] = "/Home/Index/main.html?mainurl=".$data_p['a_link'];
        }
        header('Location:'.$data_p['a_link']);
    }
    public function addnewtanmu(){
        $id=M('tanmu')->add(array('uid'=>$_SESSION['userinfo']['uid'],'content'=>I('post.content'),'addtime'=>date('Y-m-d H:i:s')));
        if($id)echo json_encode(array('status'=>1,'msg'=>'添加成功'));
        else echo json_encode(array('status'=>2,'msg'=>'添加失败'));
    }
    public function getalltanmu(){
        $data=M('tanmu')->order('id desc')->select();
        echo json_encode($data);
    }
}