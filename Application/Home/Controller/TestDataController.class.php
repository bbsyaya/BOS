<?php
namespace Home\Controller;
use Common\Controller\BaseController;

class TestDataController extends BaseController {

	public function _initialize(){
		ini_set ('memory_limit', '128M');
	}


	public function index() {


		$ddlist = M('daydata')->limit(20000)->select();
		$dolist = M('daydata_out')->limit(10000)->select();
		foreach ($ddlist as $v1) {
			foreach ($dolist as $v2) {
				echo $v2['id'] . '<br/>';
            }
		}

	}


	public function advertiser() {

		$adOld = M("advertisers_company_info",null);
		$newtab = M("advertiser");
		$regionModel = M('Region');

		$oldData = $adOld->select();

		foreach ($oldData as $val) {
			$newcol['id'] = $val['aci_id'];
			$newcol['ad_code'] = $val['coding'];
			$newcol['ad_type'] = $val['type'] == 1 ? 2 :1;
			$newcol['name'] = $val['name'];
			$newcol['email'] = $val['email'] ? $val['email'] : '';
			$newcol['address'] = $val['address'] ? $val['address'] : '';

			$newcol['province_id'] = $regionModel->where("name like '%{$val['provice_text']}%'")->getfield('id');
			$newcol['province_id'] = empty($newcol['province_id']) ? 0 : $newcol['province_id'];
			if ($newcol['province_id'] > 0) {
				$newcol['city_id'] = $regionModel->where("name like '%{$val['city_text']}%' and pid={$newcol['province_id']}")->getfield('id');
				$newcol['city_id'] = empty($newcol['city_id']) ? 0 : $newcol['city_id'];
				if ($newcol['city_id'] > 0) {
					$newcol['district_id'] = $regionModel->where("name like '%{$val['county_text']}%' and pid={$newcol['city_id']}")->getfield('id');
					$newcol['district_id'] = empty($newcol['district_id']) ? 0 : $newcol['district_id'];
				}
			}
			$newcol['website'] = $val['website'] ? $val['website'] : '';
			//$newcol['is_internal'] = $val['type_label'] == 1 ? 0 : 1;
			$newcol['add_time'] = $val['addtime'];
			$newcol['status'] = $val['status'];
//			P($newcol,true);
			$newtab->add($newcol);
			unset($newcol);
		}

		echo 'ok';

	}


	//财务收件人
	public function adFinanReceiver() {

		$newTab = M('advertiser_fireceiver');
		$fiinfotab = M('financial_information1',null);
		$fiinvoicetab = M('financial_invoice1',null);

		$oldData = $fiinfotab->where("PARENT_AUTH ='advertisers_company_info'")->select();

		foreach ($oldData as $val) {

			$invoice = $fiinvoicetab->where("fi_id=".$val['fi_id'])->find();
			if (!empty($invoice)) {
				$newcol['id'] = $invoice['fin_id'];
				$newcol['ad_id'] = $val['parent_id'];
				$newcol['name'] = empty($invoice['fin_name']) ? '' : $invoice['fin_name'];
				$newcol['mobile'] = empty($invoice['fin_telephone']) ? '' : $invoice['fin_telephone'];
				$newcol['address'] = empty($invoice['fin_address']) ? '' : $invoice['fin_address'];
				$newTab->add($newcol);
				unset($newcol);
			}

		}
		echo 'ok';

	}


	public function product() {

		$adOld = M("sales_order_info",null);
		$newtab = M("product");
		$oldData = $adOld->where('status <> 2')->select(); //过滤未通过的
		/*19 3%增值税普通发票
20 6%增值税专用发票
21 9%增值税专用发票
34 不开发票*/
		$intypeArr = array(
			34=>1 ,
			19=>2,
			20=>3,
			21=>4,
			0=>5,
		);

		foreach ($oldData as $val) {
			$newcol['id'] = $val['soid'];
			$newcol['code'] = $val['coding'];
			$newcol['name'] = $val['pr_name'];
			$ptArr = array(
				7=>	1,// => 'PC软件',
				8=>	2,// => '导航',
				9=>	3,// => 'APP',
				10=>4,// => '流量',
				11=>5,// => 'WEB',
				12=>6,// => '页游',
				13=>7,// => '手游',
				14=>8,// => '手机增值业务'
			);
			$newcol['type'] = $ptArr[$val['pmi_type']];
			$newcol['category'] = mt_rand(50,59);
			$newcol['ad_id'] = $val['aci_id'];
			$newcol['sb_id'] = $val['sb_id'];
			$newcol['bl_id'] = $val['pl_id'];
			//17官方 18代理
			$stArr = array(
				17 => 1,
				18 => 2,
				0  => 2,
			);
			$newcol['source_type'] = $stArr[$val['pr_sourcetype']];

			$newcol['saler_id'] = $val['so_salesperson'];
			if (trim($val['access']) == '正式上量') {
				$newcol['cooperate_state'] = 1;
			} else if (trim($val['access']) == '测试'){
				$newcol['cooperate_state'] = 2;
			} else {
				$newcol['cooperate_state'] = 0;//默认值
			}

			if ($val['test_name'] == '时间') {
				$newcol['order_test_type'] = 1;
			} else if ($val['test_name'] == '量级') {
				$newcol['order_test_type'] = 2;
			} else if ($val['test_name'] == '金额') {
				$newcol['order_test_type'] = 3;
			}

			//quota_time 指标天数  test_indicators '指标（量级或者金额)'
			if ($newcol['order_test_type'] == 1) {
				$newcol['order_test_quota'] = $val['quota_time'];
			} else {
				$newcol['order_test_quota'] = $val['test_indicators'];
			}

			$newcol['contract_num'] = $val['c_no'];
			$contractInfo = M('flow_data_434')->field('data_110,data_111')->where('run_id='.$newcol['contract_num'])->find();
			if (!empty($contractInfo) && $newcol['cooperate_state'] == 1) {
				$newcol['contract_s_duration'] = $contractInfo['data_110'];
				$newcol['contract_e_duration'] = $contractInfo['data_111'];
			} else {
				$newcol['contract_s_duration'] = null;
				$newcol['contract_e_duration'] = null;
			}
			$newcol['confirm_time'] = $val['confirm_time'];
			$otArr = array(
				2=>1,1=>2,0=>1
			);
			$newcol['order_type'] = $otArr[$val['so_type']];


			$newcol['invoice_type'] = $intypeArr[$val['invoice_type']];

			$retcArr = array(
				22=> 1,// => '次日',
				23=> 2,// => '次周',
				24=> 3,// => '次月',
				//25=> ?,//todo:
			);
			$newcol['return_cycle'] = $retcArr[$val['data_cycle']];
			$scArr = array(
				26 => 1,
				27 => 2,
				28 => 3,
				29 => 3,
				30 => 5,
			);
			$newcol['settle_cycle'] = $scArr[$val['billing_cycle']];
			$newcol['reconciliation_day'] = $val['reconciliation_time'];
			$newcol['bill_day'] = $val['billing_time'];
			$newcol['receivables_day'] = $val['receivables_time'];
			/*计费模式
			2 cpa
			3 cpc
			4 cpm
			5 cpt
			6 cps
			*/
			$cmArr = array(
				2=>	1 ,// 'CPA',
				3=> 2,// 'CPC',
				4=>	3 ,// 'CPM',
				6=>	4 ,// 'CPS',
				5=> 5,// => 'CPT',
				0=>	6,// => 'CPD',
			);
			$newcol['charging_mode'] = $cmArr[$val['billing_model']];
			empty($newcol['charging_mode']) && $newcol['charging_mode'] = 0;

			$newcol['price_type'] = $val['price_type'];
			if (!empty($val['scope_num']) && $val['price_type'] == 32) {
				$newcol['price_type'] = 2;
				$_tmp = array();

				$val['scope_num'] = explode(',',$val['scope_num']);
				$val['scope_price'] = explode(',',$val['scope_price']);
				$count = count($val['scope_num']);
				for ($i=0;$i<$count;$i++) {
					if ($i == ($count-1)) {
						$_tmp[] = '+,' . $val['scope_price'][$i];
					} else {
						$num = $val['scope_num'][$i];
						$_tmp[] = $num . ',' . $val['scope_price'][$i];
					}

				}
				$price = tieredprice_encode($_tmp);
			} else {
				if($val['price_type'] == 31)
					$newcol['price_type'] =1;
				else if ($val['price_type'] == 33) {
					$newcol['price_type'] =3;
				}

				$price = $val['op_price'];
			}

			$newcol['price'] = $price;
			$newcol['package_size'] = '';
			$newcol['package_return_type'] = $val['back_way'];
			$newcol['package_return_email'] = $val['email_address'];
			$newcol['package_return_account'] = $val['host_account_number'];
			$newcol['package_return_passwd'] = $val['host_password'];
			$newcol['quality_requirements'] = $val['quality_requirements'];
			$newcol['qr_check_rule'] = $val['subtract_rule'];
			$newcol['qr_check_remark'] = $val['check_remarks'];
			$newcol['info_owner'] = '';
			$newcol['add_time'] = date('Y-m-d');//$val['addtime'];
			$newcol['is_check'] = 0;
			$newcol['status'] = $val['status'];

			$newtab->add($newcol);
			unset($newcol);
		}

		echo 'ok';

	}


