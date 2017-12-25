<?php
namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 已付成本未收款
 * Class RiskPayNotReceiveController
 * @package Home\Controller
 */
class RiskPayNotReceiveController extends BaseController {
	protected $totalPage = 0;

	public function index() {
		$where = array();

		if (I('get.search',0) == 1) {
			$list = $this->lists($this, $where);
		}

		$this->assign('list', $list);
		$this->display();

	}


	public function getList($where, $field) {
		$where['bd.status'] = array('lt',5);
		$where['bdo.status'] = array('eq', 4);

		$blIds = I('get.blid','');
		if(!empty($blIds)) {
			$blArr = M('business_line')->where("id IN ({$blIds})")->field('id,name')->select();
			$where['bd.lineid'] = array('in',$blIds);
		}
		$proIds = I('get.proid','');
		if(!empty($proIds)) {
			$proArr = M('product')->where("id IN ({$proIds})")->field('id,name')->select();
			$where['bp.id'] = array('in',$proIds);
		}
		$clIds = I('get.clid','');
		if(!empty($clIds)) {
			$clArr = M('charging_logo')->where("id IN ({$clIds})")->field('id,name')->select();
			$where['bdo.jfid'] = array('in',$clIds);
		}

		$salerIds = I('get.saler','');
		if(!empty($salerIds)) {
			$salerArr = M('user')->where("id IN ({$salerIds})")->field('id,real_name')->select();
			$where['bd.salerid'] = array('in',$salerIds);
		}
		$supIds = I('get.sup','');
		if(!empty($supIds)) {
			$supArr = M('supplier')->where("id IN ({$supIds})")->field('id,name')->select();
			$where['cla.sup_id'] = array('in',$supIds);
		}

		$mon = I('get.mon');
		if($mon){
			$this->assign('mon',$mon);
			$where['_string'] = "DATE_FORMAT(bd.adddate,'%Y-%m')='{$mon}'";
		}else{
			$date = I('get.date','');
			$date = empty($date) ? date('Y-m') : $date;
			$where['_string'] = "DATE_FORMAT(bd.adddate,'%Y-%m')='{$date}'";
		}
		$pl = I('get.pl');
		if($pl){
			$where['bd.lineid'] =$pl;
		}

		$busmanIds = I('get.busman','');
		if(!empty($busmanIds)) {
			$busmanArr = M('user')->where("id IN ({$busmanIds})")->field('id,real_name')->select();
			//$where['e.businessid'] = array('in',$busmanIds);
			$where .= " AND bdo.businessid IN ($busmanIds)";
		}

		$adIds = I('get.adid','');
		if(!empty($adIds)) {
			$adArr = M('advertiser')->where("id IN ({$adIds})")->field('id,name')->select();
			//$where['d.adverid'] = array('in',$adIds);
			$where .= " AND bd.adverid IN ($adIds)";
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

		$this->assign('blNames', empty($blArr)?'[]':json_encode($blArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('proNames', empty($proArr)?'[]':json_encode($proArr,JSON_UNESCAPED_UNICODE));
		$this->assign('clNames', empty($clArr)?'[]':json_encode($clArr,JSON_UNESCAPED_UNICODE));
		$this->assign('salerNames', empty($salerArr)?'[]':json_encode($salerArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('supNames', empty($supArr)?'[]':json_encode($supArr,JSON_UNESCAPED_UNICODE));

		$this->assign('busmanNames', empty($busmanArr)?'[]':json_encode($busmanArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('adNames', empty($adArr)?'[]':json_encode($adArr,JSON_UNESCAPED_UNICODE));//

		$model = M('daydata');

		//列汇总
		$sumData = $model
			->field('
				  SUM(IFNULL(bdo.newmoney, bdo.money)) AS out_money,
				  SUM(IFNULL(bd.newmoney, bd.money)) AS in_money,
				  SUM(IFNULL(bd.newmoney, bd.money)) AS not_rec_money,
				  SUM(IFNULL(bdo.newmoney, bdo.money)) AS out_money,
				  bsi.settlementmoney')
			->alias('bd')
			->join('
			      JOIN `boss_charging_logo` cl
				    ON cl.id = bd.jfid
				  RIGHT JOIN `boss_daydata_out` bdo
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate`
				  JOIN boss_product bp
				    ON bp.id = bd.`comid`
				  JOIN boss_advertiser ba
				    ON ba.id = bd.`adverid`
				  LEFT JOIN boss_supplier sup
				    ON sup.id=bdo.`superid`
				  LEFT JOIN boss_user bu
				    ON bu.id = bdo.`businessid`
				  LEFT JOIN `boss_settlement_in` bsi
				    ON FIND_IN_SET(bd.`jfid`, bsi.`alljfid`) > 0 AND (bsi.strdate<=bdo.`adddate` AND bsi.`enddate`>=bdo.`adddate`)
				  LEFT JOIN boss_business_line bl on bl.id=bd.lineid and bl.status=1
				  LEFT JOIN boss_user sa
				    ON sa.id = bd.`salerid`
				  ')
			->where($where)
			->find();
			$this->assign('sumData',$sumData);

		$ret = $model
			->field('
				  bd.status,
				  bl.name as bl_name,
				  cl.name as cl_name,
				  DATE_FORMAT(bdo.adddate, \'%Y-%m\') AS dates,
				  bp.`name` AS pro_name,
				  ba.`name` AS ad_name,
				  sup.`name` AS sup_name,
				  bd.`comid`,
				  bu.`real_name` AS sw_name,
				  sa.`real_name` AS saler_name,
				  bdo.`jfid`,
				  SUM(IFNULL(bdo.newmoney, bdo.money)) AS out_money,
				  SUM(IFNULL(bd.newmoney, bd.money)) AS in_money,
				  SUM(IFNULL(bd.newmoney, bd.money)) AS not_rec_money,
				  SUM(IFNULL(bdo.newmoney, bdo.money)) AS out_money,
				  bd.auditdate AS out_date,
				  bsi.`audittime`,
				  bsi.settlementmoney')
			->alias('bd')
			->join('
			      JOIN `boss_charging_logo` cl 
				    ON cl.id = bd.jfid 
				  RIGHT JOIN `boss_daydata_out` bdo 
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate` 
				  JOIN boss_product bp 
				    ON bp.id = bd.`comid`
				  JOIN boss_advertiser ba 
				    ON ba.id = bd.`adverid`
				  LEFT JOIN boss_supplier sup 
				    ON sup.id=bdo.`superid`
				  LEFT JOIN boss_user bu 
				    ON bu.id = bdo.`businessid` 
				  LEFT JOIN `boss_settlement_in` bsi 
				    ON FIND_IN_SET(bd.`jfid`, bsi.`alljfid`) > 0 AND (bsi.strdate<=bdo.`adddate` AND bsi.`enddate`>=bdo.`adddate`)
				  LEFT JOIN boss_business_line bl on bl.id=bd.lineid and bl.status=1
				  LEFT JOIN boss_user sa
				    ON sa.id = bd.`salerid`
				  ')
			->where($where)
			->order($order)
			->group("bdo.`jfid`,DATE_FORMAT(bdo.adddate, '%Y-%m')")
			->page($_GET['p'], C('LIST_ROWS'))
			->select();

		if (!empty($ret)) {
			foreach ($ret as &$val) {
				if($val['status'] ==1){
					$val['status'] = '待审核';
				}elseif($val['status'] ==2) {
					$val['status'] = '已确认';
				}elseif($val['status'] ==3) {
					$val['status'] = '待开票';
				}elseif($val['status'] ==4) {
					$val['status'] = '已开票';
				}
				//收支差
				$val['in_out'] = round((float)$val['out_money']-(float)$val['in_money'],2);
				//收入审核时间
				$val['in_valid_time'] = empty($val['audittime']) ? '' : $val['audittime'];
				//风险预警时长
				$val['warnDays'] = ceil((NOW_TIME - (int)strtotime($val['in_valid_time']))/86400);//风险预警时长（天）
				//风险预警等级
				$val['warnLevel'] = $this->getWarnLevel($val['warnDays']);
			}
		}

		$subQuery =	$model
			->field('cl.id')
			->alias('bd')
			->join('
			      JOIN `boss_charging_logo` cl 
				    ON cl.id = bd.jfid 
				  RIGHT JOIN `boss_daydata_out` bdo 
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate` 
				  JOIN boss_product bp 
				    ON bp.id = bd.`comid`
				  JOIN boss_advertiser ba 
				    ON ba.id = bd.`adverid`
				  LEFT JOIN boss_supplier sup 
				    ON sup.id=bdo.`superid`
				  LEFT JOIN boss_user bu 
				    ON bu.id = bdo.`businessid` 
				  LEFT JOIN `boss_settlement_in` bsi 
				    ON FIND_IN_SET(bd.`comid`, bsi.`allcomid`) > 0 AND (bsi.strdate<=bdo.`adddate` AND bsi.`enddate`>=bdo.`adddate`)')
			->where($where)
			->group("bdo.`jfid`,DATE_FORMAT(bdo.adddate, '%Y-%m')")
			->buildSql();
		$this->totalPage = $model->table($subQuery.' aa')->where()->count();

		return $ret;

	}


	/**
	 * 导出数据
	 */
	public function export() {

		$where = array();
		C('LIST_ROWS', '');
		$list = $this->lists($this, $where);
		$title = array('dates'=>'年月','bl_name'=>'业务线','pro_name'=>'产品名称','cl_name'=>'计费标识',
			'ad_name'=>'广告主名称','sup_name'=>'供应商名称','in_valid_time'=>'收入审核时间',
			'in_money'=>'收入金额','status'=>'收入状态','out_money'=>'成本支付金额 	','saler_name'=>'销售','sw_name'=>'商务');
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '已付成本未收款明细'.date('Y-m-d H:i:s',time()));

	}

	//风险预警级
	public function getWarnLevel($interval=0) {
		$level = '';
		if ($interval < 30) {
			$level = '四级';
		} else if ($interval >= 30 && $interval < 60) {
			$level = '三级';
		} else if ($interval >= 60 && $interval < 90) {
			$level = '二级';
		} else if ($interval >= 90) {
			$level = '一级';
		}
		return $level;

	}


	public function chartView() {

		$item = I('get.item',0,'intval');
		$model = M('Product');

		$res = array();
		$fields = '';
		switch ($item) {
			case 1:
				//风险等级占比
				$typeArr = array(1=>'一级',2=>'二级',3=>'三级',4=>'四级');
				$data = M()->query("SELECT 
				  COUNT(cl.id) AS num,
				  CASE WHEN DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01')) <30 THEN 4
				  WHEN DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01'))>=30 AND DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01')) <60 THEN 3
				  WHEN DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01'))>=60 AND DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01')) <90 THEN 2 
				  WHEN DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01'))>=90 THEN 1
				  END AS warnlevel
				FROM
				  boss_daydata bd 
				  JOIN `boss_charging_logo` cl 
				    ON cl.id = bd.jfid 
				  RIGHT JOIN `boss_daydata_out` bdo 
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate` 
				  JOIN boss_product bp 
				    ON bp.id = bd.`comid`
				  JOIN boss_advertiser ba 
				    ON ba.id = bd.`adverid`
				  LEFT JOIN boss_supplier sup 
				    ON sup.id=bdo.`superid`
				  LEFT JOIN boss_user bu 
				    ON bu.id = bdo.`businessid` 
				  LEFT JOIN `boss_settlement_in` bsi 
				    ON FIND_IN_SET(bd.`comid`, bsi.`allcomid`) > 0 AND (bsi.strdate<=bdo.`adddate` AND bsi.`enddate`>=bdo.`adddate`)
				WHERE bd.status < 5 
				  AND bdo.status = 4 
				GROUP BY warnlevel");

				$datatype = array();
				if(!empty($data)) {
					foreach($data as $val) {
						$datatype[$val['warnlevel']] = $val['num'];
					}
				}
				break;
			case 2:
				//收支差
				$data = M()->query("SELECT 
				  SUM(IFNULL(bdo.newmoney, bdo.money)-IFNULL(bd.newmoney, bd.money)) AS in_out,
				  CASE WHEN DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01')) <30 THEN 4
				  WHEN DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01'))>=30 AND DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01')) <60 THEN 3
				  WHEN DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01'))>=60 AND DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01')) <90 THEN 2 
				  WHEN DATEDIFF(NOW(),IFNULL(bsi.`audittime`,'1900-01-01'))>=90 THEN 1
				  END AS warnlevel
				FROM
				  boss_daydata bd 
				  JOIN `boss_charging_logo` cl 
				    ON cl.id = bd.jfid 
				  RIGHT JOIN `boss_daydata_out` bdo 
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate` 
				  JOIN boss_product bp 
				    ON bp.id = bd.`comid`
				  JOIN boss_advertiser ba 
				    ON ba.id = bd.`adverid`
				  LEFT JOIN boss_supplier sup 
				    ON sup.id=bdo.`superid`
				  LEFT JOIN boss_user bu 
				    ON bu.id = bdo.`businessid` 
				  LEFT JOIN `boss_settlement_in` bsi 
				    ON FIND_IN_SET(bd.`comid`, bsi.`allcomid`) > 0 AND (bsi.strdate<=bdo.`adddate` AND bsi.`enddate`>=bdo.`adddate`)
				WHERE bd.status < 5 
				  AND bdo.status = 4 
				GROUP BY warnlevel");

				$datatype = array();
				if(!empty($data)) {
					foreach($data as $val) {
						$datatype[$val['warnlevel']] = $val['in_out'];
					}
				}

				$typeArr = array(1=>'一级',2=>'二级',3=>'三级',4=>'四级');
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

}


