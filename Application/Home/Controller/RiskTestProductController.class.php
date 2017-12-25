<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 逾期测试产品 2017.06.23
 * Class RiskTestProductController
 * @package Home\Controller
 */
class RiskTestProductController extends BaseController {
	protected $totalPage = 0;

	public function index() {

		$where = array();
		$list = $this->lists($this, $where);

		$this->assign('op_order_test_type',C('OPTION.order_test_type'));
		$this->assign('list', $list);
		$this->display();

	}


	//获取列表
	/**
	 * @param $where
	 * @param $field
	 * @return mixed
	 * 合作状态-测试类型
		'order_test_type' => array(
		1 => '时间',
		2 => '量级',
		3 => '金额',
		),
	 */
	public function getList($where, $field) {

		$where['c.cooperate_state'] = 2;//产品合作状态为测试

		$adIds = I('get.adid','');
		if(!empty($adIds)) {
			$adArr = M('advertiser')->where("id IN ({$adIds})")->field('id,name')->select();
			$where['d.id'] = array('in', $adIds);
		}
		$proIds = I('get.proid','');
		if(!empty($proIds)) {
			$proArr = M('product')->where("id IN ({$proIds})")->field('id,name')->select();
			$where['c.id'] = array('in', $proIds);
		}
		$salerIds = I('get.salerid','');
		if(!empty($salerIds)) {
			$salerArr = M('user')->where("id IN ({$salerIds})")->field('id,real_name AS name')->select();
			$where['a.salerid'] = array('in', $salerIds);
		}
		$testType = I('get.order_test_type','');
		if(!empty($testType)) {
			$where['c.order_test_type'] = $testType;
		}

		$map['procode'] = trim(I("procode"));
        $this->assign("map",$map);

        if(!empty($map['procode'])) {
			$proArr = M('product')->where("code like '%".$map['procode']."%'")->field('id,name')->select();
			if(I("showsql")=="showsql023"){
				print_r(M('product')->getLastSql());exit;
			}
			// $where['c.id'] = array('in', $proIds);
			// $proArr = M('product')->where("id IN ({$proIds})")->field('id,name')->select();
			$where['c.id'] = array('in', $proIds);
		}

		$testStatus = I('get.test_status','');
		$whereTestStatus = 'test_status>0';
		/*$whereTestStatus = '';
		if(!empty($testStatus)) {
			if ($testStatus == 1) { //到期
				$whereTestStatus = 'test_status=1';
			} else{ //未到期
				$whereTestStatus = 'test_status=0 OR test_status IS NULL';
			}
		}*/
		$date = I('get.date','');
		$date = empty($date) ? date('Y-m') : $date;
		$where['_string'] = "a.adddate>=c.laststoptime";//DATE_FORMAT(f.sdate, '%Y-%m') >= '{$date}' &&

		//数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where['_string'] .= " && $myrule_data";

		$this->assign('adNames', empty($adArr)?'[]':json_encode($adArr,JSON_UNESCAPED_UNICODE));//
		$this->assign('proNames', empty($proArr)?'[]':json_encode($proArr,JSON_UNESCAPED_UNICODE));
		$this->assign('salerNames', empty($salerArr)?'[]':json_encode($salerArr,JSON_UNESCAPED_UNICODE));
		$this->assign('op_test_status', array(1=>'到期',2=>'未到期'));

		$model = M('daydata');
		$ret = $model
			->field('
				  c.id AS pro_id,
				  a.id,
				  f.sdate AS start_time,
				  IFNULL(SUM(IFNULL(a.newdata, a.datanum)),0) AS datanum,
				  sum(if(a.status<9,a.newmoney,0)) AS money,
				  sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,
				  c.order_test_type,
				  c.order_test_quota,
				  c.name AS pro_name,
				  c.code AS pro_code,
				  d.name AS ad_name,
				  e.real_name AS saler_name,
				  CASE order_test_type 
					WHEN 1 THEN DATEDIFF(NOW(),f.sdate) - order_test_quota
					WHEN 2 THEN SUM(IFNULL(a.newdata,a.datanum)) - order_test_quota
					WHEN 3 THEN SUM(IFNULL(a.newmoney,a.money)) - order_test_quota
				  END AS test_status')
			->join(' a 
				  JOIN boss_product c 
				    ON a.comid = c.id 
				  JOIN boss_advertiser d 
				    ON c.ad_id = d.id 
				  LEFT JOIN boss_user e 
				    ON e.id = a.salerid 
				  JOIN (SELECT comid,a.adddate AS sdate FROM `boss_daydata` a join boss_product b on a.comid=b.id where  b.cooperate_state =2 GROUP BY a.comid) f ON f.comid=c.id')
			->where($where)
			->page($_GET['p'], C('LIST_ROWS'))
			->order('sum(if(a.status>0 && a.status<9,a.newmoney,0)) DESC')
			->group('a.comid')
			->having($whereTestStatus)
			->select();
			//a.adddate>=b.laststoptime &&

			if(I("showsql")=="showsql023"){
				$model->getLastSql();exit;
			}

//P($model->getLastSql(),true);

		foreach ($ret as &$val) {
			$val['datanum'] = round($val['datanum'], 2);
			$val['money'] = round($val['money'], 2);

			/*$quota = $val['order_test_quota'];//测试指标
			$testStatus = 1; //1到期0未到期
			switch ($val['order_test_type']) { //测试类型
				case 1:
					$quota = intval($quota);
					$testStatus = NOW_TIME >= strtotime("+$quota day", $val['pro_add_time']) ? 0 : 1;
					break;
				case 2:
					$quota = floatval($quota);
					$testStatus = $quota >= $val['datanum'] ? 0 : 1;
					break;
				case 3:
					$quota = floatval($quota);
					$testStatus = $quota >= $val['money'] ? 0 : 1;
					break;
			}
			$val['test_status'] = $testStatus; //是否达标*/
		}

		$subQuery =	$model
			->field('
				  c.id AS pro_id,
				  a.id,
				  f.sdate AS start_time,
				  IFNULL(SUM(IFNULL(a.newdata, a.datanum)),0) AS datanum,
				  IFNULL(SUM(IFNULL(a.newmoney, a.money)),0) AS money,
				  sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,
				  c.order_test_type,
				  c.order_test_quota,
				  c.name AS pro_name,
				  c.code AS pro_code,
				  d.name AS ad_name,
				  e.real_name AS saler_name,
				  CASE order_test_type
				    WHEN 1 THEN DATEDIFF(NOW(),f.sdate) - order_test_quota
					WHEN 2 THEN SUM(IFNULL(a.newdata,a.datanum)) - order_test_quota
					WHEN 3 THEN SUM(IFNULL(a.newmoney,a.money)) - order_test_quota
				  END AS test_status')
			->join(' a
				  JOIN boss_product c
				    ON a.comid = c.id
				  JOIN boss_advertiser d
				    ON c.ad_id = d.id
				  LEFT JOIN boss_user e
				    ON e.id = a.salerid
				  JOIN (SELECT comid,a.adddate AS sdate FROM `boss_daydata` a join boss_product b on a.comid=b.id where b.cooperate_state =2 GROUP BY a.comid) f ON f.comid=c.id')
			->where($where)
			->group('a.comid')
			->having($whereTestStatus)
			->buildSql();//a.adddate>=b.laststoptime &&

		$this->totalPage = $count_all = $model->table($subQuery.' aa')->where()->count();
		$this->assign('count_all',$count_all);
		//列求和
		$itemTotal = $model
			->field('
				  sum(if(a.status>0 && a.status<9,a.newmoney,0)) AS money,
				  sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,
				  a.id,
				  CASE order_test_type 
				    WHEN 1 THEN DATEDIFF(NOW(),f.sdate) >= order_test_quota
				    WHEN 2 THEN IFNULL(SUM(IFNULL(a.newdata, a.datanum)),0) >= order_test_quota
				    WHEN 3 THEN IFNULL(SUM(IFNULL(a.newmoney, a.money)),0) >= order_test_quota
				  END AS test_status
				  ')
			->join(' a 
				  JOIN boss_product c 
				    ON a.comid = c.id 
				  JOIN boss_advertiser d 
				    ON c.ad_id = d.id 
				  LEFT JOIN boss_user e 
				    ON e.id = a.salerid 
				  JOIN (SELECT comid,MIN(a.adddate) AS sdate FROM `boss_daydata` a join boss_product b on a.comid=b.id where a.adddate>=b.laststoptime && b.cooperate_state =2 GROUP BY a.comid) f ON f.comid=c.id ')
			->where($where)
			->having($whereTestStatus)
			->select();
		$this->assign('itemTotal', $itemTotal[0]);
		return $ret;
	}


	/**
	 * 导出数据
	 */
	public function export() {

		$where = array();
		C('LIST_ROWS', ''); //不分页
		$data = $this->lists($this, $where);
		foreach ($data as $key => $value) {
			if($value['test_status']>=0)$data[$key]['test_s']='到期';
			else $data[$key]['test_s']='未到期';
		}
		$list=array(array('ad_name','广告主'),array('pro_name','产品'),array('saler_name','销售'),array('order_test_type','测试类型'),array('order_test_quota','指标'),array('start_time','开始时间'),array('datanum','数据'),array('money','金额'),array('test_s','测试状态'),array('test_status','超出量'));
        $this->downloadlist($data,$list,'逾期测试产品');

	}

}