    //计费标识
	public function chargingLogo() {

		$old_cl_tab = M("charging_logo_old",null);
		$new_cl_tab = M("charging_logo");

		//分批次导入
		$oldData = $old_cl_tab->where('cl_id>21406')->limit(9000)->select();

		foreach ($oldData as $val) {

			$newcol['id'] = $val['cl_id'];
			$newcol['code'] = $val['coding'];
			$newcol['name'] = $val['charging_logo'];
			$newcol['prot_id'] = $val['soid'];
			$newcol['ad_id'] = M('product')->where('id='.$newcol['prot_id'])->getField('ad_id');
			empty($newcol['ad_id']) && $newcol['ad_id'] = 0;

			if (!empty($val['scope_num']) && $val['price_type'] == 32) {
				$newcol['price_type'] = 2;
				$_tmp = array();

				$val['scope_num'] = explode(',',$val['scope_num']);
				$val['scope_price'] = explode(',',$val['scope_price']);
				$count = count($val['scope_num']);
				for ($i=0;$i<$count;$i++) {
					if ($i == ($count-1)) {
						$_tmp[] = '+,' . $val['scope_price'][$i];
					} else {
						$num = $val['scope_num'][$i];
						$_tmp[] = $num . ',' . $val['scope_price'][$i];
					}

				}
				$price = tieredprice_encode($_tmp);
			} else {
				if($val['price_type'] == 31)
					$newcol['price_type'] =1;
				else if ($val['price_type'] == 33) {
					$newcol['price_type'] =3;
				}

				$price = $val['op_price'];
			}

			$newcol['price'] = $price;
			$newcol['promotion_url'] = $val['promotion_address'];
			$newcol['back_url'] = '';

			/*计费模式
			2 cpa
			3 cpc
			4 cpm
			5 cpt
			6 cps
			*/
			$cmArr = array(
				2=>	1 ,// 'CPA',
				3=> 2,// 'CPC',
				4=>	3 ,// 'CPM',
				6=>	4 ,// 'CPS',
				5=> 5,// => 'CPT',
				0=>	6,// => 'CPD',
			);

			$newcol['charging_mode'] = $cmArr[$val['billing_model']];
			empty($newcol['charging_mode']) && $newcol['charging_mode'] = 0;

			$newcol['account'] = '';
			$newcol['password'] = '';
			$newcol['is_check'] = 0;
			$newcol['add_time'] = null;
			$newcol['status'] = $val['status'];

			$new_cl_tab->add($newcol);
			unset($newcol);
		}
		echo 'last id :' . $val['cl_id'];

	}


	public function clDetail() {

		$old_cldetail_tab = M("nclid2spu",null);
		$new_cldetail_tab = M("charging_logo_assign");

		$oldData = $old_cldetail_tab->select();

		$sbIdArr = M('business_line')->getField('id,sb_id');
		foreach ($oldData as $val) {
			$newcol['id'] = $val['id'];
			$newcol['code'] = '';
			$newcol['cl_id'] = $val['cl_id'];
			$newcol['bl_id'] = $val['plm_id'];
			$newcol['sup_id'] = $val['sp_uid'];
			$newcol['sb_id'] = $sbIdArr[$newcol['bl_id']];
			$scArr = array(
				26 => 1,
				27 => 2,
				28 => 3,
				29 => 3,
				30 => 5,
			);
			$newcol['settlement_cycle'] = $scArr[$val['billing_cycle']]; //TODO:
			$newcol['business_uid'] = $val['sw_id'];
			$newcol['promotion_price_type'] = $val['price_type'];

			if (!empty($val['scope_num']) && $val['price_type'] == 32) {
				$newcol['price_type'] = 2;
				$_tmp = array();

				$val['scope_num'] = explode(',',$val['scope_num']);
				$val['scope_price'] = explode(',',$val['scope_price']);
				$count = count($val['scope_num']);
				for ($i=0;$i<$count;$i++) {
					if ($i == ($count-1)) {
						$_tmp[] = '+,' . $val['scope_price'][$i];
					} else {
						$num = $val['scope_num'][$i];
						$_tmp[] = $num . ',' . $val['scope_price'][$i];
					}

				}
				$price = tieredprice_encode($_tmp);
			} else {
				if($val['price_type'] == 31)
					$newcol['price_type'] =1;
				else if ($val['price_type'] == 33) {
					$newcol['price_type'] =3;
				}

				$price = $val['op_price'];
			}

			$newcol['promotion_price'] = $price;

			$retcArr = array(
				22=> 1,// => '次日',
				23=> 2,// => '次周',
				24=> 3,// => '次月',
				//25=> ?,//TODO:
			);
			$newcol['return_cycle'] = $retcArr[$val['data_cycle']];
			/*计费模式
			2 cpa
			3 cpc
			4 cpm
			5 cpt
			6 cps
			*/
			$cmArr = array(
				2=>	1 ,// 'CPA',
				3=> 2,// 'CPC',
				4=>	3 ,// 'CPM',
				6=>	4 ,// 'CPS',
				5=> 5,// => 'CPT',
				0=>	6,// => 'CPD',
			);
			$newcol['charging_mode'] = $cmArr[$val['billing_model']];
			empty($newcol['charging_mode']) && $newcol['charging_mode'] = 0;

			$newcol['deduction_ratio'] = $val['nuclear_inspection'];
			$newcol['in_settlement_prate'] = 0;

			if (intval($val['startdate']) > 0) {
				$newcol['promotion_stime'] = substr($val['startdate'],0,4).'-'.substr($val['startdate'],4,2).'-'.substr($val['startdate'],6,2);
			}
			if (intval($val['enddate']) > 0) {
				$newcol['promotion_etime'] = substr($val['enddate'],0,4).'-'.substr($val['enddate'],4,2).'-'.substr($val['enddate'],6,2);
			}

			//4禁用不得分配，2已回收，1已使用，0未使用
			//状态(0已停止1使用中2未分配3已回收)
			$_st = array(
				4 => 0,
				2 => 3,
				1 => 1,
				0 => 2,
			);
			$newcol['status'] = $_st[$val['c_status']];

			$new_cldetail_tab->add($newcol);
			unset($newcol,$_st);
		}

		echo 'ok';

	}


