<?php
/**
* 凭证数据
*/
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class VoucherController extends Controller
{
	private function setSession_(){
		ignore_user_abort();//脱离客户端
		set_time_limit(0);//不限时间执行
		session_write_close();//session解锁
	}
	/**
	* 获取收入ajax数据
	* @return [type] [description]
	*/
	function getInMoneyData(){
		$this->setSession_();
		$data["start"] = I("start");
		$data["end"]   = I("end");
		$data["month"] = I("month");
		$riskSer       = new \Home\Service\RiskCheckService();
		$riskResult    = $riskSer->getRiskIncomByTimeArea($data);
		$this->ajaxReturn($riskResult);
	}

	/**
	* 获取成本数据
	* @return [type] [description]
	*/
	function getOutMoneyData(){
		$this->setSession_();
		$data["start"] = I("start");
		$data["end"]   = I("end");
		$data["month"] = I("month");
		$riskSer       = new \Home\Service\RiskCheckService();
		$riskResult    = $riskSer->getRiskOutcomByTimeArea($data);
		$this->ajaxReturn($riskResult);
	}

	/**
	 * 加载更多发票凭证
	 * @return [type] [description]
	 */
	function getMoreInvoinceData(){
		$this->setSession_();
		$invoinceNo = I("invoinceNo");
		$riskSer    = new \Home\Service\RiskCheckService();
		$riskResult = $riskSer->getMoreInvoinceDataSer($invoinceNo);
		$this->ajaxReturn($riskResult);
	}

	/**
	 * 获取当月条件总额
	 * @return [type] [description]
	 */
	function ajaxGetOutDetaiTotal(){
		$this->setSession_();
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
		$riskResult = $riskSer->getOutMoneyDataTotalByWhere($where,$map);
		$this->ajaxReturn($riskResult);
	}
	
}
 ?>