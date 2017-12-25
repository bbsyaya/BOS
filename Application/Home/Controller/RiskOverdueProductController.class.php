<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 逾期监控
 * Class RiskOverdueProductController
 * @package Home\Controller
 */
class RiskOverdueProductController extends BaseController {

	protected $totalPage = 0;

	public function index() {

		$where = array();

		if (I('get.search',0) == 1) {
			$list = $this->lists($this, $where);
		}

		$this->assign('op_settlement_cycle',C('OPTION.settlement_cycle'));
		$this->assign('list', $list);
		$this->display();

	}


	//获取列表
	public function getList($where=array(), $field='') {

		$adIds = I('get.adid','');
		if(!empty($adIds)) {
			$adArr = M('advertiser')->where("id IN ({$adIds})")->field('id,name')->select();
			$where[] = "ad.id IN ({$adIds})";
		}
		$proIds = I('get.proid','');
		if(!empty($proIds)) {
			$proArr = M('product')->where("id IN ({$proIds})")->field('id,name')->select();
			$where[] = "p.id IN ({$proIds})";
		}
		$clIds = I('get.clid','');
		if(!empty($clIds)) {
			$clArr = M('charging_logo')->where("id IN ({$clIds})")->field('id,name')->select();
			$where[] = "dd.jfid IN ({$clIds})";
		}
		$scId = I('get.settle_cycle','');
		if(!empty($scId)) {
			$where[] = "p.settle_cycle ={$scId}";
		}
		$blIds = I('get.bl','');
		if(!empty($blIds)) {
			$blArr = M('business_line')->where("id IN ({$blIds})")->field('id,name')->select();
			$where[] = "dd.lineid IN ({$blIds})";
		}

		$sdate = I('get.sdate','');
		$sdate = empty($sdate) ? date('Y-m') : $sdate;
		$edate = I('get.edate','');
		if (empty($edate)) {
			$where[] = "DATE_FORMAT(dd.adddate,'%%Y-%%m')='{$sdate}'";
		} else {
			$where[] = "(DATE_FORMAT(dd.adddate,'%%Y-%%m')>='{$sdate}' AND DATE_FORMAT(dd.adddate,'%%Y-%%m')<='{$edate}')";
		}
		$this->assign('assDate',$sdate);

		
		//数据权限
        $arr_name=array();
        $arr_name['line']=array('dd.lineid');
        $arr_name['user']=array('dd.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where[]= $myrule_data;


		$whereStr = ' AND ' . implode(" AND ", $where);

		//排序
		$orderStr = I('get.order');
		if ($orderStr) {
			$pos = strpos($orderStr,'_desc');
			if ($pos === false) {
				$order = $orderStr.' DESC ';
			} else {
				$order = substr($orderStr, 0, $pos).' ASC ';
			}
			$order = ' ORDER BY ' . $order;
		}

		$this->assign('clNames', empty($clArr)?'[]':json_encode($clArr,JSON_UNESCAPED_UNICODE));
		$this->assign('adNames', empty($adArr)?'[]':json_encode($adArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('proNames', empty($proArr)?'[]':json_encode($proArr,JSON_UNESCAPED_UNICODE));
		$this->assign('op_settlement_cycle', C('OPTION.settlement_cycle'));
		$this->assign('blNames', empty($blArr)?'[]':json_encode($blArr,JSON_UNESCAPED_UNICODE));

		$baseSql = "SELECT 
  dd.comid,
  SUM(IFNULL(dd.newmoney, money)) AS notrec_money,
  SUM(
    IF(
      dd.status < 2,
      IFNULL(dd.newmoney, money),
      0
    )
  ) AS notconf_money,
  p.name AS pro_name,
  ad.`name` AS ad_name,
  p.settle_cycle,
  e.real_name AS saler_name,
  f.`name` AS bl_name,
  sb.name AS sb_name,
  dd.`adddate`
FROM
  `boss_daydata` AS dd 
  JOIN boss_product AS p 
    ON dd.comid = p.`id` 
  JOIN boss_advertiser ad
    ON ad.id=dd.`adverid`  
  JOIN boss_user e 
    ON e.id = dd.salerid 
  JOIN boss_business_line f 
    ON f.`id` = dd.lineid 
  LEFT JOIN boss_data_dic sb 
    ON sb.id = dd.ztid
WHERE (
    dd.status < 5 {$whereStr}
    AND %s 
    AND (
      DATEDIFF(NOW(), %s) > p.bill_day = 1 
      OR DATEDIFF(NOW(), %s) > p.receivables_day = 1 
      OR DATEDIFF(NOW(), %s) > p.reconciliation_day = 1
    )
  ) 
GROUP BY %s, dd.`comid` 
";

		//1 周
		$ld = "DATE_SUB(dd.`adddate`,INTERVAL WEEKDAY(dd.`adddate`) - 6 DAY)";
		$group = "FLOOR(DATEDIFF(dd.`adddate`, '1900-01-01') / 7)";
		$weekCycleSql = sprintf($baseSql,'p.settle_cycle=1',$ld,$ld,$ld,$group);
		//2 半月
		$ld = "IF(DAYOFMONTH(dd.adddate)>15,LAST_DAY(dd.adddate),DATE_FORMAT(dd.adddate,'%Y-%m-15'))";
		$group = "IF(DAYOFMONTH(dd.adddate)>15,LAST_DAY(dd.adddate),DATE_FORMAT(dd.adddate,'%Y-%m-15'))";//"FLOOR(DATEDIFF(dd.adddate,'1900-01-01')/15)";
		$halfMonthCycleSql = sprintf($baseSql,'p.settle_cycle=2',$ld,$ld,$ld,$group);
		//3,5 预收
		$ld = "LAST_DAY(dd.adddate)";
		$group = "DATE_FORMAT(dd.adddate,'%Y-%m')";
		$monthCycleSql = sprintf($baseSql,'(p.settle_cycle=3 OR p.settle_cycle=5)',$ld,$ld,$ld,$group);
		//4 季度
		$ld = "LAST_DAY(DATE_FORMAT(dd.adddate,'%Y-01-01') + INTERVAL QUARTER(dd.adddate)*3-1 MONTH)";
		$group = "concat(date_format(dd.adddate,'%Y'),floor((date_format(dd.adddate,'%m')+2)/3))";
		$seasonCycleSql = sprintf($baseSql,'p.settle_cycle=4',$ld,$ld,$ld,$group);

		//分页
		$listRow = C('LIST_ROWS');
		$p = $_GET['p'];
		if($p<1) $p=1;
		$offset = ($p-1)*$listRow;

		$model = M();
		$sql="
			($weekCycleSql) UNION ALL ($halfMonthCycleSql) UNION ALL ($monthCycleSql) UNION ALL ($seasonCycleSql) 
			{$order}
		";
		if($listRow>0)$sql.="LIMIT {$offset}, {$listRow}";
		$ret = $model->query($sql);
		$subQuery =	$model->query("select count(*) as t_num from( 
			($weekCycleSql) UNION ALL ($halfMonthCycleSql) UNION ALL ($monthCycleSql) UNION ALL ($seasonCycleSql) 
			) as tab
		");
		$this->totalPage = $subQuery[0]['t_num'];

		//列求和
		$itemTotal = $model->query("select sum(tab.notrec_money) AS notrec_money,sum(tab.notconf_money) AS notconf_money from (
			($weekCycleSql) UNION ALL ($halfMonthCycleSql) UNION ALL ($monthCycleSql) UNION ALL ($seasonCycleSql) 
			) AS tab
		");
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
		$title = array('adddate'=>'年月','ad_name'=>'广告主','bl_name'=>'业务线','pro_name'=>'产品','sb_name'=>'结算主体',
			'saler_name'=>'销售','settle_cycle'=>'结算周期','notconf_money'=>'逾期未确认','notrec_money'=>'逾期未回款');

		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '逾期监控'.date('Y-m-d H:i:s'));

	}


	//详情
	public function detail() {

		$proId = I('get.pro_id', 0, 'intval');
		if ($proId <= 0) {
			$this->error('产品id不能为空');
		}

		//产品结算周期
		$settleCycle = M('product')->where('id='.$proId)->getField('settle_cycle');
		switch ($settleCycle) {
			case 1: //按 周
				$group = "FLOOR(DATEDIFF(dd.`adddate`,'1900-01-01')/7)";
				$whereField = "DATE_SUB(dd.`adddate`,INTERVAL WEEKDAY(dd.`adddate`) - 6 DAY)";
				break;
			case 2: //按 半月
				$group = "lastdate";//"FLOOR(DATEDIFF(dd.`adddate`,'1900-01-01')/15)";
				$whereField = "IF(DAYOFMONTH(dd.adddate)>15,LAST_DAY(dd.adddate),DATE_FORMAT(dd.adddate,'%Y-%m-15'))";;
				break;
			case 3: //按 3月 /5预收
			case 5:
				$group = "DATE_FORMAT(dd.adddate,'%Y-%m')";
				$whereField = "LAST_DAY(dd.adddate)";
				break;
			case 4: //按 季度
				$group = "concat(date_format(dd.adddate,'%Y'),floor((date_format(dd.adddate,'%m')+2)/3))";
				$whereField = "LAST_DAY(DATE_FORMAT(dd.adddate,'%Y-01-01') + INTERVAL QUARTER(dd.adddate)*3-1 MONTH)";
				break;
			default:
				;
				break;
		}

		$getDate = I('get.date');

		$whereStr = sprintf("
		AND DATE_FORMAT(dd.adddate,'%%Y-%%m')='{$getDate}'
		AND (DATEDIFF(NOW(), %s) > p.bill_day = 1 
	      OR DATEDIFF(NOW(), %s) > p.receivables_day = 1 
	      OR DATEDIFF(NOW(), %s) > p.reconciliation_day = 1
	    )", $whereField,$whereField,$whereField);

		$model = M('daydata');
		$datas = $model
			->field("p.name,p.settle_cycle,p.receivables_day,bill_day,reconciliation_day,SUM(IFNULL(dd.newmoney,dd.money)) AS money,dd.`adddate`,{$whereField} AS lastdate")
			->join(" AS dd JOIN boss_product AS p ON dd.comid=p.`id`")
			->where("dd.comid={$proId} AND dd.status<5 {$whereStr}")
			->order('adddate ASC')
			->group($group)
			->page($_GET['p'],C('LIST_ROWS'))
			->select();

		$subQuery =	$model
			->field("dd.id,{$whereField} AS lastdate")
			->join(" AS dd JOIN boss_product AS p ON dd.comid=p.`id`")
			->where("dd.comid={$proId} AND dd.status<5 {$whereStr}")
			->group($group)
			->buildSql();
		$this->totalPage = $model->table($subQuery.' aaa')->where()->count();

		$dcount = count($datas);
		if ($dcount > 0) {
			//记录日期
			$end = $datas[count($datas)-1];

			//获取当前页的日期区间
			$tt = $this->showSettleDate($datas[0]['adddate'], $end['adddate'], $datas[0]['settle_cycle']);
			foreach ($datas  as &$val) {
				$adt = strtotime($val['adddate']);
				foreach($tt as $t) {
					//当前记录的结算日期区间
					if($adt >= $t[0] && $adt <= $t[1]) {
						$val['settle_date_section'] = date('Ymd',$t[0]) . ' ~ ' . date('Ymd',$t[1]);
						break;
					}

				}

				$nowTime = time();
				$lastDate = strtotime($val['lastdate']);
				//对账
				$rd = $val['reconciliation_day'];//对账周期天数
				$val['Reconciliation_date'] = strtotime("+{$rd} day", $lastDate);//对账日期
				$val['Reconciliation_overdue_day'] = floor(($nowTime-$val['Reconciliation_date'])/86400);//对账逾期天数
				//开票
				$bd = $val['bill_day'];//开票周期天数
				$val['bill_date'] = strtotime("+{$bd} day", $lastDate);//开票日期
				$val['bill_overdue_day'] = floor(($nowTime-$val['bill_date'])/86400);//开票逾期天数
				//收款
				$recd = $val['receivables_day'];//收款周期天数
				$val['receivables_date'] = strtotime("+{$recd} day", $lastDate);//收款日期
				$val['receivables_overdue_day'] = floor(($nowTime-$val['receivables_date'])/86400);//收款逾期天数

			}

		}

		$total = $this->totalPage;
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page = new \Think\Page($total, $listRows);
		if($total>$listRows){
			$page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
		}
		$p =$page->show();
		$this->assign('_page', $p? $p: '');
		$this->assign('_total',$total);
		$this->assign('list', $datas);
		$this->assign('op_settlement_cycle',C('OPTION.settlement_cycle'));

		$this->display();
	}



 /**
 * 页面显示结算日期
  * 'settlement_cycle' => array(
	 1 => '周',
	 2 => '半月',
	 3 => '月',
	 4 => '季度',
	 ),
 * @param string $startDate
 * @param string $endDate
 * @return array
 */
	private function showSettleDate($startDate='', $endDate='', $settle_cycle=1) {

		$startDateTs = strtotime($startDate);
		$endDateTs = strtotime($endDate);
		$retDate = array();
		$sub1 = '';
		do {
			if(!$sub1)$sub1=$startDateTs;
			switch ($settle_cycle) {
				case 1: //周
					$sub2 = strtotime('this Sunday', $sub1);
				break;
				case 2:  //半月
					$mdays = date('t',$sub1);//当月天数
					$jdays = date('j',$sub1);//月中第几天
					$midDay = floor($mdays/2)-1;
					$curmonth = $this->getthemonth($sub1);
					if($jdays <=$midDay){//前半月
						$sub2 = strtotime("+{$midDay} day",$curmonth[0]);
					} else { //后半月
						$sub2 = $curmonth[1];
					}
				break;
				case 3: //月
					$curmonth = $this->getthemonth($sub1);
					$sub2 = $curmonth[1];
				break;
				case 4:  //季度
					$cur = strtotime('+3 month', $sub1);
					$tt = $this->getthemonth($cur);
					$sub2 = $tt[1];
				break;
			}

			$retDate[] = array(
				$sub1,
				$sub2
			);
			$sub1 = strtotime('+1 day',$sub2);
		}while($sub2<$endDateTs);
		return $retDate;
	}

	//返回当月第一天和最后一天的时间戳
	private function getthemonth($curTime) {
		$firstday = date('Y-m-01', $curTime);
		$lastday  = strtotime("$firstday +1 month -1 day");
		$firstday = strtotime($firstday);
		return array($firstday,$lastday);
	}


	/**
	 * 报表
	 */
	public function chartView() {

		$item = I('get.item',0,'intval');
		$model = M('Product');

		$res = array();
		$fields = '';

		$baseSql = "
		  SUM(IFNULL(dd.newmoney, money)) AS notrec_money
		FROM
		  `boss_daydata` AS dd 
		  JOIN boss_product AS p 
		    ON dd.comid = p.`id` 
		  JOIN boss_advertiser ad
		    ON ad.id=dd.`adverid`  
		  JOIN boss_user e 
		    ON e.id = dd.salerid 
		  JOIN boss_business_line f 
		    ON f.`id` = dd.lineid 
		  LEFT JOIN boss_data_dic sb 
		    ON sb.id = dd.ztid
		WHERE (
		    dd.status < 5
		    AND %s 
		    AND (
		      DATEDIFF(NOW(), %s) > p.bill_day = 1 
		      OR DATEDIFF(NOW(), %s) > p.receivables_day = 1 
		      OR DATEDIFF(NOW(), %s) > p.reconciliation_day = 1
		    )
		  ) 
		
		";

		//1 周
		$ld = "DATE_SUB(dd.`adddate`,INTERVAL WEEKDAY(dd.`adddate`) - 6 DAY)";
		$weekCycleSql = 'SELECT %s,'.sprintf($baseSql,'p.settle_cycle=1',$ld,$ld,$ld)."GROUP BY %s";
		//2 半月
		$ld = "IF(DAYOFMONTH(dd.adddate)>15,LAST_DAY(dd.adddate),DATE_FORMAT(dd.adddate,'%%Y-%%m-15'))";
		$halfMonthCycleSql = 'SELECT %s,'.sprintf($baseSql,'p.settle_cycle=2',$ld,$ld,$ld)."GROUP BY %s";
		//3,5 预收
		$ld = "LAST_DAY(dd.adddate)";
		$monthCycleSql = 'SELECT %s,'.sprintf($baseSql,'(p.settle_cycle=3 OR p.settle_cycle=5)',$ld,$ld,$ld)."GROUP BY %s";
		//4 季度
		$ld = "LAST_DAY(DATE_FORMAT(dd.adddate,'%%Y-01-01') + INTERVAL QUARTER(dd.adddate)*3-1 MONTH)";
		$seasonCycleSql = 'SELECT %s,'.sprintf($baseSql,'p.settle_cycle=4',$ld,$ld,$ld)."GROUP BY %s";

		switch ($item) {
			case 1:
				//广告主top10逾期金额
				$weekCycleSql = sprintf($weekCycleSql,'dd.adverid','dd.adverid');
				$halfMonthCycleSql = sprintf($halfMonthCycleSql,'dd.adverid','dd.adverid');
				$monthCycleSql = sprintf($monthCycleSql,'dd.adverid','dd.adverid');
				$seasonCycleSql = sprintf($seasonCycleSql,'dd.adverid','dd.adverid');

				$datatype =array();
				$model = M();

				$datas = $model->query("
					($weekCycleSql) UNION ALL ($halfMonthCycleSql) UNION ALL ($monthCycleSql) UNION ALL ($seasonCycleSql) 
					ORDER BY notrec_money DESC
					LIMIT 10
				");

				foreach ($datas as $val) {
					$datatype[$val['adverid']] = round($val['notrec_money'],2);
				}

				$adidArr = array_keys($datatype);
				$adids = implode(',', $adidArr);
				$typeArr = M('advertiser')->where("id IN ({$adids})")->getField('id,name');
				break;
			case 2:
				//销售逾期金额
				$datatype =array();
				$datas	= M('daydata')
					->alias('a')
					->join(' JOIN boss_user u ON a.salerid=u.id')
					->where('a.status<5')
					->group('a.salerid')
					->order('notrec_money DESC')
					->limit(10)
					->getField('u.id AS uid,SUM(IFNULL(a.newmoney, money)) AS `notrec_money`');
				foreach ($datas as $val) {
					$datatype[$val['uid']] = round($val['notrec_money'],2);
				}
				$uidArr = array_keys($datatype);
				$uids = implode(',', $uidArr);
				$typeArr = M('user')->where("id IN ({$uids})")->getField('id,real_name as name');
				break;
			case 3:
				//每月逾期金额
				$thisyear = date('Y');
				$datatype =array();
				$datas	= M('daydata')
					->alias('a')
					->where('a.status<5 AND YEAR(a.adddate) = '.$thisyear)
					->group("DATE_FORMAT(a.adddate,'%Y-%m')")
					->order('notrec_money DESC')
					->getField('SUM(IFNULL(a.newmoney, money)) AS `notrec_money`,MONTH(a.adddate) AS `month`');
				foreach ($datas as $val) {
					$datatype[$val['month']] = round($val['notrec_money'],2);
				}
				$typeArr = array(1=>'1月',2=>'2月',3=>'3月',4=>'4月',5=>'5月',6=>'6月',7=>'7月',8=>'8月',9=>'9月',10=>'10月',11=>'11月',12=>'12月',);
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