	//供应商
	public function supplier() {

		$adOld = M("new_supplier_info",null);
		$newtab = M("supplier");
		$regionModel = M('Region');

		$oldData = $adOld->where('id>1750')->select();
		foreach ($oldData as $val) {
			$newcol['id'] = $val['id'];
			$newcol['code'] = empty($val['coding']) ? '' : $val['coding'];
			$newcol['name'] = $val['name'];
			$newcol['email'] = $val['email'];
			$newcol['type'] = $val['type'];
			$newcol['mobile'] = empty($val['telephone']) ? '' : $val['telephone'];

			$newcol['province_id'] = $regionModel->where("name like '%{$val['provice_text']}%'")->getfield('id');
			$newcol['region'] = empty($newcol['province_id']) ? 0 : $newcol['province_id'];

			$newcol['address'] = empty($val['address']) ? '' : $val['address'];
			$newcol['contract_num'] = '';
			$newcol['is_check'] = 0;
			$newcol['add_time'] = $val['add_time'];
			$newcol['status'] = $val['status'];

//			p($newcol,true);
			$newtab->add($newcol);
			unset($newcol);
		}
		echo 'ok';
	}


	//财务
	function finance () {

/*		UPDATE financial_information1 SET FI_INVOICETYPE='17%增值税专用发票' WHERE FI_INVOICETYPE='增值税专用发票17%'
UPDATE financial_information1 SET FI_INVOICETYPE='3%增值税普通发票' WHERE FI_INVOICETYPE='增值税专用发票3%'
UPDATE financial_information1 SET FI_INVOICETYPE='6%增值税专用发票' WHERE FI_INVOICETYPE='增值税专用发票6%'
UPDATE financial_information1 SET FI_INVOICETYPE='3%增值税普通发票' WHERE FI_INVOICETYPE='增值税普通发票'
UPDATE financial_information1 SET FI_INVOICETYPE='无发票' WHERE FI_INVOICETYPE=''
UPDATE financial_information1 SET FI_INVOICETYPE='无发票' WHERE FI_INVOICETYPE='无发票 6%'*/

		$adTab = M('advertiser');
		$supTab = M('supplier_finance');
		$oldtab = M('financial_information1',null);

		$oldData = $oldtab->select();
		$invoice_type = array(
			'无发票'=>1 ,
			'3%增值税普通发票'=>2,
			'6%增值税专用发票'=>3,
			'9%增值税专用发票'=>4,
			'17%增值税专用发票'=>5,
		);
		$taxRate = array(
			1 => 0,
			2 => 0.03,
			3 => 0.06,
			4 => 0.09,
			5 => 0.17,
		);
		$fiobjArr = array(
			'公司'=>2,
			'个人'=>1,
			''=>1
		);


		foreach ($oldData as $val) {
			if ($val['parent_auth'] == 'advertisers_company_info') { //广告主

				$newcol['object_type'] = $fiobjArr[$val['fi_objects']];
				$newcol['account_name'] = $val['account'];
				$newcol['opening_bank'] = $val['fi_bank'];
				$newcol['bank_no'] = $val['fi_accountsreceivable'];
				$newcol['invoice_type'] = $invoice_type[$val['fi_invoicetype']];
				$newcol['invoice_remark'] = '';
				$newcol['taxpayer_num'] = $val['taxpayer'];
				$adTab->where('id='.$val['parent_id'])->save($newcol);
				//echo $adTab->getLastSql().';<br>';
			} else if ($val['parent_auth'] == 'new_supplier_info') {//子渠道

				$newcol['bl_id'] = $val['fi_business_line'];
				$newcol['sp_id'] = $val['parent_id'];
				$newcol['invoice_type'] = $invoice_type[$val['fi_invoicetype']];
				$newcol['object_type'] = $fiobjArr[$val['fi_objects']];
				$newcol['payee_name'] = $val['fi_beneficiary'];
				$newcol['opening_bank'] = $val['fi_bank'];
				$newcol['bank_no'] = $val['fi_accountsreceivable'];
				$newcol['financial_tax'] = $val['financial_tax'];
				$supTab->add($newcol);

			}else if ($val['parent_auth'] == 'a_new_supplier_info' && !empty($val['sub_sup_id'])) {
				$subArr = explode(',', $val['sub_sup_id']);
				foreach ($subArr as $item) {
					$newcol['bl_id'] = $val['fi_business_line'];
					$newcol['sp_id'] = $item;
					$newcol['invoice_type'] = $invoice_type[$val['fi_invoicetype']];
					$newcol['object_type'] = $fiobjArr[$val['fi_objects']];
					$newcol['payee_name'] = $val['fi_beneficiary'];
					$newcol['opening_bank'] = $val['fi_bank'];
					$newcol['bank_no'] = $val['fi_accountsreceivable'];
					$newcol['financial_tax'] = $val['financial_tax'];
					$supTab->add($newcol);
				}

			}

			unset($newcol);
		}
		echo 'ok';

	}


	//2.0供应商财务，挂接到子渠道
	function supFinPToC() {

		$tab_fin = M('financial_information1', null);
		$tab_relation = M('supplier_channel1', null);
//		sub_sup_id
		$finData = $tab_fin->where("parent_auth='a_new_supplier_info'")->select();
		foreach ($finData as $val) {
			$relData = $tab_relation->where("aid=".$val['parent_id'])->group('nid')->field('nid')->select();
			$_tmp = array();
			foreach($relData as $v){
				$_tmp[] = $v['nid'];
			}
			if (!empty($_tmp)) {
				$tmpStr = implode(',', $_tmp);
				$_map['sub_sup_id'] = $tmpStr;
				$tab_fin->where('fi_id='.$val['fi_id'])->save($_map);
			}

		}
		echo 'ok';
	}


