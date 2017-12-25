<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 封禁数据
 * Class RiskBanDataController
 * @package Home\Controller
 */
class RiskBanDataController extends BaseController {

	public $totalPage = 0;

    public function index() {

    	$where = array();
    	$list = $this->lists($this, $where);
	    $this->assign('list', $list);
	    $this->assign('op_charging_mode', C('OPTION.charging_mode'));
	    $this->display();

    }


    //获取列表
    public function getList($where, $field) {

	    $where['a.status'] = 9;//封禁状态

	    $adIds = I('get.adid','');
	    if(!empty($adIds)) {
		    $adArr = M('advertiser')->where("id IN ({$adIds})")->field('id,name')->select();
		    $where['d.id'] = array('in',$adIds);
	    }
	    $proIds = I('get.proid','');
	    if(!empty($proIds)) {
		    $proArr = M('product')->where("id IN ({$proIds})")->field('id,name')->select();
		    $where['c.id'] = array('in',$proIds);
	    }
	    $clIds = I('get.clid','');
	    if(!empty($clIds)) {
		    $clArr = M('charging_logo')->where("id IN ({$clIds})")->field('id,name')->select();
		    $where['a.jfid'] = array('in',$clIds);
	    }
	    $salerIds = I('get.saler','');
	    if(!empty($salerIds)) {
		    $salerArr = M('user')->where("id IN ({$salerIds})")->field('id,real_name')->select();
		    $where['a.salerid'] = array('in',$salerIds);
	    }
	    $blIds = I('get.bl','');
	    if(!empty($blIds)) {
		    $blArr = M('business_line')->where("id IN ({$blIds})")->field('id,name')->select();
		    $where['a.lineid'] = array('in',$blIds);
	    }

	    $date = I('get.date','');
	    $date = empty($date) ? date('Y-m') : $date;
	    $where['_string'] = "DATE_FORMAT(a.adddate,'%Y-%m')='{$date}'";
	    //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where['_string'] .= " && $myrule_data";


	    $this->assign('adNames', empty($adArr)?'[]':json_encode($adArr,JSON_UNESCAPED_UNICODE));//
	    $this->assign('proNames', empty($proArr)?'[]':json_encode($proArr,JSON_UNESCAPED_UNICODE));
	    $this->assign('clNames', empty($clArr)?'[]':json_encode($clArr,JSON_UNESCAPED_UNICODE));//

	    $this->assign('salerNames', empty($salerArr)?'[]':json_encode($salerArr,JSON_UNESCAPED_UNICODE));//
	    $this->assign('blNames', empty($blArr)?'[]':json_encode($blArr,JSON_UNESCAPED_UNICODE));

	    $model = M('daydata');
	    $ret = $model
		    ->field('a.id,
		          a.banimgpath,  
				  a.adddate,
				  a.jfid,
				  IFNULL(a.newdata, a.datanum) AS datanum,
				  IFNULL(a.newmoney, money) AS money,
				  cl.name AS jfname,
				  a.price,
				  cl.charging_mode AS jftype,
				  c.name AS pro_name,
				  d.name AS ad_name,
				  e.real_name AS saler_name,
				  f.`name` AS bl_name')
		    ->alias('a')
		    ->join('
	              JOIN boss_charging_logo cl
			        ON cl.id=a.jfid
				  JOIN boss_product c 
				    ON cl.prot_id = c.id 
				  JOIN boss_advertiser d 
				    ON cl.ad_id = d.id 
				  JOIN boss_user e
				    ON e.id=a.salerid
				  JOIN boss_business_line f
				    ON f.`id`=a.lineid')
		    ->order('a.id DESC')
		    ->where($where)->page($_GET['p'], C('LIST_ROWS'))->select();

	    $this->totalPage = $model
		    ->alias('a')
		    ->join('
	              JOIN boss_charging_logo cl
			        ON cl.id=a.jfid
				  JOIN boss_product c 
				    ON cl.prot_id = c.id 
				  JOIN boss_advertiser d 
				    ON cl.ad_id = d.id 
				  JOIN boss_user e
				    ON e.id=a.salerid
				  JOIN boss_business_line f
				    ON f.`id`=a.lineid')
		    ->where($where)->count();

	    //列求和
	    $itemTotal = $model
		    ->field('
				  sum(IFNULL(a.newdata, a.datanum)) AS datanum,
				  sum(IFNULL(a.newmoney, money)) AS money
				  ')
		    ->alias('a')
		    ->join('
	              JOIN boss_charging_logo cl
			        ON cl.id=a.jfid
				  JOIN boss_product c 
				    ON cl.prot_id = c.id 
				  JOIN boss_advertiser d 
				    ON cl.ad_id = d.id 
				  JOIN boss_user e
				    ON e.id=a.salerid
				  JOIN boss_business_line f
				    ON f.`id`=a.lineid
				  ')
		    ->where($where)
		    ->group("DATE_FORMAT(a.adddate, '%Y-%m')")
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
		$title = array('adddate'=>'日期','jfid'=>'计费标识ID','jfname'=>'计费标识名称','price'=>'单价','jftype'=>'计费模式',
			'datanum'=>'有效数据','money'=>'金额','xx'=>'依据','pro_name'=>'产品名称','ad_name'=>'广告主名称','bl_name'=>'业务线',
			'saler_name'=>'所属销售');
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '封禁数据'.date('Y-m-d-H:i:s'));

	}

}


