<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 财务关账报表
 * Class FinanceRiskController
 * @package Home\Controller
 */
class FinanceRiskController extends BaseController {

	protected $totalPage = 0;

	public function index() {

		$where = array();
		$list = $this->lists($this, $where);
		$this->assign('data', $list);

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

		$this->display();

	}


	//获取列表
	public function getList($where,$field) {

		$time_s=date('Y-m').'-01';
		$time_e=date('Y-m').'-31';
		if(!empty(I('get.time_s')))$time_s=I('get.time_s').'-01';
		if(!empty(I('get.time_e')))$time_e=I('get.time_e').'-31';
		if (!empty(I('get.advid'))) {
			$w = array();
			foreach (I('get.advid') as $key => $value) {
				$w[] = "bc.in_adverid=" . $value;
			}
			$wheres[] = "(" . implode(' || ', $w) . ")";
		}
		if (!empty(I('get.supid'))) {
			$w = array();
			foreach (I('get.supid') as $key => $value) {
				$w[] = "bc.out_superid=" . $value;
			}
			$wheres[] = "(" . implode(' || ', $w) . ")";
		}
		if (!empty(I('get.lineid'))) {
			$w = array();
			foreach (I('get.lineid') as $key => $value) {
				$w[] = "bc.in_lineid=" . $value . "";
			}
			$wheres[] = "(" . implode(' || ', $w) . ")";
		}
		if (!empty(I('get.comid'))) {
			$w = array();
			foreach (I('get.comid') as $key => $value) {
				$w[] = "bc.in_comid=" . $value;
			}
			$wheres[] = "(" . implode(' || ', $w) . ")";
		}
		if (!empty(I('get.jfid'))) {
			$w = array();
			foreach (I('get.jfid') as $key => $value) {
				$w[] = "bc.jfid=" . $value;
			}
			$wheres[] = "(" . implode(' || ', $w) . ")";
		}
		if (!empty(I('get.sourcetype'))) {
			$w = array();
			foreach (I('get.sourcetype') as $key => $value) {
				$w[] = "bp.source_type=" . $value;
			}
			$wheres[] = "(" . implode(' || ', $w) . ")";
		}
		if (!empty(I('get.module'))) {
			$w = array();
			foreach (I('get.module') as $key => $value) {
				$w[] = "bcl.charging_mode=" . $value;
			}
			$wheres[] = "(" . implode(' || ', $w) . ")";
		}
		if (!empty($time_s) && !empty($time_e)) $wheres[] = "bc.`adddate`>='".$time_s."' && bc.`adddate`<='".$time_e."'";

		//数据权限
        $arr_name=array();
        $arr_name['line']=array('bc.inlineid','bc.out_lineid');
        $arr_name['user']=array('bc.in_salerid','bc.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;


		if (count($wheres) > 0) $where = implode(' && ', $wheres);
		else $where = '1=1';

		$model = M('closing');
		$reData = $model->field('bc.id,bc.jfid,dic.`name` as js_name,bbl.`name` AS bl_name,out_l.`name` AS out_bl_name,bp.name AS pro_name,bcl.`name` AS jf_name,bcl.`code` as jf_code,concat(min(bc.`adddate`),"-",max(bc.`adddate`)) as date,adv.`name` AS adv_name,bu.real_name,sum(if(bc.in_status!=0 && bc.in_status!=9,bc.in_newmoney,0)) AS inmoney,dic_sup.`name` AS sup_js_name,sup.`name` AS sup_name,sup.type,sw.real_name AS sw_name,SUM(if(bc.out_status!=0 && bc.out_status!=9,bc.out_newmoney,0)) AS outmoney,bc.in_status,bc.out_addid,bc.out_status,bc.out_lineid,bc.out_addid,bc.out_superid')
			->join(' as bc
			left JOIN boss_business_line AS bbl ON bbl.id=bc.in_lineid
			LEFT JOIN boss_business_line AS out_l ON out_l.id=bc.out_lineid
			LEFT JOIN boss_product AS bp ON bp.id=bc.in_comid
			LEFT JOIN boss_charging_logo AS bcl ON bcl.id=bc.jfid
			LEFT JOIN boss_advertiser AS adv ON adv.id=bc.in_adverid
			LEFT JOIN boss_data_dic AS dic ON dic.dic_type=4 AND dic.id=bc.in_ztid
			left JOIN boss_user AS bu ON bu.id=bc.in_salerid
			LEFT JOIN boss_supplier AS sup ON sup.id=bc.out_superid
			LEFT JOIN boss_data_dic AS dic_sup ON dic_sup.dic_type=4 AND dic_sup.id=bc.out_sbid
			LEFT JOIN boss_user AS sw ON sw.id=bc.out_businessid
			')->where($where)
			->group('bc.in_adverid,bc.in_lineid,bc.jfid,bc.in_status,bc.out_status,bc.out_superid,bc.out_businessid,bc.out_lineid,bc.out_sbid')
			->page($_GET['p'], C('LIST_ROWS'))
			->select();
		//bc.in_adverid,bc.in_lineid,bc.jfid
		foreach($reData as $key =>$val){
			if(empty($val['pro_name'])){
				$prData = M('product')->field('a.name')->join('a join boss_charging_logo b on a.id=b.prot_id')->where("b.id=".$val['jfid'])->find();
				$reData[$key]['pro_name'] = $prData['name'];
			}
			if(empty($val['js_name'])){
				$reData[$key]['js_name'] =$val['sup_js_name'];
			}
			if(empty($val['bl_name'])){
				$reData[$key]['bl_name'] =$val['out_bl_name'];
			}
			if(empty($val['adv_name']) OR empty($val['real_name'])){
				$allData = M('product')->field('c.name,d.real_name')->join('a join boss_charging_logo b on a.id=b.prot_id join boss_advertiser c on c.id=a.ad_id join boss_user d on d.id=a.saler_id')->where("b.id=".$val['jfid'])->find();
				$reData[$key]['adv_name'] = $allData['name'];
				$reData[$key]['real_name'] = $allData['real_name'];
			}
			/*2017.03.02 start*/
			$addData = M('closing_data')->field('addtime')->where("yearandmonth='".I('get.time_s')."'")->find();
			$reData[$key]['addtime'] = $addData['addtime'];
			if($val['in_status'] == 0){
				$reData[$key]['in_status'] = '待审核';
			}elseif($val['in_status'] == 1){
				$reData[$key]['in_status'] = '待审核';
			}elseif($val['in_status'] == 2){
				$reData[$key]['in_status'] = '已确认';
			}elseif($val['in_status'] == 3){
				$reData[$key]['in_status'] = '待开票';
			}elseif($val['in_status'] == 4){
				$reData[$key]['in_status'] = '已开票';
			}elseif($val['in_status'] == 5){
				$reData[$key]['in_status'] = '已结清';
			}elseif($val['in_status'] == 8){
				$reData[$key]['in_status'] = '已回款但未开票';
			}else{
				$reData[$key]['in_status'] = '';
			}

			if($val['out_status'] == 1){
				$reData[$key]['out_status'] = '待审核';
			}elseif($val['out_status'] == 2){
				$reData[$key]['out_status'] = '已确认';
			}elseif($val['out_status'] == 3){
				$reData[$key]['out_status'] = '待结算';
			}elseif($val['out_status'] == 4){
				$reData[$key]['out_status'] = '已结算';
			}else{
				$reData[$key]['out_status'] = '';
			}
			if(empty($val['out_bl_name'])){//成本业务线
				$blData = M('charging_logo_assign')->field('b.id,b.name,a.sup_id')->join('a join boss_business_line b on a.bl_id=b.id')->where("cl_id=".$val['jfid'])->find();
				$reData[$key]['out_bl_name'] = $blData['name'];
				$reData[$key]['out_lineid'] = $blData['id'];
				$reData[$key]['out_superid'] = $blData['sup_id'];
			}
			if(empty($val['sup_js_name'])){//结算主体
				$blData = M('charging_logo_assign')->field('b.name')->join('a join boss_data_dic b on b.dic_type=4 AND b.id=a.sb_id')->where("cl_id=".$val['jfid'])->find();
				$reData[$key]['sup_js_name'] = $blData['name'];
			}
			if(empty($val['sw_name']) or empty($val['sup_name'])){//商务
				$blData = M('charging_logo_assign')->field('b.real_name,c.name')->join('a join boss_user b on b.id=a.business_uid join boss_supplier c on c.id=a.sup_id')->where("cl_id=".$val['jfid'])->find();
				$reData[$key]['sw_name'] = $blData['real_name'];
				$reData[$key]['sup_name'] = $blData['name'];
			}
			//获取收款方 和发票类型
			$op_invoice_type = C('OPTION.invoice_type');
			if($val['out_addid']>0 && $val['out_lineid']){
				$finData = M('supplier_finance')->field('payee_name,invoice_type')->where("sp_id=".$val['out_addid']." and bl_id=".$val['out_lineid']."")->find();

				$reData[$key]['payee_name'] = $finData['payee_name'];
				$reData[$key]['invoice_type'] = $op_invoice_type[$finData['invoice_type']];;
			}elseif( $val['type'] ==2 && $val['out_lineid']){//$val['out_addid'] == 0 &&
				$finData = M('supplier_finance')->field('payee_name,invoice_type')->where("sp_id=".$val['out_superid']." and bl_id=".$val['out_lineid']."")->find();

				$reData[$key]['payee_name'] = $finData['payee_name'];
				$reData[$key]['invoice_type'] = $op_invoice_type[$finData['invoice_type']];
			}else if($val['out_superid'] && $val['out_lineid']){

				$finData = M('supplier_finance')->field('payee_name,invoice_type')->where("sp_id=".$val['out_superid']." and bl_id=".$val['out_lineid']."")->find();
				$reData[$key]['payee_name'] = $finData['payee_name'];
				$reData[$key]['invoice_type'] = $op_invoice_type[$finData['invoice_type']];
			}
			/*2017.03.02 end*/

			/*//确认金额
			$qr_money = M('daydata')->field('round(sum(newmoney),2) as qr_money')->where("adddate>='".$time_s."' and adddate<='".$time_e."' and jfid=".$val['jfid']." and status>1 ")->find();

			if(!empty($qr_money['qr_money'])) {
				$reData[$key]['qr_money'] = $qr_money['qr_money'];
			}else{
				$reData[$key]['qr_money'] =0;
			}
			//开票金额
			$kp_money = M('daydata')->field('round(sum(newmoney),2) as kp_money')->where("adddate>='".$time_s."' and adddate<='".$time_e."' and jfid=".$val['jfid']." and status>=4 ")->find();
			if(!empty($kp_money['kp_money'])) {
				$reData[$key]['kp_money'] = $kp_money['kp_money'];
			}else{
				$reData[$key]['kp_money'] = 0;
			}
			//未开票额
			$wkp_money = $qr_money['qr_money'] - $kp_money['kp_money'];
			$reData[$key]['wkp_money'] = $wkp_money;
			//回款金额
			$hk_money = M('daydata')->field('round(sum(newmoney),2) as hk_money')->where("adddate>='".$time_s."' and adddate<='".$time_e."' and jfid=".$val['jfid']." and status=5 ")->find();
			if(!empty($hk_money['hk_money'])) {
				$reData[$key]['hk_money'] = $hk_money['hk_money'];
			}else{
				$reData[$key]['hk_money'] = 0;
			}
			//应收帐款
			$ys_money = $qr_money['qr_money'] - $hk_money['hk_money'];
			$reData[$key]['ys_money'] = $ys_money;*/

			/*//成本确认金额
			$qr_money = M('daydata_out')->field('round(sum(newmoney),2) as out_qr_money')->where("adddate>='".$time_s."' and adddate<='".$time_e."' and jfid=".$val['jfid']." and status>1 ")->find();
			if(!empty($qr_money['out_qr_money'])) {
				$reData[$key]['out_qr_money'] = $qr_money['out_qr_money'];
			}else{
				$reData[$key]['out_qr_money'] =0;
			}
			//付款金额
			$out_money = M('daydata_out')->field('round(sum(newmoney),2) as out_fk_money')->where("adddate>='".$time_s."' and adddate<='".$time_e."' and jfid=".$val['jfid']." and status=4 ")->find();
			if(!empty($out_money['out_fk_money'])) {
				$reData[$key]['out_fk_money'] = $out_money['out_fk_money'];
			}else{
				$reData[$key]['out_fk_money'] =0;
			}
			//应付帐款
			$out_money = M('daydata_out')->field('round(sum(newmoney),2) as out_yf_money')->where("adddate>='".$time_s."' and adddate<='".$time_e."' and jfid=".$val['jfid']." and status=3 ")->find();
			if(!empty($out_money['out_yf_money'])) {
				$reData[$key]['out_yf_money'] = $out_money['out_yf_money'];
			}else {
				$reData[$key]['out_yf_money'] = 0;
			}*/
		}
		$ca_ch = $model->field('bc.id')->join(' as bc
			left JOIN boss_business_line AS bbl ON bbl.id=bc.in_lineid
			LEFT JOIN boss_business_line AS out_l ON out_l.id=bc.out_lineid
			LEFT JOIN boss_product AS bp ON bp.id=bc.in_comid
			LEFT JOIN boss_charging_logo AS bcl ON bcl.id=bc.jfid
			LEFT JOIN boss_advertiser AS adv ON adv.id=bc.in_adverid
			LEFT JOIN boss_data_dic AS dic ON dic.dic_type=4 AND dic.id=bc.in_ztid
			left JOIN boss_user AS bu ON bu.id=bc.in_salerid
			LEFT JOIN boss_supplier AS sup ON sup.id=bc.out_superid
			LEFT JOIN boss_data_dic AS dic_sup ON dic_sup.dic_type=4 AND dic_sup.id=bc.out_sbid
			LEFT JOIN boss_user AS sw ON sw.id=bc.out_businessid
			')->where($where)
			->group('bc.in_adverid,bc.in_lineid,bc.jfid,bc.in_status,bc.out_status,bc.out_superid,bc.out_businessid,bc.out_lineid,bc.out_sbid')
			->buildSql();
		$this->totalPage = $model->table($ca_ch.' bc')->where()->count();
		//$this->totalPage = $model->where($where)->alias('bc')->count();

		//列求和
		/*$itemTotal = $model
			->field('
				  sum(in_newmoney) AS in_money,
				  sum(out_newmoney) AS out_money
				  ')
			->where($where)
			->alias('clo')
			->select();
		$this->assign('itemTotal', $itemTotal[0]);*/
		return $reData;
	}
	/**
	 * 导出数据
	 */
	public function export() {
		$where = array();
		C('LIST_ROWS', '');
		$list = $this->lists($this, $where);

		$title = array('js_name'=>'公司主体','bl_name'=>'业务线','pro_name'=>'产品名称','jf_name'=>'计费标识','jf_code'=>'计费标识编码','date'=>'账单期间',
			'adv_name'=>'广告主名称','real_name'=>'销售人员','inmoney'=>'关帐金额','in_status'=>'收入状态',/*'qr_money'=>'确认金额',
			'kp_money'=>'开票金额','wkp_money'=>'未开票额','hk_money'=>'回款金额','ys_money'=>'应收帐款'*/);/*'outmoney'=>'关帐时间',*/
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '收入关账月报'.date('Y-m-d H:i:s',time()));
	}

	public function outExport(){
		$where = array();
		C('LIST_ROWS', '');
		$list = $this->lists($this, $where);

		$title = array('sup_js_name'=>'公司主体','out_bl_name'=>'业务线','pro_name'=>'产品名称','jf_name'=>'计费标识','jf_code'=>'计费标识编码','date'=>'账单期间',
			'sup_name'=>'供应商','sw_name'=>'商务人员','outmoney'=>'关帐金额','payee_name'=>'收款方名称','out_status'=>'成本状态','payee_name'=>'收款方名称','invoice_type'=>'发票类型'/*'out_qr_money'=>'确认金额',
			'out_fk_money'=>'付款金额','out_yf_money'=>'应付金额'*/);/*'outmoney'=>'关帐时间',*/
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '成本关账月报'.date('Y-m-d H:i:s',time()));
	}
}