	//对接人
	function contacts() {

		$adTab = M('advertiser_contacts');
		$proTab = M('product_contacts');
		$supTab = M('supplier_contacts');
		$oldtab = M('docking_user',null);

		$oldData = $oldtab->order('parent_id asc')->select();

		foreach ($oldData as $key=>$val) {

			$blId=M('business_line')->where("name LIKE '{$val['product_line']}'")->getField('id');
			$val['parent_auth'] = strtolower($val['parent_auth']);
			if (trim($val['parent_auth']) == 'advertisers_company_info') { //广告主

				$newcol['ad_id'] = $val['parent_id'];
				$newcol['name'] = $val['sed_name'];
				$newcol['mobile'] = $val['sed_telephone'];
				$newcol['qq'] = $val['sed_qq'];
				$newcol['email'] = $val['sed_email'];
				$newcol['address'] = $val['sed_address'];
				$newcol['user'] = '';
				$newcol['bl'] = empty($blId) ? 0 : $blId;
				$adTab->add($newcol);

			} else if (trim($val['parent_auth']) == 'new_supplier_info') {//供应商

				$newcol['sp_id'] = $val['parent_id'];
				$newcol['bl_id'] = empty($blId) ? 0 : $blId;
				$newcol['name'] = $val['sed_name'];
				$newcol['mobile'] = $val['sed_telephone'];
				$newcol['qq'] = substr($val['sed_qq'],0,12);
				$newcol['email'] = $val['sed_email'];
				$newcol['address'] = $val['sed_address'];
				$newcol['business_uid'] = 0;

				$supTab->add($newcol);

			} else if (trim($val['parent_auth']) == 'product_info') {

				 $newcol['pro_id'] = $val['parent_id'];
				 $newcol['name'] = $val['sed_name'];
				 $newcol['mobile'] = $val['sed_telephone'];
				 $newcol['qq'] = $val['sed_qq'];
				 $newcol['email'] = $val['sed_email'];

				 $proTab->add($newcol);
			}

			unset($newcol);
		}
		echo 'ok';

	}


	public function user() {

		$newtab = M("user");
		$oldTab = M('user_old',null);
		$oldData = $oldTab->select();

		foreach ($oldData as $val) {
			//是否存在
			$existUserName = trim($val['user_id']);
			$oldUid = $val['uid'];
			$count = $newtab->where("username='{$existUserName}' OR id={$oldUid}")->count();
			if($count > 0) {
				P($existUserName);
				continue;
			}
			$newcol['id'] = $val['uid'];
			$newcol['dept_id'] = $val['dept_id'];
			$newcol['position_id'] = 0;
			$newcol['employee_number'] = $val['uid'];
			$newcol['real_name'] = $val['user_name'];
			$newcol['username'] = $val['user_id'];
			$newcol['password'] = boss_md5(123456, UC_AUTH_KEY);
			$newcol['last_login_time'] = time();
			$newcol['reg_time'] = time();
			$newcol['gender'] = $val['sex'];
			$newcol['mobile'] = $val['mobil_no'];
			$newcol['qq'] = $val['oicq_no'];
			$newcol['email'] = $val['email'];
			$newcol['wechat'] = '';
			$newcol['education'] = '';
			$newcol['degree'] = '';
			$newcol['ethnic_group'] = '';
			$newcol['major'] = '';
			$newcol['address'] = $val['add_home'];
			$newcol['ismarried'] = 0;
			$newcol['status'] = 1;

//			P($newcol,true);
			$newtab->add($newcol);
			unset($newcol);
		}

		echo 'ok';

	}


	public function sspClImport() {
		exit('access deny');
		$clName1 = array('KaSNMob10031','KaSNMob10030','KaSNMob10035','KaSNMob10034','KaSNMob10029','KaSNMob10033','KaSNMob10032','KaSNMob10028','gdt_andzh06','gdt_ioszh04','gdt_andzh05','gdt_ioszh06','gdt_andzh04','gdt_ioszh05','gdt_zhios06','gdt_zhand06','zh_andzh02','zh_andzh03','zh_zhand01','zh_andzh01','zh_zhios01','zh_ioszh01','zh_ioszh02','zh_ioszh03','bannerAND02','gdt_andsc06','gdt_iossc10','gdt_iossc08','gdt_iossc06','gdt_andsc09','gdt_iossc07','TGhngg-gdt-js-banner','gdt_andsc08','gdt_andsc10','gdt_iossc09','gdt_andsc07','fs-安卓banner','SNMob100080IOS_Banner','SNMob100080ADR_Banner','xm_andsc01','xm_andsc05','德创-banner-android2','xm_andsc03','xm_andsc02','xm_andsc04','德创-banner-andro','SNMob100072IOS_Banner','TGjiawenqiang-gdt-js-banner-1','hy底部悬浮安卓01','TGjiawenqiang-gdt-js-banner819','fyt-安卓固定','xiaomiapi3','xiaomiapi','微信内置浏览器_banner','faloo-安卓banner','xiaomiapi2','微信内置浏览器_banner2','4007co','固定安卓01','AND+banner','xm_iossc04','qq浏览器banner03','xm_iossc01','xm_iossc03','xm_iossc05','SNMob100080Ope_Banner','xm_iossc02','TGjingbu-gdt-js-banner888','SNMob100080IOS_Ope_Banner','qq浏览器banner2','qq浏览器banner04','德创-banner-ios','SNMob100072','SNMob100072_FlowAds01','yrc_banner_ad_gy033','lqw跨域banner','SNMob100072IOS_Ope_Banner','dc底部banner_android','54banner 安卓','测试fsl02','fyt-广东Adr','faloo-IOSbanner','00xsbanner1','54banner ios','测试fsl01','上悬浮banner安卓','43banner安卓','yrc_banner_ios_gy034','yrc_banner_ad_jw001','下悬浮banner安卓','小米网安卓01','fyt-安卓banner1','56banner安卓','qq内置浏览器_banner','44banner安卓','56bannerios','44banner ios','中电底部悬浮banner02');
		$clName2=array('jcwl2_and','TGjiawenqiang-gdt-js-banner819','TGjiawenqiang-gdt-js-banner-1','jcwl1_and','TGkpkj-gdt-js-bannertop','酷视计时器_and09','酷视calendar_and03','酷视chemistry_and04','AND+banner','jcwl3_and','酷势环境监测_and01','faloo-安卓banner','wt2_banner_android','wt3_banner_android','JK嵌入式banner01','fs-安卓banner','zh_andzh03','zh_andzh02','Dictionary_chaping','AppBlock_chaping','Piano_chaping','zh_andzh01','fyt-安卓固定','酷势汽车资讯_and02','酷视汇率转换_and08','yrc_banner_ad_jw001','酷视walkman_and05','酷视MobileLocation_and07','4007co','zh_zhand01','wt4_底部banner','Postcard_chaping','dc底部banner_android','xm_andsc01','gdt_andzff01','儿童猜谜语游戏_A','酷视ZipQuery_and06','faloo-IOSbanner','中电底部悬浮banner01','养车易_android_开屏','魔力还原-banner1','WT_BANNER-ANDROID','bmh-安卓banner','养车易-banner','Dictionary_banner','TGhngg-gdt-js-banner','AppBlock_banner','Postcard_banner','bannerAND02');

		$clModel = D('ChargingLogo');
		$proModel = M('product');
		$clDetModle = D('ChargingLogoDetail');

		$clNames[0] = $clName1;
		$clNames[1] = $clName2;

		$proIds = array(
			0=>2298,
			1=>2731
		);

		foreach ($clNames as $key=>$clName) {
			$firstId = 0;
			$firstDetId = 0;
			$product = $proModel->find($proIds[$key]);
			if (empty($product)) {
				$this->error('产品不存在');
			}

			foreach ($clName as $cnVal) {
				$clData = array(
					'name' => $cnVal,
					'ad_id' => $product['ad_id'],
					'prot_id' => $product['id'],
					'price_type' => $product['price_type'],
					'price' => $product['price'],
					'url' => '-',
					'back_url' => '-',
					'charging_mode' => $product['charging_mode'],
					'account' => $product['package_return_account'],
					'password' => $product['package_return_passwd'],
				);

				if ($clModel->create($clData) === false) {
					$this->error(array('errcode'=>'5000','msg'=>'clname'.$cnVal.',计费标识分配错误:'.$clModel->getError()));
				}
				$insClId = $clModel->add();
				if ($insClId === false ) {
					$this->error(array('errcode'=>'5000','msg'=>'clname'.$cnVal.',计费标识分配错误:'.$clModel->getError()));
				} else {
					//添加后更新计费标识编码
					$_upmap['id'] = $insClId;
					$_upmap['code'] = $clModel->generalCode($insClId);
					if ($clModel->save($_upmap) === false) { //更新失败删除刚添加的计费标识
						$this->delete($insClId);
						$this->error(array('errcode'=>'5000','msg'=>'clname'.$cnVal.',更新计费标识编码错误:'));
					}
				}

				//增加分配记录
				//默认值
				$_defualt = array(
					'sup_id'=>2140, //供应商
					'bl_id'=>44,
					'promotion_stime'=>'2016-10-01', //开始推广时间
				);
				$_defualt['promotion_price_type']=1;//推广价格类型
				$_defualt['promotion_price']=1; //推广单价
				$_defualt['charging_mode']=1; //计费模式
				$_defualt['return_cycle']=1; //返量周期
				$_defualt['settlement_cycle']=1; //结算周期
				$_defualt['deduction_ratio']=0.0; //扣量比例
				$_defualt['in_settlement_prate']=0.0; //内部结算利润率

				$_defualt['cl_id'] = $insClId;
				$_defualt['status'] = 1; //状态使用中

				if ($clDetModle->create($_defualt) === false ) {
					$clModel->delete($insClId);//删除添加的计费标识
					$this->error(array('errcode'=>'5000','msg'=>'clname'.$cnVal.$clDetModle->getError()));
				}
				$detClId = $clDetModle->add();
				if ($detClId === false ) {
					$clModel->delete($insClId); //删除添加的计费标识
					$this->error(array('errcode'=>'5000','msg'=>'clname'.$cnVal.$clDetModle->getError()));
				} else {
					//添加后更新分配记录编码
					$_detmap['id'] = $detClId;
					$_detmap['code'] = $clDetModle->generalCode($insClId, $detClId);
					if ($clDetModle->save($_detmap) === false) { //更新失败删除刚添加的计费标识分配记录
						$clModel->delete($insClId);//删除添加的计费标识
						$clDetModle->delete($detClId);
						$this->error(array('errcode'=>'5000','msg'=>'clname'.$cnVal.$clDetModle->getError()));
					}
				}

				if ($firstId==0){
					$firstId = $insClId;
				}
				if ($firstDetId==0) {
					$firstDetId = $detClId;
				}

			}

			//增加计费标识
			echo 'ok first id is :' . $firstId.';  last id is :' . $insClId . '<br>';
			echo 'ok first det id is :' . $firstDetId.';  last id is :' . $detClId . '<br><br>';

		}


	}


