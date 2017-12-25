<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 核检统计
 * Class RiskCheckStatisticsController
 * @package Home\Controller
 */
class RiskCheckStatisticsController extends BaseController {
	protected $totalPage = 0;
	private $finLogSer;
	public function index() {
		$where = array();
		$list = array();
		if (I('get.search',0) == 1) {
			$list = $this->lists($this, $where);
		}
		$this->assign('list', $list);
		$this->display();

	}


	public function detail(){
		$this->display();
	}


	//获取列表
	public function getList($where, $field) {
		$where = '1=1';
		$adIds = I('get.adid','');
		if(!empty($adIds)) {
			$adArr = M('advertiser')->where("id IN ({$adIds})")->field('id,name')->select();
			//$where['d.adverid'] = array('in',$adIds);
			$where .= " AND d.adverid IN ($adIds)";
		}
		$proIds = I('get.proid','');
		if(!empty($proIds)) {
			$proArr = M('product')->where("id IN ({$proIds})")->field('id,name')->select();
			//$where['c.id'] = array('in',$proIds);
			$where .= " AND c.id IN ($proIds)";
		}
		$clIds = I('get.clid','');
		if(!empty($clIds)) {
			$clArr = M('charging_logo')->where("id IN ({$clIds})")->field('id,name')->select();
			//$where['a.jfid'] = array('in',$clIds);
			$where .= " AND a.jfid IN ($clIds)";
		}

		$salerIds = I('get.saler','');
		if(!empty($salerIds)) {
			$salerArr = M('user')->where("id IN ({$salerIds})")->field('id,real_name')->select();
			//$where['d.`salerid`'] = array('in',$salerIds);
			$where .= " AND d.`salerid` IN ($salerIds)";
		}
		$supIds = I('get.sup','');
		if(!empty($supIds)) {
			$supArr = M('supplier')->where("id IN ({$supIds})")->field('id,name')->select();
			//$where['e.superid'] = array('in',$supIds);
			$where .= " AND e.superid IN ($supIds)";
		}
		$busmanIds = I('get.busman','');
		if(!empty($busmanIds)) {
			$busmanArr = M('user')->where("id IN ({$busmanIds})")->field('id,real_name')->select();
			//$where['e.businessid'] = array('in',$busmanIds);
			$where .= " AND e.businessid IN ($busmanIds)";
		}
		$blIds = I('get.bl','');
		if(!empty($blIds)) {
			$blArr = M('business_line')->where("id IN ({$blIds})")->field('id,name')->select();
			//$where['d.lineid'] = array('in',$blIds);
			$where .= " AND d.lineid IN ($blIds)";
		}
		//数据权限
        $arr_name=array();
        $arr_name['line']=array('c.bl_id');
        $arr_name['user']=array('c.saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where .=  " and $myrule_data";

		//$where['_string'] = "(d.id > 0 || e.id > 0)";
		$listwhere = $where . " AND (d.id > 0 || e.id > 0)";
		$sdate = I('get.sdate','');
		$sdate = empty($sdate) ? date('Y-m') : $sdate;
		$edate = I('get.edate','');
		if (empty($edate)) {
			$dateStrIn = "DATE_FORMAT(d.adddate,'%Y-%m')=\"{$sdate}\"";
			$dateStrOut = "DATE_FORMAT(e.adddate,'%Y-%m')=\"{$sdate}\"";
		} else {
			$dateStrIn = "DATE_FORMAT(d.adddate,'%Y-%m')>=\"{$sdate}\" && DATE_FORMAT(d.adddate,'%Y-%m')<=\"{$edate}\" ";
			$dateStrOut = "DATE_FORMAT(e.adddate,'%Y-%m')>=\"{$sdate}\" && DATE_FORMAT(e.adddate,'%Y-%m')<=\"{$edate}\" ";
		}

		//排序
		$orderStr = I('get.order');
		$order = '';
		if ($orderStr) {
			$pos = strpos($orderStr,'_desc');
			if ($pos === false) {
				$order = $orderStr.' DESC';
			} else {
				$order = substr($orderStr, 0, $pos).' ASC';
			}
		}

		$this->assign('adNames', empty($adArr)?'[]':json_encode($adArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('proNames', empty($proArr)?'[]':json_encode($proArr,JSON_UNESCAPED_UNICODE));
		$this->assign('clNames', empty($clArr)?'[]':json_encode($clArr,JSON_UNESCAPED_UNICODE));

		$this->assign('salerNames', empty($salerArr)?'[]':json_encode($salerArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('supNames', empty($supArr)?'[]':json_encode($supArr,JSON_UNESCAPED_UNICODE));
		$this->assign('busmanNames', empty($busmanArr)?'[]':json_encode($busmanArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('blNames', empty($blArr)?'[]':json_encode($blArr,JSON_UNESCAPED_UNICODE));
		//--------------------列表------------------------------------
		$model = M('charging_logo');
		$ret = $model
			->field('
			      d.lineid,
				  d.adverid,
				  e.superid,
				  d.`salerid`,
				  e.`businessid`,
				  DATE_FORMAT(d.adddate, \'%Y-%m\') AS ym,
				  a.name AS jf_name,
				  SUM(d.money) AS in_money,
				  SUM(e.money) AS out_money,
				  SUM(d.newmoney) AS in_newmoney,
				  SUM(e.newmoney) AS out_newmoney,
				  c.name AS pro_name')
			->join(" 
				   a 
				  LEFT JOIN boss_daydata d 
				    ON d.jfid = a.id && {$dateStrIn} && d.status != 0 
				  JOIN boss_product c 
				    ON a.prot_id = c.id 
				  LEFT JOIN boss_daydata_out e 
				    ON e.jfid = a.id && IF(
				      d.adddate IS NULL,
				      {$dateStrOut},
				      d.adddate = e.adddate
				    ) && e.status != 0 
				  LEFT JOIN boss_charging_logo_assign b 
				    ON b.cl_id = a.id && b.promotion_stime <= IF(
				      d.adddate IS NULL,
				      e.adddate,
				      d.adddate
				    ) && IF(
				      b.promotion_etime IS NULL,
				      1,
				      b.promotion_etime >= IF(
				        d.adddate IS NULL,
				        e.adddate,
				        d.adddate
				      )
				    ) ")
			->group("a.id,
				  d.lineid,
				  e.lineid,
				  d.adverid,
				  a.prot_id,
				  e.superid ")
			->order($order)
			->where($listwhere)
			->page($_GET['p'], C('LIST_ROWS'))
			->select();

		$adIds = $spIds = $salerIds = $busuids = $blids = array();
		foreach ($ret as $v) {
			$adIds[] = $v['adverid'];
			$spIds[] = $v['superid'];
			$salerIds[] = $v['salerid'];
			$busuids[] = $v['businessid'];
			$busuids[] = $v['businessid'];
			$blids[] = $v['lineid'];
		}

		$adidArr = empty($adIds) ? array() : M('advertiser')->where(array('id'=>array('in',$adIds)))->getField('id, name');
		$supArr = empty($spIds) ? array() : M('supplier')->where(array('id'=>array('in',$spIds)))->getField('id, name');
		$salerArr = empty($salerIds) ? array() : M('user')->where(array('id'=>array('in',$salerIds)))->getField('id, real_name AS name');
		$busArr = empty($busuids) ? array() : M('user')->where(array('id'=>array('in',$busuids)))->getField('id, real_name AS name');
		$blArr = empty($blids) ? array() : M('business_line')->where(array('id'=>array('in',$blids)))->getField('id, name');

		foreach ($ret as &$val) {
			$val['adverid'] = $adidArr[$val['adverid']];
			$val['superid'] = $supArr[$val['superid']];
			$val['salerid'] = $salerArr[$val['salerid']];
			$val['businessid'] = $busArr[$val['businessid']];
			$val['lineid'] = $blArr[$val['lineid']];
		}

		$ret = $this->_checkPriceList($ret);

		$subQuery =	$model
			->field('d.lineid')
			->join(" 
				   a 
				  LEFT JOIN boss_daydata d 
				    ON d.jfid = a.id && {$dateStrIn} && d.status != 0 
				  JOIN boss_product c 
				    ON a.prot_id = c.id 
				  LEFT JOIN boss_daydata_out e 
				    ON e.jfid = a.id && IF(
				      d.adddate IS NULL,
				      {$dateStrOut},
				      d.adddate = e.adddate
				    ) && e.status != 0 
				  LEFT JOIN boss_charging_logo_assign b 
				    ON b.cl_id = a.id && b.promotion_stime <= IF(
				      d.adddate IS NULL,
				      e.adddate,
				      d.adddate
				    ) && IF(
				      b.promotion_etime IS NULL,
				      1,
				      b.promotion_etime >= IF(
				        d.adddate IS NULL,
				        e.adddate,
				        d.adddate
				      )
				    ) ")
			->group("a.id,
				  d.lineid,
				  e.lineid,
				  d.adverid,
				  a.prot_id,
				  e.superid")
			->where($listwhere)
			->buildSql();
		$this->totalPage = $model->table($subQuery.' a')->where()->count();

		//-----------列求和---------------------------begin-----------------------------------
		$itemTotal = M()->query("(SELECT 
		    SUM(d.money) AS in_money,
			SUM(e.money) AS out_money,
			SUM(d.newmoney) AS in_newmoney,
			SUM(e.newmoney) AS out_newmoney 
		FROM
		  `boss_charging_logo` a 
		  LEFT JOIN boss_daydata d 
		    ON d.jfid = a.id && {$dateStrIn} && d.status != 0 
		  JOIN boss_product c 
		    ON a.prot_id = c.id 
		  LEFT JOIN boss_daydata_out e 
		    ON e.jfid = a.id && IF(
		      d.adddate IS NULL,
		      {$dateStrOut},
		      d.adddate = e.adddate
		    ) && e.status != 0 
		  LEFT JOIN boss_charging_logo_assign b 
				    ON b.cl_id = a.id && b.promotion_stime <= IF(
				      d.adddate IS NULL,
				      e.adddate,
				      d.adddate
				    ) && IF(
				      b.promotion_etime IS NULL,
				      1,
				      b.promotion_etime >= IF(
				        d.adddate IS NULL,
				        e.adddate,
				        d.adddate
				      )
				    )  
		WHERE (d.id > 0) AND {$where}
		LIMIT 1)
		
		UNION ALL
		
		(SELECT 
		    SUM(d.money) AS in_money,
			SUM(e.money) AS out_money,
			SUM(d.newmoney) AS in_newmoney,
			SUM(e.newmoney) AS out_newmoney 
		FROM
		  `boss_charging_logo` a 
		  LEFT JOIN boss_daydata_out e 
		    ON e.jfid = a.id && {$dateStrOut} && e.status != 0 
		  LEFT JOIN boss_daydata d 
		    ON d.jfid = a.id && IF(
		      e.adddate IS NULL,
		      {$dateStrIn},
		      d.adddate = e.adddate
		    ) && d.status != 0 
		  JOIN boss_product c 
		    ON a.prot_id = c.id
		  LEFT JOIN boss_charging_logo_assign b 
				    ON b.cl_id = a.id && b.promotion_stime <= IF(
				      d.adddate IS NULL,
				      e.adddate,
				      d.adddate
				    ) && IF(
				      b.promotion_etime IS NULL,
				      1,
				      b.promotion_etime >= IF(
				        d.adddate IS NULL,
				        e.adddate,
				        d.adddate
				      )
				    )   
		WHERE (d.id IS NULL) AND {$where}
		LIMIT 1)");

		$newItemTotal = array();
		foreach ($itemTotal as $val1) {
			$newItemTotal['out_datenum'] += round($val1['out_datenum'],2);
			$newItemTotal['out_newdatenum'] += round($val1['out_newdatenum'],2);
			$newItemTotal['out_money'] += round($val1['out_money'],2);
			$newItemTotal['out_newmoney'] += round($val1['out_newmoney'],2);
			$newItemTotal['in_datenum'] += round($val1['in_datenum'],2);
			$newItemTotal['in_newdatenum'] += round($val1['in_newdatenum'],2);
			$newItemTotal['in_money'] += round($val1['in_money'],2);
			$newItemTotal['in_newmoney'] += round($val1['in_newmoney'],2);
		}
		unset($itemTotal);
		$newItemTotal = $this->_checkPriceList($newItemTotal);
		$this->assign('itemTotal', $newItemTotal);
		//--------------------------------END----------------------------------------------
		return $ret;

	}


	private function _checkPriceList($datas=array()) {
		if (!empty($datas)) {
			foreach ($datas as $key=>$val) {
				$datas[$key]['in_money'] = round($datas[$key]['in_money'],2);
				$datas[$key]['in_newmoney'] = round($datas[$key]['in_newmoney'],2);
				$datas[$key]['out_money'] = round($datas[$key]['out_money'],2);
				$datas[$key]['out_newmoney'] = round($datas[$key]['out_newmoney'],2);

				//原始预估收入
				$inMoney = (float)$datas[$key]['in_money'];
				//确认收入
				$inNewMoney = (float)$datas[$key]['in_newmoney'];
				//收入核减金额 预估收入-确认收入
				$inCheckMoney = bcsub($inMoney, $inNewMoney, 2);
				//收入核减率
				$inCheckRatio = (float)bcdiv($inCheckMoney,$inMoney,2);
				//原始预估成本
				$outMoney = (float)$datas[$key]['out_money'];
				//确认成本
				$outNewMoney = (float)$datas[$key]['out_newmoney'];
				//成本核减金额 预估成本-确认成本；
				$outCheckMoney = bcsub($outMoney, $outNewMoney, 2);
				//成本核减率
				$outCheckRatio = (float)bcdiv($outCheckMoney,$outMoney,2);
				//利润变动额 （预估收入-预估成本）-（确认收入-确认成本）
				$profitChange = bcsub(bcsub($inMoney, $outMoney, 2),bcsub($inNewMoney, $outNewMoney, 2));
				//利润变动率 利润变动额/（预估收入-预估成本）、
				$_tmp = bcsub($inMoney, $outMoney, 2);
				$profitChangeRaio = $_tmp==0 ? 0 : bcdiv($profitChange, $_tmp, 2);

				$datas[$key]['inCheckMoney'] =  $inCheckMoney;
				$datas[$key]['inCheckRatio'] =  $inCheckRatio;
				$datas[$key]['outCheckMoney'] = $outCheckMoney;
				$datas[$key]['outCheckRatio'] = $outCheckRatio;
				$datas[$key]['profitChange'] =  $profitChange;
				$datas[$key]['profitChangeRaio'] = $profitChangeRaio;
			}
		}
		return $datas;
	}


	/**
	 * 导出数据
	 */
	public function export() {

		$where = array();
		C('LIST_ROWS', '');
		$list = $this->lists($this, $where);

		$title = array('ym'=>'年月','adverid'=>'广告主','superid'=>'供应商',
			'lineid'=>'业务线','pro_name'=>'产品','jf_name'=>'计费标识',
			'salerid'=>'销售','businessid'=>'商务','in_money'=>'原始收入','in_newmoney'=>'确认收入',
			'inCheckMoney'=>'收入核减金额','inCheckRatio'=>'收入核减率','out_money'=>'原始成本','out_newmoney'=>'确认成本',
			'outCheckMoney'=>'成本核减金额','outCheckRatio'=>'成本核减率','profitChange'=>'利润变动额','profitChangeRaio'=>'利润变动率');
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '核检统计'.date('Y-m-d H:i:s'));

	}

	public function chartView() {

		$item = I('get.item',0,'intval');
		$model = M('Product');

		$res = array();
		$fields = '';
		switch ($item) {
			case 1:
				//各业务线核检金额占比
				$typeArr = M('business_line')->getField('id,name');
				$datas = M()->query("SELECT 
							  bl.id AS blid,
							  bl.name AS bl_name,
							  SUM(bdo.money) AS out_money,
							  SUM(bdo.`newmoney`) AS out_newmoney,
							  SUM(bd.money) AS in_money,
							  SUM(bd.`newmoney`) AS in_newmoney 
							FROM
							  `boss_daydata` bd 
							  JOIN `boss_charging_logo` cl 
							    ON cl.id = bd.`jfid` 
							  LEFT JOIN `boss_charging_logo_assign` cla 
							    ON cl.id = cla.cl_id 
							  JOIN `boss_daydata_out` bdo 
							    ON bdo.jfid = cla.`id` 
							  JOIN boss_business_line bl 
							    ON bl.id = bd.lineid 
							GROUP BY bd.lineid ");

				$datatype = array();
				foreach ($datas as $val) {
					//原始预估收入
					$inMoney = (float)$val['in_money'];
					//确认收入
					$inNewMoney = (float)$val['in_newmoney'];
					//收入核减金额 预估收入-确认收入
					$inCheckMoney = bcsub($inMoney, $inNewMoney, 3);
					//原始预估成本
					$outMoney = (float)$val['out_money'];
					//确认成本
					$outNewMoney = (float)$val['out_newmoney'];
					//成本核减金额 预估成本-确认成本；
					$outCheckMoney = bcsub($outMoney, $outNewMoney, 3);
					$total = bcadd($inCheckMoney, $outCheckMoney,2);
					$datatype[$val['blid']] = $total;
				}
				break;
			case 2:
				//全公司核检利润变动率
				$typeArr = array('01'=>1,'02'=>2,'03'=>3,'04'=>4,'05'=>5,'06'=>6,'07'=>7,'08'=>8,'09'=>1,'10'=>10,'11'=>11,'12'=>12);
				$data = M()->query("SELECT 
					  DATE_FORMAT(bd.`adddate`, '%m') AS m,
					  SUM(bdo.money) AS out_money,
					  SUM(bdo.`newmoney`) AS out_newmoney,
					  SUM(bd.money) AS in_money,
					  SUM(bd.`newmoney`) AS in_newmoney 
					FROM
					  `boss_daydata` bd 
					  JOIN `boss_charging_logo` cl 
					    ON cl.id = bd.`jfid` 
					  LEFT JOIN `boss_charging_logo_assign` cla 
					    ON cl.id = cla.cl_id 
					  JOIN `boss_daydata_out` bdo 
					    ON bdo.jfid = cla.`id` 
					GROUP BY DATE_FORMAT(bd.`adddate`, '%y%m')");

				$datatype = array();
				foreach ($data as $val) {
					$inMoney = (float)$val['in_money'];
					$inNewMoney = (float)$val['in_newmoney'];
					$outMoney = (float)$val['out_money'];
					$outNewMoney = (float)$val['out_newmoney'];
					$profitChange = bcsub(bcsub($inMoney, $outMoney, 3),bcsub($inNewMoney, $outNewMoney, 3));
					$profitChangeRaio = bcdiv($profitChange,bcsub($inMoney, $outMoney, 3),3);
					$datatype[$val['m']] = $profitChangeRaio*100;
				}

				break;
			case 3:
				//广告主核检金额top10
				$datatype = M('daydata')->group('adverid')->order('check_money DESC')->limit(10)->getField('adverid,(money-newmoney) AS check_money');
				$adidArr = array_keys($datatype);
				$adids = implode(',', $adidArr);
				$typeArr = M('advertiser')->where("id IN ({$adids})")->getField('id,name');
				break;
			case 4:
				//供应商核检金额top10
				$datatype = M('daydata_out')->group('superid')->order('check_money DESC')->limit(10)->getField('superid,(money-newmoney) AS check_money');
				$supidArr = array_keys($datatype);
				$supids = implode(',', $supidArr);
				$typeArr = M('supplier')->where("id IN ({$supids})")->getField('id,name');
				break;
			case 5:
				//销售核检金额
				$datatype = M('daydata')->group('salerid')->order('check_money DESC')->limit(10)->getField('salerid,(money-newmoney) AS check_money');
				$useridArr = array_keys($datatype);
				$userids = implode(',', $useridArr);
				$typeArr = M('user')->where("id IN ({$userids})")->getField('id,real_name');
				break;
			case 6:
				//商务核检金额
				$datatype = M('daydata_out')->group('businessid')->order('check_money DESC')->limit(10)->getField('businessid,(money-newmoney) AS check_money');
				$supidArr = array_keys($datatype);
				$supids = implode(',', $supidArr);
				$typeArr = M('user')->where("id IN ({$supids})")->getField('id,real_name');
				break;
		}

		$fields = array_values($typeArr);
		foreach ($typeArr as $key=>$val) {
			$res[] = array(
				'name' => $val,
				'value'=> empty($datatype[$key]) ? 0 : $datatype[$key],
			);
		}

		$ret = array(
			'item'=> $item,
			'fields'=>$fields,
			'data'=>$res
		);
		$this->ajaxReturn($ret);

	}


	/**
	 * 发票核对 ---发票收入：V_RBillData
	 * @return [type] [description]
	 */
	public function invoiceCheck(){
		$riskSer         = new \Home\Service\RiskCheckService();
		$url             = C("VOUCHER_IP").C("VOUCHER_URL.queryRbillData_Url");

		//如果只选择一个时间，默认向前、后加一个月
		$map['boss_sdate'] = I("boss_sdate");
		$map['boss_edate'] = I("boss_edate");
		$map["lineid"]     = I("lineid");
		$map["advername"]  = I("advername");
		$map["pname"]      = I("pname");
		if(!$map['boss_sdate'] && !$map['boss_edate']){
			//当前时间前一个月
			$map['boss_sdate'] = $map['boss_edate'] = date("Y-m",time());
		}
		if($map['boss_sdate'] && !$map['boss_edate']){
			$map['boss_edate'] = $map['boss_sdate'];
		}
		if(!$map['boss_sdate'] && $map['boss_edate']){
			//结束时间前一个月
			$map['boss_sdate'] = $map['boss_edate'];
		}
		if($map['boss_sdate'] && $map['boss_edate']){
			$map['boss_sdate'] = date("Y-m",strtotime($map['boss_sdate']));
			$map['boss_edate'] = date("Y-m",strtotime($map['boss_edate']));
		}
		$this->assign("map",$map);
		$map["boss_sdate"] .= "-01";
		$month_fact_days = $riskSer->getMonthDays($map['boss_edate']);
		$map["boss_edate"] .= "-".$month_fact_days;
		//----------查询出boss数据
		$where = " where s.strdate<='".$map["boss_edate"]."' and s.enddate>='".$map["boss_sdate"]."' and s.status!=0 and s.status!=6";
		 // $where = " where s.enddate>='".$map["boss_sdate"]."' and s.enddate<='".$map["boss_edate"]."' and s.status!=0 and s.status!=6";
		// $where = " where s.enddate<='".$map["boss_edate"]."' and s.status!=0 and s.status!=6";
		//业务线
		if(intval($map["lineid"])>0){
			$where .= " and s.lineid=".$map["lineid"];
		}
		//广告主
		if($map["advername"]){
			$where .= " and a.name like '%".$map["advername"]."%'";
		}
		//产品名称
		if($map["pname"]){
			$where .= " and p.name like '%".$map["pname"]."%'";
		}

		//数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where .= " && $myrule_data";


		$result = $riskSer->invoiceCheckService($url,$where,$map);
		if($result["status"]==500){
			$this->assign("error",$result["msg"]);
		}

		if($result["status"]==200){
			$this->assign("invoice_type_list",C("OPTION.invoice_type"));
			$this->assign("pzlist",$result["data"]);
			$this->assign("total_boss_jiesuan_money",$result["total_boss_jiesuan_money"]);
			$this->assign("total_yongyou",$result["total_yongyou"]);
			//分页
			$this->assign("page",$result["page"]);
		}
		$this->display();
	}

	/**
	 * 收款核对总表  收款：V_RData
	 * @return [type] [description]
	 */
	function inmoneyCheckTotal(){
		$riskSer = new \Home\Service\RiskCheckService();
		$time    = time();
		$year    = date("Y",$time);
		$month   = ltrim(date("m",$time));
		$timeList = $riskSer->getLastTimeArea($year,$month,$month);
		//过滤时间
		foreach ($timeList as $k => $v) {
			$timeList[$k]["startMonth"] = $v["startMonth"]." 00:00:00";
			$timeList[$k]["endMonth"]   = $v["endMonth"]." 23:59:59";
		}
		krsort($timeList);
		$this->assign("timeList",$timeList);
		$this->assign("year",$year);
		$this->display();
	}

	/**
	 * 获取收入ajax数据
	 * @return [type] [description]
	 */
	function getInMoneyData(){
		$data["start"] = I("start");
		$data["end"]   = I("end");
		$data["month"] = I("month");
		$riskSer       = new \Home\Service\RiskCheckService();
		$riskResult    = $riskSer->getRiskIncomByTimeArea($data);
		$this->ajaxReturn($riskResult);
	}

	/**
	 * 收款核对  收款：V_RData
	 * @return [type] [description]
	 */
	public function inmoneyCheck(){
		$riskSer = new \Home\Service\RiskCheckService();
		//如果只选择一个时间，默认向前、后加一个月
		$map['boss_sdate'] = trim(I("boss_sdate"));
		$map['boss_edate'] = trim(I("boss_edate"));
		//替换+
		$map['boss_sdate'] = str_replace("+", " ", $map['boss_sdate']);
		$map['boss_edate'] = str_replace("+", " ", $map['boss_edate']);

		$map["lineid"]     = I("lineid");
		$map["advername"]  = trim(I("advername"));
		$map["pname"]      = trim(I("pname"));
		if(!$map['boss_sdate'] && !$map['boss_edate']){
			//当前时间前一个月
			$map['boss_sdate'] = $map['boss_edate'] = date("Y-m",time());
		}
		if($map['boss_sdate'] && !$map['boss_edate']){
			$map['boss_edate'] = $map['boss_sdate'];
		}
		if(!$map['boss_sdate'] && $map['boss_edate']){
			//结束时间前一个月
			$map['boss_sdate'] = $map['boss_edate'];
		}
		if($map['boss_sdate'] && $map['boss_edate']){
			$map['boss_sdate'] = date("Y-m",strtotime($map['boss_sdate']));
			$map['boss_edate'] = date("Y-m",strtotime($map['boss_edate']));
		}
		$this->assign("map",$map);
		$map["boss_sdate"] .= "-01";
		$month_fact_days = $riskSer->getMonthDays($map['boss_edate']);
		$map["boss_edate"] .= "-".$month_fact_days;
		//----------查询出boss数据
		$where = " where s.strdate<='".$map["boss_edate"]."' and s.enddate>='".$map["boss_sdate"]."' and r.type=1";
		// print_r($where);exit;
		//业务线
		if(intval($map["lineid"])>0){
			$where .= " and s.lineid=".$map["lineid"];
		}
		//广告主
		if($map["advername"]){
			$where .= " and a.name like '%".$map["advername"]."%'";
		}
		//产品名称
		if($map["pname"]){
			$where .= " and p.name like '%".$map["pname"]."%'";
		}

	
		//查询数据
		$result  = $riskSer->inmoneyCheckService($where,$map);
		if($result["status"]==500){
			$this->assign("error",$result["msg"]);
		}

		if($result["status"]==200){
			$this->assign("pzlist",$result["data"]);
			//分页
			$this->assign("page",$result["page"]);
			$this->assign("total_boss_jiesuan_money",$result["total_boss_jiesuan_money"]);
			$this->assign("total_yongyong",$result["total_yongyong"]);
			$this->assign("total_bank",$result["total_bank"]);
		}
		$this->display();
	}

	/**
	 * 付款核对总表  4付款：V_PaymentData
	 * @return [type] [description]
	 */
	function outmoneyCheckTotal(){
		$riskSer = new \Home\Service\RiskCheckService();
		$time    = time();
		$year    = date("Y",$time);
		$month   = ltrim(date("m",$time));
		$timeList = $riskSer->getLastTimeArea($year,$month,$month);
		//过滤时间
		foreach ($timeList as $k => $v) {
			$timeList[$k]["startMonth"] = $v["startMonth"]." 00:00:00";
			$timeList[$k]["endMonth"]   = $v["endMonth"]." 23:59:59";
		}
		krsort($timeList);
		$this->assign("timeList",$timeList);
		$this->assign("year",$year);
		$this->display();
	}



	/**
	 * 付款核对 2.4.4付款：V_PaymentData
	 * @return [type] [description]
	 */
	public function outmoneyCheck(){
		$riskSer = new \Home\Service\RiskCheckService();
		//如果只选择一个时间，默认向前、后加一个月
		$map['boss_sdate'] = trim(I("boss_sdate"));
		$map['boss_edate'] = trim(I("boss_edate"));
		//替换+
		$map['boss_sdate'] = str_replace("+", " ", $map['boss_sdate']);
		$map['boss_edate'] = str_replace("+", " ", $map['boss_edate']);

		$map["lineid"]     = I("lineid");
		$map["advername"]  = trim(I("advername"));
		$map["pname"]      = trim(I("pname"));
		if(!$map['boss_sdate'] && !$map['boss_edate']){
			//当前时间前一个月
			$map['boss_sdate'] = $map['boss_edate'] = date("Y-m",time());
		}
		if($map['boss_sdate'] && !$map['boss_edate']){
			$map['boss_edate'] = $map['boss_sdate'];
		}
		if(!$map['boss_sdate'] && $map['boss_edate']){
			//结束时间前一个月
			$map['boss_sdate'] = $map['boss_edate'];
		}
		if($map['boss_sdate'] && $map['boss_edate']){
			$map['boss_sdate'] = date("Y-m",strtotime($map['boss_sdate']));
			$map['boss_edate'] = date("Y-m",strtotime($map['boss_edate']));
		}
		$this->assign("map",$map);
		$map["boss_sdate"] .= "-01";
		$month_fact_days = $riskSer->getMonthDays($map['boss_edate']);
		$map["boss_edate"] .= "-".$month_fact_days;

		//----------查询出boss数据
		$where = " where s.strdate<='".$map["boss_edate"]."' and s.enddate>='".$map["boss_sdate"]."'";
		//业务线
		if(intval($map["lineid"])>0){
			$where .= " and s.lineid=".$map["lineid"];
		}
		//供应商
		if($map["supname"]){
			$where .= " and su.name like '%".$map["supname"]."%'";
		}
		//查询数据
		$result  = $riskSer->outmoneyCheckService($where,$map);
		if($result["status"]==500){
			$this->assign("error",$result["msg"]);
		}

		if($result["status"]==200){
			$this->assign("pzlist",$result["data"]);
			//分页
			$this->assign("page",$result["page"]);
		}
		$this->display();
	}

}


