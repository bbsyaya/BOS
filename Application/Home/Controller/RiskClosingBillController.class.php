<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;

/**
 * 业务数据关账
 * Class RiskClosingBillController
 * @package Home\Controller
 */
class RiskClosingBillController extends BaseController {

	protected $totalPage = 0;

	public function index() {
		$_GET['inandout']=1;
		$linelist=M('business_line')->field('id,name')->select();
		$this->assign('linelist',$linelist);
		$comlist=M('product')->field('id,name')->select();
		$this->assign('comlist',$comlist);
		$advlist=M('advertiser')->field('id,name')->select();
		$this->assign('advlist',$advlist);
		$suplist=M('supplier')->field('id,name')->select();
		$this->assign('suplist',$suplist);
		$jflist=M('charging_logo')->field('id,name')->where("status=1")->select();
		$this->assign('jflist',$jflist);
		$this->userlist=M('user')->field('real_name,id')->select();
		$time_s=date('Y-m').'-01';
		$time_e=date('Y-m').'-31';
		if(!empty(I('get.time_s')))$time_s=I('get.time_s').'-01';
		if(!empty(I('get.time_e')))$time_e=I('get.time_e').'-31';
		$total=D('ChargingLogo')->getmonthdatacount_close($time_s,$time_e);
		$this->getpagelist($total);
		$data=D('ChargingLogo')->getmonthtable_close($time_s,$time_e);

		$this->assign('data',$data['data']);

		$this->alldatalist=D('ChargingLogo')->getmonthalldata_close($time_s,$time_e);
		$this->assign('showtablestr',$data['showtablestr']);
		$this->display();

	}