	/*谢强*/

	public function makeFP(){
		/*
		$data=M()->table('qixin_incomedata_check_more')->field('ad_id,perioddatestart,perioddate,pretax_money,period_date,qic_id')->select();
		var_dump($data);
		foreach ($data as $key => $value) {
			$FParr[$value['qic_id']]['list'][]=$value;
		}*/
		$data=M('settlement_in')->where("id in (823,1043,1046,1124,1152,1153,1154,1157,1158,1159,1177,1178,1179,1180,1181,1182,1183,1184,1188,1189,1190,1192,1193,1195,1196,1197,1198,1199,1201,1202,1203,1204,1205,1207,1208,1211,1213,1214,1215,1217,1218,1220)")->select();
		$FPdata=M()->table('qixin_incomedata_check_tax')->field('qic_id,tax_money,tax_coding,kd_number')->where("qic_id in (823,1043,1046,1124,1152,1153,1154,1157,1158,1159,1177,1178,1179,1180,1181,1182,1183,1184,1188,1189,1190,1192,1193,1195,1196,1197,1198,1199,1201,1202,1203,1204,1205,1207,1208,1211,1213,1214,1215,1217,1218,1220)")->select();
		foreach ($FPdata as $key => $value) {
			$FParr[$value['qic_id']]['invo'][]=array('money'=>$value['tax_money'],'code'=>$value['tax_coding']);
			$FParr[$value['qic_id']]['num']=$value['kd_number'];
		}
		foreach ($FParr as $key => $value) {
			M('settlement_in')->where("id=".$key)->save(array('invoiceinfo'=>json_encode($value['invo']),'expresscode'=>$value['num']));
			var_dump(json_encode($value['invo']));
			echo $key.'<br/>';
		}
		/*
		foreach ($FParr as $key => $value) {
			$allmoney=0;
			$comidarr=array();
			foreach ($value['list'] as $k => $v) {
				echo $v['pretax_money'].' ';
				$allmoney+=$v['pretax_money'];
				$comidarr[]=$v['ad_id'];
			}
			echo 'allmoney=>'.$allmoney.' ';
			M('settlement_in')->where("id=".$key)->save(array('invoiceinfo'=>json_encode($value['invo']),'nowskmoneytime'=>$value['list'][0]['period_date'],'expresscode'=>$value['num'],'comid'=>$value['list'][0]['ad_id'],'strdate'=>$value['list'][0]['perioddatestart'],'enddate'=>$value['list'][0]['perioddate'],'allcomid'=>implode(',',$comidarr),'settlementmoney'=>$allmoney));
			echo $key.'<br/>';
		}*/
		echo '完成';
	}

