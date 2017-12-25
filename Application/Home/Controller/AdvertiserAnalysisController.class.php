<?php
namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 广告主分析
 * Class AdvertiserAnalysisController
 * @package Home\Controller
 */
class AdvertiserAnalysisController extends BaseController {
	//累计
	public function index(){
		$list = D('Advertiser')->getAll();
		//print_r($list);exit;
		$this->assign('list', $list);
		$t1=date('m.1');//本月1号
		$t2=date("m.d");//当前日期
		$t3=date("m.1",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月1号
		$t4=date("m.d",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月今天
		$t5=date("m.d",strtotime(date('Y').'-'.date("m").'-0'));//上月末
		$t6=date('Y-m-d',time()-3600*24*7);//7天前
		$t7=date('Y-m-d',time()-3600*24*30);//30天前
		$t8=date("Y-01-01");
		$t9=date("Y-m-d");
		$this->date_arr=array($t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9);
		$this->display();
	}

	//占比分析
	public function proportion(){
		//print_r(D('Advertiser')->proportion());
		$this->ajaxReturn(D('Advertiser')->proportion());
	}
	//趋势分析
	public function trend(){
		//print_r(D('Advertiser')->trend());exit;
		$this->ajaxReturn(D('Advertiser')->trend());
	}
	//广告主区域分布
	public function region(){
		var_dump(D('Advertiser')->region());
	}
	//覆盖行业Top10
	public function coverTop10(){
		$this->ajaxReturn(D('Advertiser')->coverTop10());
	}

	public function getAciId(){//获取广告主id
		$this->ajaxReturn(D('Advertiser')->getAciId());
	}

	public function getSupId(){//获取供应商id
		$this->ajaxReturn(D('Supplier')->getSupId());
	}
	/**
	 * 产品分析
	 * 2017.02.21
	 */
	//产品分析table数据
	public function productTable(){
		$this->ajaxReturn(D('Product')->productTable());
	}

	//占比分析
	public function productProportion(){
		$this->ajaxReturn(D('Product')->productProportion());
	}
	//覆盖行业Top10
	public function productCoverTop10(){
		$this->ajaxReturn(D('Product')->productCoverTop10());
	}

	/**
	 * 供应商分析
	 * 2017.02.21
	 */
	public function supTable(){
		$this->ajaxReturn(D('Supplier')->supTable());
	}
	//占比分析
	public function supProportion(){
		$this->ajaxReturn(D('Supplier')->supProportion());
	}
	//趋势分析
	public function supTrend(){
		$this->ajaxReturn(D('Supplier')->supTrend());
	}
	//区域分布
	public function supRegion(){
		$this->ajaxReturn(D('Supplier')->supRegion());
	}

	public function supplier(){//供应商
		$t1=date('m.1');//本月1号
		$t2=date("m.d");//当前日期
		$t3=date("m.1",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月1号
		$t4=date("m.d",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月今天
		$t5=date("m.d",strtotime(date('Y').'-'.date("m").'-0'));//上月末
		$t6=date('Y-m-d',time()-3600*24*7);//7天前
		$t7=date('Y-m-d',time()-3600*24*30);//30天前
		$t8=date("Y-01-01");
		$t9=date("Y-m-d");
		$this->date_arr=array($t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9);
		$this->display();
	}
	public function product(){//产品
		$t1=date('m.1');//本月1号
		$t2=date("m.d");//当前日期
		$t3=date("m.1",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月1号
		$t4=date("m.d",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月今天
		$t5=date("m.d",strtotime(date('Y').'-'.date("m").'-0'));//上月末
		$t6=date('Y-m-d',time()-3600*24*7);//7天前
		$t7=date('Y-m-d',time()-3600*24*30);//30天前
		$t8=date("Y-01-01");
		$t9=date("Y-m-d");
		$this->date_arr=array($t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9);
		$this->display();
	}
}