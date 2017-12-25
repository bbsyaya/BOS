<?php
namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 供应商
 * Class ProductController
 * @package Home\Controller
 */
class SupplierController extends BaseController {


	private function _commonWhere($qubie) {

		$name = I('get.name','');
		$type = I('get.type',0,'intval');
		$region = I('get.region',0,'');
		$bb_type = I('get.bb_type','');
		$sup_id = I('get.sup_id');
		if($qubie ==1){
			$where = " status=1";
		}else{
			$where = " where status=1";
		}

		if($sup_id){
			// $where['boss_supplier.id'] = array('in', $sup_id);
			$where .= " and su.id in ({$sup_id})";
		}
		if (!empty($name)) {
			// $where['boss_supplier.name'] = array('like',"%{$name}%");
			$where .= " and su.name like '%{$name}%'";
		}
		if ($type > 0) {
			// $where['boss_supplier.type'] = $type;
			$where .= " and su.type =".$type;
		}
		if ($region > 0) {
			// $where['boss_supplier.region'] = $region;
			$where .= " and su.region={$region}";
		}
		$email = I('get.email','');
		if($email) {
			// $where['boss_supplier.email'] = array('like',"%{$email}%");
			$where .= " and su.email like '%{$email}%'";
		}


		$business_uid = I('get.business_uid',0,'');
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"]);
		if($isRead){
			$business_uid = $_SESSION["userinfo"]["uid"];
		}

		if ($business_uid > 0) {
			// print_r($business_uid);exit;
			//商务对应的 供应商id
			$spRes = M()->query("SELECT sp_id FROM `boss_supplier_contacts` WHERE business_uid={$business_uid} GROUP BY sp_id,business_uid");
			$_tmp = array();
			foreach ($spRes as $val) {
				$_tmp[] = $val['sp_id'];
			}
			$spidStr = implode(',', $_tmp);
			$spidStr = empty($spidStr)?"0":$spidStr;
			$where .= " and su.id in ({$spidStr})";

		}

		
		if((int)I('get.is_check') >=0 && I('get.is_check') !=''){
			// $where['boss_supplier.is_check'] = I('get.is_check');
			$ck_id = I('get.is_check');
			$where .= " and su.is_check={$ck_id}";
		}
		$start_time = date('Y-m-01', strtotime('-1 month'));//上月
		$end_time   = date('Y-m-t', strtotime('-1 month'));//上月
		if($bb_type == 'yx'){
			$yxData = M()->query("SELECT superid FROM boss_daydata_out WHERE adddate >='".$start_time."' AND adddate <='".$end_time."' GROUP BY superid");
			$supid = "";
			foreach($yxData as $key=>$val){
				$supid .= $val['superid'].",";
			}
			$supid = rtrim($supid, ",");
			if($supid){
				// $where[] = "id in ($supid)";
				$where .= " and su.id={$supid}";
			}


		}elseif($bb_type == 'xz'){
			$BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));//当月
			$EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));//当月
			$xzData = M()->query("select id from boss_supplier where DATE_FORMAT(add_time,'%Y-%m-%d') >='".$BeginDate."' and DATE_FORMAT(add_time,'%Y-%m-%d') <='".$EndDate."'");
			$supid = "";
			foreach($xzData as $key=>$val){
				$supid .= $val['id'].",";
			}
			$supid = rtrim($supid, ",");
			if($supid){
				// $where[] = "id in ($supid)";
				$where .= " and su.id={$supid}";
			}else{
				// $where['id'] = '';
			}


		}elseif($bb_type == 'zt'){
			$s_time = date('Y-m-01', strtotime('-2 month'));//上上个月
			$e_time = date('Y-m-t', strtotime('-2 month'));//上上个月
			$ztData = M()->query("SELECT superid FROM boss_daydata_out WHERE adddate >='".$s_time."' AND adddate <='".$e_time."' AND superid NOT IN (SELECT superid FROM boss_daydata_out WHERE adddate >='".$start_time."' AND adddate <='".$end_time."' GROUP BY superid) GROUP BY superid");

			$supid = "";
			foreach($ztData as $key=>$val){
				$supid .= $val['superid'].",";
			}
			$supid = rtrim($supid, ",");
			// $where[] = "id in ($supid)";
			$where .= " and su.id={$supid}";

		}

		return $where;
	}


	public function index(){
		ignore_user_abort();//脱离客户端
		set_time_limit(0);//不限时间执行
		session_write_close();//session解锁

		$this->assign('type', C('OPTION.supplier_type'));
		$this->assign('region', D('Region')->getRegion());
		$this->assign('business_uid', M('user')->field('a.id,a.real_name')->join("a JOIN boss_oa_hr_manage b ON a.id=b.user_id JOIN boss_oa_position c ON c.id=b.duty")->where("c.`name` LIKE '%商务%'")->getField('a.id,a.real_name'));
		$this->assign('is_check', C('OPTION.is_check'));
		$this->assign('hz_status', C('OPTION.hz_status'));

		/*$options = array(
			array('type'=>'a', 'id'=>'', 'class'=>'', 'title'=>'新增', 'url'=>U('edit')),
			array('type'=>'a', 'id'=>'doExport', 'class'=>'', 'title'=>'导出', 'url'=>'javascript:;'),
			array('type'=>'a', 'id'=>'doFinanceExport', 'class'=>'', 'title'=>'财务导出', 'url'=>'javascript:;'),
		);
		$this->assign('toolOptions', $options);*/

		/*$searchOptions = array(
			array('title'=>'供应商名称','name'=>'name','type'=>'text'),
			array('title'=>'类型','name'=>'type','type'=>'select','options'=>C('OPTION.supplier_type'), 'value'=>$_REQUEST['supplier_type'],'class'=>'selectpicker'),
			array('title'=>'地区','name'=>'region','type'=>'select','options'=>D('Region')->getRegion(), 'value'=>$_REQUEST['region'],'class'=>'selectpicker'),
			array('title'=>'责任商务','name'=>'business_uid','type'=>'select','options'=>M('user')->getField('id,real_name'), 'value'=>$_REQUEST['business_uid'],'class'=>'selectpicker'),
			array('title'=>'邮箱','name'=>'email','type'=>'text', 'value'=>$_REQUEST['email'],'class'=>'selectpicker'),
			array('title'=>'是否检查','name'=>'is_check','type'=>'select','options'=>C('OPTION.is_check'), 'value'=>$_REQUEST['is_check'],'class'=>'selectpicker'),
			array("title"=>"合作状态","name"=>"hz_status","type"=>"select","options"=>C('OPTION.hz_status'),'value'=>$_REQUEST['hz_status'],'class'=>'selectpicker')
		);
		$this->assign('searchOptions', $searchOptions);*/

		//筛选
		$where = $this->_commonWhere();

		$list = $this->lists('Supplier', $where);

		if (!empty($list)) {
			$regionData = D('Region')->getRegion();
			foreach ($list as &$val) {
				$val['region'] = $regionData[$val['region']];
			}
			unset($regionData);
		}

		$this->assign('list', $list);
		$this->assign('op_supplier_type', C('OPTION.supplier_type'));
		Cookie('__forward__',$_SERVER['REQUEST_URI']);
		$this->display();

	}


	/* 导出基础信息 */
	public function export() {
		/*$where = $this->_commonWhere(1);
		$list = M('supplier')->field('id,code,name,region,type,grade,credit')->where($where)->select();

		$aids = array();
		foreach ($list as $val) {
			$aids[] = $val['id'];
		}

		$contactsData = M('supplier_contacts')->field('sp_id,name,mobile,business_uid')->where(array('sp_id'=>array('in',$aids)))->select();
		$conUArr = array();
		foreach($contactsData as $val) {
			//D('User')->getAssoUserName($val['business_uid']);
			$user_data = M('user')->where("id =".$val['business_uid']." ")->getField('real_name');
			$contactArr[$val['sp_id']][] = $val['name'].'|'.$val['mobile'];
			$conUArr[$val['sp_id']][] = $user_data;
		}
		unset($contactsData);
		foreach ($list as $key=>$val) {
			$list[$key]['contacts'] = implode(',',$contactArr[$val['id']]);
			$list[$key]['we_dock'] = implode(',',$conUArr[$val['id']]);
		}*/

		$where = $this->_commonWhere();
		C('LIST_ROWS', '');
		$list = $this->lists('Supplier', $where);

		$op_supplier_type = C('OPTION.supplier_type');
		if ($list) {
			$regionData = D('Region')->getRegion();
			foreach ($list as $key=>$val) {
				$list[$key]['region'] = $regionData[$val['region']];
				$list[$key]['type'] = $op_supplier_type[$val['type']];
			}
			unset($regionData);
		}

		$title = array('id'=>'序号','code'=>'供应商编码','name'=>'供应商名称','region'=>'地区',
			'type'=>'类型','grade'=>'实力评级','credit'=>'信用评级','hz_status'=>'合作状态',"lxr"=>"联系人");
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '供应商'.date('Y-m-d H:i:s'));

	}


	/* 导出财务信息 */
	public function financeExport() {
		$where = $this->_commonWhere();

		$list = M('supplier')->field('boss_supplier.name AS sp_name,bsf.bl_id,bsf.sp_id,bsf.invoice_type,bsf.object_type,bsf.payee_name,bsf.opening_bank,bsf.bank_no,bsf.financial_tax')
			->join('JOIN boss_supplier_finance bsf ON boss_supplier.id=bsf.sp_id')
			->where($where)
			->order('sp_id desc')
			->select();

		$blArr = array();
		foreach ($list as $val) {
			$blArr[] = $val['bl_id'];
		}
		$blids = implode(',', $blArr);

		$op_invoice_type = C('OPTION.invoice_type');
		$op_finance_object = C('OPTION.finance_object');
		$blNameArr = empty($blArr) ? array() : M('business_line')->where("id IN ($blids)")->getField('id,name');
		foreach ($list as $key=>&$val) {
			$val['invoice_type'] = $op_invoice_type[$val['invoice_type']];
			$val['object_type'] = $op_finance_object[$val['object_type']];
			$val['bl_id'] = $blNameArr[$val['bl_id']];
		}

		$title = array('sp_id'=>'供应商id','sp_name'=>'供应商名称','bl_id'=>'业务线','invoice_type'=>'发票类型',
			'object_type'=>'财务对象','payee_name'=>'收款方','opening_bank'=>'开户行','bank_no'=>'银行账号','financial_tax'=>'税点');
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '供应商财务信息'.date('Y-m-d H:i:s'));

	}


	public function edit() {

		if (!$this->checkRule('/Home/Supplier/edit')) {
			$this->error('您没有访问权限,请联系管理员');
		}

		$id = I('get.id', 0);
		$errorMsg = '';
		$supInfo = array();
		if ($id > 0) {
			//修改
			$supInfo = M('Supplier')->field(true)->find($id);
			$contactsData = M('supplier_contacts')->where('sp_id='.$supInfo['id'])->select();
			$buidArr = array();
			foreach ($contactsData as $val) {
				$buidArr[] = $val['business_uid'];
			}
			$uNames = D('user')->getAssoUserName($buidArr);
			foreach ($contactsData as &$val) {
				$val['business_name'] = $uNames[$val['business_uid']];
			}

			$financeData = M('supplier_finance')->where('sp_id='.$supInfo['id'])->select();//财务信息
			$this->assign('data', $supInfo);
			$this->assign('contactsData', $contactsData); //联系人信息
			$this->assign('financeData', $financeData); //财务信息
		} else {

		}

		$this->assign('has_check_auth', $this->checkRule('/Home/Supplier/ischeck')); //权限 是否检查供应商
		$this->assign('op_is_check', C('OPTION.is_check'));

		$this->assign('op_bl', D('BusinessLine')->getField('id,name'));
		$this->assign('op_region', D('Region')->getRegion());
		$this->assign('op_supplier_type', C('OPTION.supplier_type'));
		$this->assign('op_finance_object', C('OPTION.finance_object'));
		$this->assign('op_invoice_type', C('OPTION.invoice_type'));
		$this->display();

	}


	public function update() {
		if(I('post.type')==2){
			if(empty(I('post.region'))){
				$this->ajaxReturn(array('msg'=>'企业类型供应商地区字段为必填', 'go'=>'/Supplier/index'));
			}
		}


		$editId = $supId = I('post.id', 0, 'intval');
		$goUrl = $supId > 0 ? Cookie('__forward__') : U('index');
		//基本信息
		$supModel = D('Supplier');
		if ($supModel->create() === false) {
			$this->ajaxReturn(array('msg'=>$supModel->getStrError()));
		}
		if ($supId > 0) {
			if ($supModel->save() === false) {
				$this->ajaxReturn(array('msg'=>$supModel->getStrError()));
			}
		} else { //新增
			$editId = $insertId = $supModel->add();
			if ($insertId === false) {
				$this->ajaxReturn(array('msg'=>$supModel->getStrError()));
			} else {
				//更新供应商编码
				$_map['id'] = $insertId;
				$_map['code'] = $supModel->generalCode($insertId);
				if ($supModel->save($_map) === false) { //更新失败删除刚添加的供应商
					$supModel->delete($insertId);
					$this->ajaxReturn(array('msg'=>$supModel->getStrError()));
				}

			}
		}

		$redirectUrl = $editId > 0 ? U('edit?id='.$editId) : ''; //如果基本信息添加成功，后面的错误信息后跳转到已添加的编辑页

		//联系人数据
		$contactsModel = D('SupplierContacts');
		foreach ($_POST['contacts'] as $val) {
			$val['sp_id'] = $editId;
			if ($contactsModel->validate($supModel->contactRule)->create($val) === false) {
				$this->ajaxReturn(array('msg'=>$contactsModel->getError(), 'go'=>$redirectUrl));
			}
			if ($val['id'] > 0) {
				$r = $contactsModel->save();
			} else {
				$r = $contactsModel->add();
			}
			if ($r === false) {
				$this->ajaxReturn(array('msg'=>$contactsModel->getError(), 'go'=>$redirectUrl));
			}

		}


		//财务信息
		$spFinanModel = D('SupplierFinance');
		foreach ($_POST['spFinance'] as $val) {
			$val['sp_id'] = $editId;
			if ($spFinanModel->create($val) === false) {
				$this->ajaxReturn(array('msg'=>$spFinanModel->getError(), 'go'=>$redirectUrl));
			}
			if ($val['id'] > 0) {
				$r = $spFinanModel->save();
			} else {
				$r = $spFinanModel->add();
			}
			if ($r === false) {
				$this->ajaxReturn(array('msg'=>$spFinanModel->getError(), 'go'=>$redirectUrl));
			}
			$fukuanname=$val['payee_name'];

			//判断财务信息是否在客户白名单中 start 2017.06.02
			/*$ml = M('white_list');
			if($val['payee_name'] && $val['opening_bank'] && $val['bank_no']) {
				$white_list = $ml->field('opening_bank')->where("name='" . $val['payee_name'] . "' && opening_bank='" . $val['opening_bank'] . "' && bank_no='" . $val['bank_no'] . "'")->find();
				if (empty($white_list)) {//为空则添加
					$add = array();
					$add['name'] = $val['payee_name'];
					$add['opening_bank'] = $val['opening_bank'];
					$add['bank_no'] = $val['bank_no'];
					$add['type'] = 2;
					$ml->add($add);
				}
			}*/
			//end
		}
		if(!empty($fukuanname)){
			$supinfo=M('supplier')->where("id='{$supId}'")->find();
			if($supinfo['type']!=1){
				M('supplier')->where("id='{$supId}'")->save(array('fukuanname'=>$fukuanname));
			}
		}
		
		$retMsg = $supId > 0 ? '供应商修改成功' : '供应商添加成功';
		$this->ajaxReturn(array('msg'=>$retMsg, 'go'=>$goUrl));

	}


	public function detail() {

		$id = I('get.id',0);
		if ($id <=0) {
			$this->error('参数错误');
		}

		$supData = M('supplier')->find($id);
		$supContactData = M('supplier_contacts')->where('sp_id='.$id)->select();
		$buidArr = array();
		foreach ($supContactData as $val) {
			$buidArr[] = $val['business_uid'];
		}
		$uNames = D('user')->getAssoUserName($buidArr);
		foreach ($supContactData as &$val) {
			$val['business_uid'] = $uNames[$val['business_uid']];
		}

		$supFinanceData = M('supplier_finance')->where('sp_id='.$id)->select();

		$this->assign('supdata', $supData);
		$taglist=$tag=json_decode(htmlspecialchars_decode($supData['tag']),true);
		$arr=array('电脑客户端'=>'安装量','网站'=>'日独立用户数','加粉设备'=>'用户覆盖量','电脑预装'=>'安装量','移动预装'=>'安装量','网红推广'=>'粉丝量','电脑端拓展工具'=>'日活跃用户数','移动端拓展工具'=>'日活跃用户数','社群推广'=>'日活跃用户数','微博/博客'=>'用户访问量','群控'=>'微信账号量','ASO刷榜'=>'设备量','下载站'=>'日活跃用户数','竞价排名'=>'指标为空','移动应用'=>'日活跃用户数','商业WiFi'=>'用户覆盖量','公众号'=>'粉丝量','平台联盟'=>'广告展现量','应用商店'=>'日活跃用户数');
		foreach ($taglist as $k => $v) {
			$newarr=array();
			$newarr['name']=$v['media_type'];
			$newarr['value']=$v['resource_scale'];
			$newarr['key']=$arr[$v['media_type']];
			$newtaglist[]=$newarr;
		}
		$this->assign('taglist',$newtaglist);
		$this->assign('contactData', $supContactData);
		$this->assign('financeData', $supFinanceData);

		$this->assign('op_bl', M('business_line')->getField('id,name')); //业务线

		$this->assign('op_region', D('Region')->getRegion());
		$this->assign('op_supplier_type', C('OPTION.supplier_type'));
		$this->assign('op_finance_object', C('OPTION.finance_object'));
		$this->assign('op_invoice_type', C('OPTION.invoice_type'));

		$this->assign('op_is_check', C('OPTION.is_check'));
		$this->assign('has_check_auth', $this->checkRule('/Home/Supplier/ischeck')); //权限 是否检查供应商
		$this->display();

	}


	//删除联系人
	public function deleteContact() {
		$id = I('post.id', 0, 'intval');
		if ($id > 0) {
			$spContactModel = D('SupplierContacts');
			if ($spContactModel->delete($id) === false) {
				$this->ajaxReturn(array('msg'=>$spContactModel->getError(),'error'=>'1'),'JSON');
			}
			$this->ajaxReturn(array('msg'=>'联系人已经删除','error'=>'0'),'JSON');

		} else {
			$this->ajaxReturn(array('msg'=>'参数错误','error'=>'1'),'JSON');
		}
	}

	//删除联系人
	public function deleteFinance() {
		$id = I('post.id', 0, 'intval');
		if ($id > 0) {
			$spFinanceModel = D('SupplierFinance');
			if ($spFinanceModel->delete($id) === false) {
				$this->ajaxReturn(array('msg'=>$spFinanceModel->getError(),'error'=>'1'),'JSON');
			}
			$this->ajaxReturn(array('msg'=>'财务信息已经删除','error'=>'0'),'JSON');

		} else {
			$this->ajaxReturn(array('msg'=>'参数错误','error'=>'1'),'JSON');
		}
	}


	public function chartView() {

		$item = I('get.item',0,'intval');
		$model = M('supplier');

		$res = array();
		$fields = '';
		switch ($item) {
			case 2:
				//大客户占比
				$getBl = I('get.bl', 0, 'intval');
				$whereStr = '';
				if($getBl > 0) {
					$whereStr .= ' AND lineid='.$getBl;
				}
				$sql = <<<EOF
				SELECT tab.level,COUNT(tab.level) AS num FROM
				(SELECT 
				  superid,
				  CASE WHEN SUM(IFNULL(newmoney, money))>1000000 THEN 'big1'
				   WHEN SUM(IFNULL(newmoney, money))>500000 AND SUM(IFNULL(newmoney, money)) <1000000 THEN 'big2'
				   WHEN SUM(IFNULL(newmoney, money))>200000 AND SUM(IFNULL(newmoney, money)) <=500000 THEN 'big3'
				   WHEN SUM(IFNULL(newmoney, money))>100000 AND SUM(IFNULL(newmoney, money)) <2000000 THEN 'big4'
				  ELSE 'big5' END AS `level`
				FROM
				  `boss_daydata_out` 
				WHERE 1=1 {$whereStr}     
				GROUP BY superid) AS tab GROUP BY tab.level
EOF;
				$ress = M()->query($sql);
				$typeArr = array('big1'=>'100+','big2'=>'50~100','big3'=>'20~50','big4'=>'10~20','big5'=>'10-',);
				foreach($ress as $val) {
					$datatype[$val['level']] = $val['num'];
				}
				break;
			case 3:
				//广告主行业分布 (个数)
				$typeArr = array();
				$datatype = array();
				break;
			case 4:
				//客户遗失占比分析
				//合作中
				$hzNum = M()->query("SELECT COUNT(*) AS num FROM (
							SELECT id FROM boss_daydata_out WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())=0 GROUP BY superid
							) AS tab");
				$hzNum = $hzNum[0]['num'];
				//一个月无量级
				$no1= M()->query("SELECT COUNT(*) AS num FROM
						 (SELECT superid FROM boss_daydata_out WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())=1 GROUP BY superid) AS aa RIGHT JOIN
						 (SELECT superid  FROM boss_daydata_out WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())>1 GROUP BY superid) AS bb
						  ON aa.superid=bb.superid WHERE aa.superid IS NULL");
				$no1 = $no1[0]['num'];
				//1~3个月无量级
				$no13 = M()->query("SELECT COUNT(*) AS num FROM
							 (SELECT superid FROM boss_daydata_out WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())>=1 AND TIMESTAMPDIFF(MONTH,`adddate`,NOW()) <=3 GROUP BY superid) AS aa RIGHT JOIN
							 (SELECT superid  FROM boss_daydata_out WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())>3 GROUP BY superid) AS bb
							  ON aa.superid=bb.superid WHERE aa.superid IS NULL");
				$no13 = $no13[0]['num'];
				//3+以上无量级
				$no3 = M()->query("SELECT COUNT(*) AS num FROM 
							 (SELECT superid FROM boss_daydata_out WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())<4 GROUP BY superid) AS aa RIGHT JOIN 
							 (SELECT superid  FROM boss_daydata_out WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())>=4 GROUP BY superid) AS bb
							  ON aa.superid=bb.superid WHERE aa.superid IS NULL");
				$no3 = $no3[0]['num'];

				$typeArr = array(1=>'合作中', 2=>'1月无量级', 3=>'1~3月无量级', 4=>'3+月无量级');
				$datatype = array(1=>$hzNum, 2=>$no1, 3=>$no13, 4=>$no3);
				break;
			case 5:
				//区域分布
				$datatype = $model->group('region')->getField('region,COUNT(id) AS num');
				$ids = implode(',',array_keys($datatype));
				$typeArr = D('Region')->where("id IN ({$ids})")->getField("id,name");
				break;
			case 6:
				//广告主类型占比
				$typeArr = C('OPTION.supplier_type');
				$datatype = $model->group('type')->getField('type,COUNT(id) AS num');
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


	public function chartDetailView() {

		$itemId = I('get.itemid',0,'intval');
		switch ($itemId) {
			case 2:
				$bl = M('business_line')->getField('id,name');
				$this->assign('op_bl', $bl);
				$this->assign('itemid', $itemId);
				$this->display('chart_detail_2');
				break;

			default:
				;
				break;
		}
	}

}