	public function makeout(){
		$data=M()->table('qixin_senddata_check_more')->select();
		foreach ($data as $key => $value) {
			$arr[$value['qic_id']][]=$value;
		}
		foreach ($arr as $key => $value) {
			$res=M('flow_data_432')->field('data_1')->where("data_150 like '$key,%' || data_150 like '%,$key,%'")->find();
			$res_u=M('user')->field('id')->where("real_name='".$res['data_1']."'")->find();
			$jfidarr=array();
			$allmoney=0;
			$comidarr=array();
			$comarr=array();
			foreach ($value as $k => $v) {
				$jfidarr[]=$v['cl_id'];
				$allmoney+=$v['pretax_money'];
				$comidarr[]=$v['ad_id'];
			}
			$res_c=M('product')->field('name')->where("id in (".implode(',',$comidarr).")")->select();
			foreach ($res_c as $k => $v) {
				$comarr[]=$v['name'];
			}
			M('settlement_out_copy')->where("id=".$key)->save(array('jfid'=>$value[0]['cl_id'],'strdate'=>$value[0]['perioddatestart'],'enddate'=>$value[0]['perioddate'],'alljfid'=>implode(',',$jfidarr),'settlementmoney'=>$allmoney,'allcomname'=>implode(',',$comarr),'struserid'=>$res_u['id']));
			echo $key.'<br/>';
		}
		echo "完成";
	}
	public function changeoutcomname(){
		$data=M('settlement_out')->where("allcomname=''")->select();
		foreach ($data as $key => $v) {
			$res=M('charging_logo')->field('b.name')->join('a join boss_product b on a.prot_id=b.id')->where("a.id=".$v['jfid'])->find();
			$id=M('settlement_out')->where("id=".$v['id'])->save(array('allcomname'=>$res['name']));
			echo $id.'<br/>';
		}
		echo '完成';
	}
	public function changeoutsupid(){
		$data=M('settlement_out')->select();
		foreach ($data as $key => $v) {
			$res=M('daydata_out')->field('superid')->where('jfid in ('.$v['alljfid'].') && adddate>="'.$v['strdate'].'" && adddate<="'.$v['enddate'].'"')->find();
			$id=M('settlement_out')->where("id=".$v['id'])->save(array('superid'=>$res['superid']));
			echo $id.'<br/>';
		}
		echo '完成';
	}
	public function changeoutaddid(){
		$data=M('settlement_out')->field('group_concat(a.id) as id')->join('a join boss_supplier b on a.superid=b.id')->where('b.type=1')->find();
		echo M()->getLastSql();
		M('settlement_out')->where("id in (".$data['id'].")")->save(array('addresserid'=>1488));
		echo '完成';
	}
	public function changeinsbid(){
		$data=M('settlement_in')->select();
		foreach ($data as $key => $v) {
			$res=M('daydata')->field('a.ztid')->join('a join boss_charging_logo b on a.jfid=b.id')->where('b.prot_id ='.$v['comid'].' && a.adddate>="'.$v['strdate'].'" && a.adddate<="'.$v['enddate'].'"')->find();
			$id=M('settlement_in')->where("id=".$v['id'])->save(array('jsztid'=>$res['ztid']));
			echo $id.'<br/>';
		}
		$data=M('settlement_out')->select();
		foreach ($data as $key => $v) {
			$res=M('daydata_out')->field('sbid')->where('jfid ='.$v['jfid'].' && adddate>="'.$v['strdate'].'" && adddate<="'.$v['enddate'].'"')->find();
			$id=M('settlement_out')->where("id=".$v['id'])->save(array('jsztid'=>$res['sbid']));
			echo $id.'<br/>';
		}
		echo '完成';
	}
	public function changedaydatasalerid(){
		$res=M('settlement_in')->field('a.id')->join('a left join boss_user b on a.salerid=b.id')->where('b.id is null')->select();
		echo M()->getLastSql();
		foreach ($res as $k => $v) {
			$r[]=$v['id'];
		}
		if(count($r)>0)M('settlement_in')->where("id in (".implode(',',$r).")")->save(array('salerid'=>211));
		$res2=M('daydata')->field('a.id')->join('a left join boss_user b on a.salerid=b.id')->where('b.id is null')->select();
		foreach ($res2 as $k => $v) {
			$r2[]=$v['id'];
		}
		if(count($r2)>0)M('daydata')->where("id in (".implode(',',$r2).")")->save(array('salerid'=>211));
		$res3=M('settlement_out')->field('a.id')->join('a left join boss_user b on a.sangwuid=b.id')->where('b.id is null')->select();
		foreach ($res3 as $k => $v) {
			$r3[]=$v['id'];
		}
		if(count($r3)>0)M('settlement_out')->where("id in (".implode(',',$r3).")")->save(array('sangwuid'=>633));
		$res4=M('daydata_out')->field('a.id')->join('a left join boss_user b on a.businessid=b.id')->where('b.id is null')->select();
		foreach ($res4 as $k => $v) {
			$r4[]=$v['id'];
		}
		if(count($r4)>0)M('daydata_out')->where("id in (".implode(',',$r4).")")->save(array('businessid'=>633));
	}
	public function makeclosing(){
		$res=M()->query("SELECT e.id
FROM `boss_charging_logo` a 
left join boss_daydata d on d.jfid=a.id && d.adddate > '2016-02-27' && d.adddate< '2016-02-30' && d.status!=0 

left join boss_daydata_out e on e.jfid=if(d.jfid is null,a.id,d.jfid) && if(d.adddate is null,e.adddate > '2016-02-27' && e.adddate< '2016-02-30',d.adddate=e.adddate)  && e.status!=0
where  e.id >0");
		foreach ($res as $key => $value) {
			echo $value['id'].',';
		}
	}
	public function getclosingdata(){
		$res=M()->query("select
g.name as 结算主体,h.name as 业务线,c.name as 产品名称,c.code as 产品编码,b.name as 计费标识,
b.code as 计费编码,concat(min(a.adddate),'-',max(a.adddate)) as 时间,
d.name as 供应商,if(d.type=1 && f.payee_name is null,'江西拓展无限网络有限公司',f.payee_name) as 收款方名称,
	if(d.type=1,'GYS0002182',d.code) as 供应商编码,e.real_name as 商务,
round(sum(a.out_newmoney)*100)/100 as 金额,round(sum(a.out_newmoney*(1-f.financial_tax))*100)/100 as 不含税金额,
round(sum(a.out_newmoney*f.financial_tax)*100)/100 as 税额,a.out_status as 状态,f.invoice_type as 发票类型
from boss_closing a 
join boss_charging_logo b on a.jfid=b.id
join boss_product c on b.prot_id=c.id
join boss_supplier d on a.out_superid=d.id
join boss_user e on a.out_businessid=e.id
left join boss_supplier_finance f on if((d.type=2 || d.type=3) && (a.out_addid=0 || a.out_addid is null),d.id,a.out_addid)=f.sp_id && a.out_lineid=f.bl_id
join boss_data_dic g on a.out_sbid=g.id
join boss_business_line h on a.out_lineid=h.id
where a.adddate like '2017-02%' && a.out_status!=0 && a.out_status!=9
group by a.jfid,a.out_superid,a.out_businessid,a.out_sbid,a.out_status,a.in_adverid,a.in_lineid,a.out_addid");

		
		$res1=M()->query("select
g.name as 结算主体,h.name as 业务线,c.name as 产品名称,c.code as 产品编码,b.name as 计费标识,
b.code as 计费编码,concat(min(a.adddate),'-',max(a.adddate)) as 时间,
d.name as 广告主,d.ad_code as 广告主编码,e.real_name as 销售,e.id as 销售ID,0,
round(sum(a.in_newmoney)*100)/100 as 金额,d.invoice_type as 发票类型,a.in_status as 状态
from boss_closing a 
join boss_charging_logo b on a.jfid=b.id
join boss_product c on b.prot_id=c.id
join boss_advertiser d on a.in_adverid=d.id
join boss_user e on a.in_salerid=e.id
join boss_data_dic g on a.in_ztid=g.id
join boss_business_line h on a.in_lineid=h.id
left join boss_data_dic i on d.invoice_type=i.id
where a.adddate like '2017-02%' && a.in_status!=0 && a.in_status!=9
group by a.jfid,a.in_adverid,a.in_salerid,a.in_ztid,a.in_status,a.out_superid,a.out_lineid");

		echo '<style>*{font-size:12px;}</style>';
		echo '成本数据 <a href="#FFF">点击翻到收入数据</a>';
		echo '<table border=1 cellspacing=0>';
		echo "<tr>";
		foreach ($res[0] as $key => $value) {
			echo '<td>'.$key.'</td>';
		}
		echo '</tr>';
		foreach ($res as $key => $value) {
			echo '<tr>';
				foreach ($value as $k => $v) {
					echo '<td>';
					if($k=='发票类型'){
						switch ($v) {
							case '1':
								echo '无发票';
								break;
							
							case '2':
								echo '3%增值税普通发票';
								break;
							case '3':
								echo '6%增值税专用发票';
								break;
							case '4':
								echo '9%增值税专用发票';
								break;
							case '5':
								echo '17%增值税专用发票';
								break;
						}
					}else echo $v;
					echo '</td>';
				}
			echo '</tr>';
		}
		echo '</table>';
		echo '<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>';
		
		echo '收入数据<a name="FFF"></a>';
		echo '<table border=1 cellspacing=0>';
		echo "<tr>";
		foreach ($res1[0] as $key => $value) {
			echo '<td>'.$key.'</td>';
		}
		echo '</tr>';
		foreach ($res1 as $key => $value) {
			echo '<tr>';
				foreach ($value as $k => $v) {
					echo '<td>';
					if($k=='发票类型'){
						switch ($v) {
							case '1':
								echo '无发票';
								break;
							
							case '2':
								echo '3%增值税普通发票';
								break;
							case '3':
								echo '6%增值税专用发票';
								break;
							case '4':
								echo '9%增值税专用发票';
								break;
							case '5':
								echo '17%增值税专用发票';
								break;
						}
					}else echo $v;
					echo '</td>';
				}
			echo '</tr>';
		}
		echo '</table>';
	}
	public function changesetstatus(){
		$r=M('settlement_out')->where('status!=6 && status!=0')->select();
		foreach ($r as $key => $v) {
			$strtime=$v['strdate'];
			$endtime=$v['enddate'];
			$jfidarr=$v['alljfid'];
			if($r['id']<=1348){
				$where="a.adddate >= '$strtime' && a.adddate <= '$endtime' && a.jfid in ($jfidarr)";
				$res= M('daydata_out')->field('a.status,a.id')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.prot_id=c.id join boss_supplier d on a.superid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.sbid=f.id join boss_user g on a.businessid=g.id left join boss_daydata i on a.jfid=i.jfid && a.adddate=i.adddate')->where($where)->group('a.id')->select();
			}
			else{
				$superid=$v['superid'];
				$businessid=$v['sangwuid'];
				$sbid=$v['jsztid'];
				$where="a.adddate >= '$strtime' && a.adddate <= '$endtime' && a.superid=$superid && a.businessid in ($businessid) && a.sbid=$sbid && a.jfid in ($jfidarr)";
				$res=$Daydata->field('a.status,a.id')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.prot_id=c.id join boss_supplier d on a.superid=d.id join boss_business_line e on a.lineid=e.id join boss_data_dic f on a.sbid=f.id join boss_user g on a.businessid=g.id left join boss_daydata i on a.jfid=i.jfid && a.adddate=i.adddate')->where($where)->group('a.id')->select();
			}
			foreach ($res as $key => $val) {
				if($v['status']==1 && $val['status']!=2){
					M('daydata_out')->where("id=".$val['id'])->save(array('status'=>2));
				}
				if($v['status']==2 && $val['status']!=3){
					M('daydata_out')->where("id=".$val['id'])->save(array('status'=>3));
				}
			}
			
		}
	}
	public function changesetstatus_in(){
		$p=I('get.p',0);
		$data=M('daydata')->where("status!=0 && status!=9")->order('id desc')->limit(($p*100).',100')->select();
		foreach ($data as $key => $v) {
			echo $v['id'].' ';
			$res=M('settlement_in')->where("strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && (allcomid like '".$v['comid'].",%' || allcomid like '%,".$v['comid']."' || allcomid like '%,".$v['comid'].",%' || allcomid='".$v['comid']."') && status!=6 && advid=".$v['adverid'])->order('status desc')->find();
			echo ' '.$v['status'].'&&'.$res['status'];
			if((!$res || $res['status']==0) && $v['status']!=1){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>1));
				echo '<br/>'.$v['status'].'=>1<br/>';
			}elseif($res['status']==1 && $v['status']!=2){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>2));
				echo '<br/>'.$v['status'].'=>2<br/>';
			}elseif($res['status']==2 && $v['status']!=3){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>3));
				echo '<br/>'.$v['status'].'=>3<br/>';
			}elseif($res['status']==3 && $v['status']!=4){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>4));
				echo '<br/>'.$v['status'].'=>4<br/>';
			}elseif($res['status']==4 && $v['status']!=5){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>5));
				echo '<br/>'.$v['status'].'=>5<br/>';
			}
			echo '&nbsp;&nbsp;';
		}
		if(count($data)<100)exit('完成');
		else echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	public function changesetstatus_out(){
		$p=I('get.p',0);
		$data=M('daydata_out')->where("status!=0 && status!=9")->order('id desc')->limit(($p*100).',100')->select();
		foreach ($data as $key => $v) {
			echo $v['id'].' ';
			$res=M('settlement_out')->where("strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && (alljfid like '".$v['jfid'].",%' || alljfid like '%,".$v['jfid']."' || alljfid like '%,".$v['jfid'].",%' || alljfid='".$v['jfid']."') && status!=6 && superid='".$v['superid']."'")->order('status desc')->find();
			echo ' '.$v['status'].'&&'.$res['status'];
			if(!$res && $v['status']!=1){
				M('daydata_out')->where("id=".$v['id'])->save(array('status'=>1));
				echo '<br/>'.$v['status'].'=>1<br/>';
			}elseif($res['status']==1 && $v['status']!=2){
				M('daydata_out')->where("id=".$v['id'])->save(array('status'=>2));
				echo '<br/>'.$v['status'].'=>2<br/>';
			}elseif($res['status']==2 && $v['status']!=3){
				M('daydata_out')->where("id=".$v['id'])->save(array('status'=>3));
				echo '<br/>'.$v['status'].'=>3<br/>';
			}elseif($res['status']==0 && $v['status']!=1){
				M('daydata_out')->where("id=".$v['id'])->save(array('status'=>1));
				echo '<br/>'.$v['status'].'=>1<br/>';
			}
			echo '&nbsp;&nbsp;';
		}
		if(count($data)<100)exit('完成');
		else echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	public function changeindataandsetttlementstatus(){
		$p=I('get.p',0);
		$data=M('daydata')->where("status=5")->limit(($p*100).',100')->select();
		foreach ($data as $key => $v) {
			$res=M('settlement_in')->where("strdate<='".$v['adddate']."' && enddate>='".$v['adddate']."' && (allcomid like '".$v['comid'].",%' || allcomid like '%,".$v['comid']."' || allcomid like '%,".$v['comid'].",%' || allcomid='".$v['comid']."')")->find();
			if($res['status']==5){
				M('daydata')->where("id=".$v['id'])->save(array('status'=>8));
				echo $v['id'].'<br/>';
			}

		}
		if(count($data)<100)exit('完成');
		else echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	public function test(){
		/*
		$res_a=M('daydata111')->where('id in (178,183,195,214,219,220,221,222,223,229,242,243,244,250,251,256,257,268,272,275,309,335,336,341,343,345,366,393,417,420,421,428,434,448,465,470,477,478,480,481,500,506,541,543,548,551,574,580,582,583,584,585,595,600,601,602,605,607,609,617,620,636,639,640,652,665,683,715,729,735,750,774,780,781,796,803,807,811,813,829,847,853,862,874,877,894,896,908,913,928,943,951,981,982,985,988,989,991,1003,1004,1027,1028,1036,1037,1048,1049,1063,1075,1078,1079,1105,1141,1149,1166,1167,1170,1183,1206,1208,1218,1219,1220,1221,1222)')->select();
		var_dump($res_a);
		foreach ($res_a as $k => $v) {
			$res=M()->table('qixin_incomedata_check_more111')->where("qic_id =".$v['id'])->select();
			$arr=array();
			foreach ($res as $key => $val) {
				$arr[]=$val['qiid_str'];
			}
			$str=implode(',',$arr);
			echo '<br/>'.$str;
			M('daydata111')->where("id=".$v['id'])->save(array('alldataid'=>$str));
		}
		echo 'aaaa';*/
		var_dump(trace());
	}
	public function indatafororther(){
		$res=M('indata')->select();
		$type=I('get.type');
		foreach ($res as $k => $v) {
			$jfid=$v['jfid'];
			$adddate=$v['adddate'];
			if($type==1){//收入

			}else{//成本
				$r=M('daydata_inandout')->where("jfid=$jfid && adddate='$adddate'")->find();
				$r2=M('daydata_out')->where("jfid=$jfid && adddate='$adddate'")->find();
				if($r){
					M('daydata_inandout')->where('id='.$r['id'])->save(array('out_newdata'=>$v['datanum'],'out_datanum'=>$v['datanum'],'out_money'=>$v['money'],'out_remarks'=>$v['remarks'],'out_newmoney'=>$v['money'],'out_status'=>$v['status'],'out_superid'=>$v['superid'],'out_businessid'=>$v['businessid'],'out_price'=>$v['price'],'out_lineid'=>$v['lineid'],'out_sbid'=>$v['sbid'],'out_id'=>$r2['id']));
				}else{
					M('daydata_inandout')->add(array('out_newdata'=>$v['datanum'],'out_datanum'=>$v['datanum'],'out_money'=>$v['money'],'out_remarks'=>$v['remarks'],'out_newmoney'=>$v['money'],'out_status'=>$v['status'],'out_superid'=>$v['superid'],'out_businessid'=>$v['businessid'],'out_price'=>$v['price'],'out_lineid'=>$v['lineid'],'out_sbid'=>$v['sbid'],'out_id'=>$r2['id']));
				}
			}
		}
	}
	public function checkdatastatus(){
		$p=I('get.p',0);
		$data=M('settlement_in')->where("id>1224 && status!=6 && status!=0")->group('allcomid,strdate,enddate,advid,lineid')->order('id desc')->limit($p.',1')->select();
		$data=$data[0];
		$time=$data['audittime'];
		if($data['addtime']!='')$time=$data['addtime'];
		if($data['alljfid']!=''){
			$alljfid=$data['alljfid'];
			$creattablesql="update boss_daydata b join boss_daydata_log a on b.id=a.dataid && a.datatype=1 set b.status=10 where ".'b.jfid in ('.$alljfid.') && b.adddate>="'.$data['strdate'].'" && b.adddate<="'.$data['enddate'].'" && a.addtime>"'.$time.'" && a.remark="数据同步"';
		}else{
			$alljfid=$data['allcomid'];
			$creattablesql="update boss_daydata b join boss_daydata_log a on b.id=a.dataid && a.datatype=1 set b.status=10 where ".'b.comid in ('.$alljfid.') && b.adddate>="'.$data['strdate'].'" && b.adddate<="'.$data['enddate'].'" && a.addtime>"'.$time.'" && a.remark="数据同步"';
		}
		M()->execute($creattablesql);
		if(count($data)==0)exit('结束');
		echo '结算单ID：'.$data['id'].'<br/>';
		var_dump($creattablesql);
		echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	public function checkdatastatus_out(){
		
		$p=I('get.p',0);
		$data=M('settlement_out')->where("id>1346 && status!=6 && status!=0")->group('alljfid,strdate,enddate,superid,lineid')->order('id desc')->limit($p.',1')->select();
		$data=$data[0];
		$time=$data['audittime'];
		if($data['addtime']!='')$time=$data['addtime'];
			$alljfid=$data['alljfid'];
			$creattablesql="update boss_daydata_out b join boss_daydata_log a on b.id=a.dataid && a.datatype=2 set b.status=10 where ".'b.jfid in ('.$alljfid.') && b.adddate>="'.$data['strdate'].'" && b.adddate<="'.$data['enddate'].'" && a.addtime>"'.$time.'" && a.remark="数据同步"';
		M()->execute($creattablesql);
		if(count($data)==0)exit('结束');
		echo '结算单ID：'.$data['id'].'<br/>';
		var_dump($creattablesql);
		echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	public function checkycdata(){
		$p=I('get.p',0);
		$data_a=M('daydata')->where("adddate>='2017-01-01' && adddate<='2017-01-31' && lineid=1 && status=5")->limit(($p*100).',100')->select();
		$ycdata=array();
		foreach ($data_a as $key => $data) {
			$set_data=M('settlement_in')->where("find_in_set(".$data['jfid'].",alljfid) && strdate<='".$data['adddate']."' && enddate>='".$data['adddate']."' && status=4")->find();
			if(!$set_data)$ycdata[]=$data['id'];
		}
		if(count($ycdata)>0){
			var_dump(implode(',', $ycdata));
			exit();
		}
		if(count($data_a)<100){
			exit('ok');
		}
		echo '<script>window.location="?p='.($p+1).'"</script>';
	}
	public function changeaddid(){
		echo 'abc';
		//$res=M('xq_t')->field("a.jfid,a.date")->join("a join boss_daydata b on a.jfid=b.jfid && left(b.adddate,7)=a.date")->where("a.jfid!=0 && b.status!=5")->group("a.jfid")->select();
		if(I('get.xq')=='gasidegg'){
			$res=M('xq_t')->select();
			foreach ($res as $k => $v) {
				M('daydata')->where("jfid=".$v['jfid']." && adddate like '".$v['date']."%'")->save(array('status'=>5));
				echo M()->getLastSql();
				echo '<br/>';
				M('daydata_inandout')->where("jfid=".$v['jfid']." && adddate like '".$v['date']."%'")->save(array('in_status'=>5));
				echo M()->getLastSql();
				echo '<br/>';
			}
			echo '完成了';
		}
		
	}
	public function changelcid2(){
		$data=M('oa_liuchen')->where("liuchenid in (51300,51461,51405,51412,51413,51414,51419,51420,51423,51430,51480,51481,51487,51490,51507,51363,51433,51479,51321,51358,51403,51407,51442,51445,51450,51453,51455,51456,51457,51463,51466,51469,51473,51474,51477,51492,51505,51509,51206,51348,51349,51350,51353,51393,51397,51475,51476,51498,51499,51202,51204,51282,51302,51305,51313,51324,51331,51333,51364,51372,51380,51382,51401,51409)")->select();
		$arr=array(40=>'xb27546_3',44=>'x95f845_3',45=>'x72668e_3',46=>'xf159bb_3',48=>'xe38d6e_3',52=>'x45be09_3',53=>'xa4d9cb_3',56=>'xb9b4a2_2',57=>'xe87944_3',58=>'x5098d0_3',60=>'x782c92_3',63=>'x2a1540_3',64=>'xb4cef4_3',65=>'x05b464_1',68=>'x9de76c_3',67=>'x29cc4e_3',69=>'x240975_3',66=>'x739c8a_1');
		foreach ($data as $key => $value) {
			M('oa_'.$value['mid'])->where("id=".$value['alldata'])->save(array($arr[$value['mid']]=>$value['liuchenid']));
		}
	}
}