	//获取列表
	public function getList($where, $field) {

		$adIds = I('get.adid','');
		if(!empty($adIds)) {
			$adArr = M('advertiser')->where("id IN ({$adIds})")->field('id,name')->select();
			$where['in_adverid'] = array('in',$adIds);
		}
		$proIds = I('get.proid','');
		if(!empty($proIds)) {
			$proArr = M('product')->where("id IN ({$proIds})")->field('id,name')->select();
			$where['in_comid'] = array('in',$proIds);
		}
		$blIds = I('get.blid','');
		if(!empty($blIds)) {
			$blArr = M('business_line')->where("id IN ({$blIds})")->field('id,name')->select();
			$where[] = "(in_lineid in ($blIds) || out_lineid in ($blIds))";
		}
		$clIds = I('get.clid','');
		if(!empty($clIds)) {
			$clArr = M('charging_logo')->where("id IN ({$clIds})")->field('id,name')->select();
			$where['jfid'] = array('in',$clIds);
		}
		$supIds = I('get.supid','');
		if(!empty($supIds)) {
			$supArr = M('supplier')->where("id IN ({$supIds})")->field('id,name')->select();
			$where['out_superid'] = array('in', $supIds);
		}

		$date = I('get.date','');
		$date = empty($date) ? date('Y-m') : $date;
		$where[] = "adddate like '$date%'";

		//数据权限
        $arr_name=array();
        $arr_name['line']=array('a.in_lineid','a.out_lineid');
        $arr_name['user']=array('a.in_salerid','a.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where[]= $myrule_data;
        //var_dump($myrule_data);


		$this->assign('adNames', empty($adArr)?'[]':json_encode($adArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('proNames', empty($proArr)?'[]':json_encode($proArr,JSON_UNESCAPED_UNICODE));
		$this->assign('blNames', empty($blArr)?'[]':json_encode($blArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('clNames', empty($clArr)?'[]':json_encode($clArr,JSON_UNESCAPED_UNICODE));
		$this->assign('supNames', empty($supArr)?'[]':json_encode($supArr,JSON_UNESCAPED_UNICODE));

		$model = M('closing');
		$ret = $model
			->field('left(a.adddate,7) as month,bl.name as blname,pro.name as proname,cl.name as clname,cl.charging_mode,pro.source_type,a.in_newmoney as inmoney,a.out_newmoney as outmoney,(a.in_newmoney/a.out_newmoney)-1 as profit,sum(if(a.in_status>1 && a.in_status!=9,a.in_newmoney,0))/sum(if(a.in_status!=0 && a.in_status!=9,a.in_newmoney,0)) as inquerenlv,sum(if(a.in_status=4 || a.in_status=5,a.in_newmoney,0))/sum(if(a.in_status in (3,4,5,8),a.in_newmoney,0)) as kaipiaolv,sum(if(a.in_status=5,a.in_newmoney,0))/sum(if(a.in_status in (3,4,5,8),a.in_newmoney,0)) as huikuanlv,"暂未统计" as fukuanlv,ad.name as adname,sp.name as spname,f.invoice_type,ad.invoice_type as fp')
			->where($where)
			->alias('a')
			->join('
			LEFT JOIN boss_advertiser ad ON ad.id=a.in_adverid
			LEFT JOIN boss_supplier sp ON sp.id=a.out_superid
			LEFT JOIN boss_product pro ON pro.id=a.in_comid
			LEFT JOIN boss_business_line bl ON bl.id=a.out_lineid
			LEFT JOIN boss_charging_logo cl ON cl.id=a.jfid
			left join boss_supplier_finance f on if((sp.type=2 || sp.type=3) && (a.out_addid=0 || a.out_addid is null),sp.id,a.out_addid)=f.sp_id && a.out_lineid=f.bl_id
			')
			->group('a.jfid,a.out_superid,a.out_businessid,a.in_adverid,a.in_lineid,a.in_salerid,a.out_lineid')
			->page($_GET['p'], C('LIST_ROWS'))
			->select();

		$this->totalPage = $model->where($where)->count();

		//列求和
		$itemTotal = $model
			->field('   
				  sum(in_newmoney) AS in_money,
				  sum(out_newmoney) AS out_money
				  ')
			->where($where)
			->alias('clo')
			->select();

		$this->assign('itemTotal', $itemTotal[0]);


		return $ret;

	}

	/**
	 * 导出数据
	 */
	public function export() {
		$where = array();
		C('LIST_ROWS', '');
		$list = $this->lists($this, $where);
		$title = array('month'=>'年月','lineid'=>'业务线','comid'=>'产品名称','jfid'=>'计费标识','charging_mode'=>'计费模式',
			'source_type'=>'来源类型','inmoney'=>'收入金额','outmoney'=>'成本金额','profit'=>'毛利率','inquerenlv'=>'收入确认率',
			'kaipiaolv'=>'开票率','huikuanlv'=>'回款率','fukuanlv'=>'付款率','adverid'=>'广告主','superid'=>'供应商');
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '业务数据关账'.date('Y-m-d H:i:s'));
	}

	/*
	 * 同步暂估收入
	 * */
	function ZinData(){

		$date_t = $_POST['date_t'];
		//echo $date_t;exit;
		if(!empty($date_t)){

			$firstday = date('Y-m-01', strtotime($date_t));
			$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
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
			//暂估收入数据
			$sr_f = '/zInData/insertZInData';
			$sr_url = $http_r.$gcm.$sr_f.'?token='.$token;
			$srModel = M('closing');
			$where = "a.adddate >='".$firstday."' and a.adddate<='".$lastday."' and a.in_newmoney>0 ";
			//查询总条数
			$count_num = $srModel->join('a left join boss_advertiser b on a.in_adverid=b.id left join boss_business_line c on a.in_lineid=c.id left join boss_data_dic d on a.in_ztid=d.id')->where($where)->count();
			//echo $srModel->getLastSql();exit;
			if(empty($count_num)){
				$this->ajaxReturn($date_t."无关账数据,请重试");exit;
			}
			if($count_num <1000 && $count_num>0){
				$count_num_f = 1;
			}elseif($count_num >=1000){
				$count_num_f = ceil($count_num/1000);
			}
			//echo $count_num_f;exit;
			$res = "";
			for($i=0;$i<$count_num_f;$i++) {

				$srData = $srModel->field('b.ad_code,b.`name`,c.id AS bl_id,c.`name` AS bl_name,b.invoice_type,d.`code` AS bd_code,a.in_newmoney')->join('a left join boss_advertiser b on a.in_adverid=b.id left join boss_business_line c on a.in_lineid=c.id left join boss_data_dic d on a.in_ztid=d.id')->where($where)->order('a.id desc')->limit($i * 1000, 1000)->select();
				//echo $srModel->getLastSql();exit;
				$sr_Data = array();
				if(empty($srData)) {
					$this->ajaxReturn("当月无关账信息，请重新选择月份");exit;
				} else {
					foreach ($srData as $key => $sr_val) {
						$sr_Data[$key]['dDate'] = $lastday;
						if ($sr_val['invoice_type'] == 1) {
							$sr_Data[$key]['TaxRate'] = '0';
						} elseif ($sr_val['invoice_type'] == 2) {
							$sr_Data[$key]['TaxRate'] = '0.03';
						} elseif ($sr_val['invoice_type'] == 3) {
							$sr_Data[$key]['TaxRate'] = '0.06';
						} elseif ($sr_val['invoice_type'] == 4) {
							$sr_Data[$key]['TaxRate'] = '0.09';
						} elseif ($sr_val['invoice_type'] == 5) {
							$sr_Data[$key]['TaxRate'] = '0.17';
						}else{
							$sr_Data[$key]['TaxRate'] = '0';
						}
						$sr_Data[$key]['CusCode'] = $sr_val['ad_code'];
						$sr_Data[$key]['CusName'] = $sr_val['name'];
						$sr_Data[$key]['ItemCode'] = $sr_val['bl_id'];
						$sr_Data[$key]['ItemName'] = $sr_val['bl_name'];
						$sr_Data[$key]['AccID'] = $sr_val['bd_code'];
						$sr_Data[$key]['Money'] = $sr_val['in_newmoney'];
					}
					$sr_Data = json_encode($sr_Data);
					//echo $sr_Data;exit;
					$sr_res = bossPostData_json($sr_url, $sr_Data);
					$srRes = json_decode($sr_res,true);
					$res = $srRes['message'];
					if ($srRes['message'] != "success") {
						sleep(10);//暂停10秒
						$sr_res = bossPostData_json($sr_url, $sr_Data);
						$srRes = json_decode($sr_res,true);
						if($srRes['message'] != "success"){
							sleep(10);//暂停10秒
							$sr_res = bossPostData_json($sr_url, $sr_Data);
							$srRes = json_decode($sr_res,true);
							if($srRes['message'] != "success"){
								sleep(10);//暂停10秒
								$sr_res = bossPostData_json($sr_url, $sr_Data);
								$srRes = json_decode($sr_res,true);
								if($srRes['message'] != "success"){
									//$this->ajaxReturn("网络不稳定,同步收入暂估失败,请重试");exit;
								}
							}
						}
					}
				}
			}
			if($res == "success"){
				$this->ajaxReturn("TRUE");exit;
			}

		}else{
			$this->ajaxReturn("时间期间不能为空，请重新选择");exit;
		}
	}

	/*
	 * 同步暂估成本
	 * */
	function ZcostData(){
		$date_t = $_POST['date_t'];
		if(!empty($date_t)){

			$firstday = date('Y-m-01', strtotime($date_t));
			$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));

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

			$cb_f = '/zCost/insertZCostData';
			$cb_url = $http_r.$gcm.$cb_f.'?token='.$token;
			$cbModel = M('closing');
			$where = "a.adddate >='".$firstday."' and a.adddate<='".$lastday."' and a.out_newmoney>0 ";
			//查询总条数
			$cb_count = $cbModel->join('a left join boss_supplier b on a.out_superid=b.id join boss_data_dic c on a.out_sbid=c.id left join boss_business_line d on a.out_lineid=d.id left join boss_supplier_finance e on a.out_superid=e.sp_id && a.out_lineid=e.bl_id
')->where($where)->count();
			if(empty($cb_count)){
				$this->ajaxReturn($date_t."无关账数据,请重试");exit;
			}
			if($cb_count>0 && $cb_count<1000){
				$cb_count_f = 1;
			}elseif($cb_count>=1000){
				$cb_count_f = ceil($cb_count/1000);
			}
			$res = "";
			for($i=0;$i<$cb_count_f;$i++) {
				$cbData = $cbModel->field('b.`code`,b.`name`,c.`code` AS bd_code,e.financial_tax,a.out_newmoney,d.id AS bl_id,d.`name` AS bl_name')->join('a left join boss_supplier b on a.out_superid=b.id join boss_data_dic c on a.out_sbid=c.id left join boss_business_line d on a.out_lineid=d.id left join boss_supplier_finance e on a.out_superid=e.sp_id && a.out_lineid=e.bl_id
')->where($where)->order('a.id desc')->limit($i * 1000, 1000)->select();
				//echo $cbModel->getLastSql();exit;
				$cb_data = array();
				foreach ($cbData as $key => $val) {
					$cb_data[$key]['ItemCode'] = $val['bl_id'];
					$cb_data[$key]['ItemName'] = $val['bl_name'];
					if(!empty($val['code'])) {
						$cb_data[$key]['cVenCode'] = $val['code'];
					}else{
						$cb_data[$key]['cVenCode'] = '0';
					}
					if(!empty($val['name'])) {
						$cb_data[$key]['cVenName'] = $val['name'];
					}else{
						$cb_data[$key]['cVenName'] = '0';
					}
					if($val['bd_code']) {
						$cb_data[$key]['AccID'] = $val['bd_code'];
					}else{
						$cb_data[$key]['AccID'] = '0';
					}
					if($val['outmoney']) {
						$cb_data[$key]['Money'] = $val['out_newmoney'];
					}else{
						$cb_data[$key]['Money'] = '0';
					}
					if(!empty($val['financial_tax'])){
						$cb_data[$key]['TaxRate'] = $val['financial_tax'];
					}else{
						$cb_data[$key]['TaxRate'] = '0';
					}

					$cb_data[$key]['dDate'] = $lastday;
				}
				$cb_data = json_encode($cb_data);
				//echo $cb_data;exit;
				$cb_res = bossPostData_json($cb_url, $cb_data);
				$cbRes = json_decode($cb_res, true);
				$res = $cbRes['message'];
				if ($cbRes['message'] != "success") {
					sleep(10);//暂停10秒
					$sr_res = bossPostData_json($cb_url, $cb_data);
					$cbRes = json_decode($sr_res,true);
					if($cbRes['message'] != "success"){
						$this->ajaxReturn("网络不稳定,同步成本暂估失败,请重试");exit;
					}
				}
			}
			if($res == "success"){
				$this->ajaxReturn("TRUE");exit;
			}
		}else{
			$this->ajaxReturn("时间期间不能为空，请重新选择");exit;
		}
	}

}


