<?php
namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 广告主管理
 * Class AdvertiserController
 * @package Home\Controller
 */
class AdvertiserController extends BaseController {

	/**
	 * 广告主列表
	 */
	public function index(){
		ignore_user_abort();//脱离客户端
		set_time_limit(0);//不限时间执行
		session_write_close();//session解锁
		//列表数据
		$name             = I('get.name','','urldecode');
		$ad_type          = I('get.ad_type',0);
		$province_id      = I('get.province_id',0);
		$add_time         = I('get.add_time','');
		$bb_type          = I('get.bb_type','');
		$aciid            = I('get.aciid');
		$map["hz_status"] = I('hz_status');
		$this->assign("map",$map);

		$where[] = "a.status=1";
		if($aciid){
			$where[] = "a.id in ($aciid)";
		}
		if (!empty($name)) {
			$where[] = "a.name like '%{$name}%'";
		}
		if ($ad_type > 0) {
			$where[] = 'a.ad_type='.$ad_type;
		}else{
			$where[] = "a.ad_type > 0";
		}
		if ($province_id > 0) {
			$where[] = 'a.province_id='.$province_id;
		}
		if ($add_time) {
			$where['_string'] = "DATE_FORMAT(a.add_time,'%Y-%m')='{$add_time}'";
		}
		$adGrade = I('get.ad_grade','');
		if (!empty($adGrade)) {
			$where['ad_grade'] = array('like',"%{$adGrade}%");
		}
		$ad_credit = I('get.ad_credit','');
		if (!empty($ad_credit)) {
			$where['ad_credit'] = array('like',"%{$ad_credit}%");
		}
		$isCheck = I('get.is_check','','trim');
		if (in_array($isCheck,array('0','1'))) {
			$where[] = 'a.is_check='.$isCheck;
		}

		//合作状态
		if($map["hz_status"]){
			$hz_status_s = $map["hz_status"]==1?"1,2":"3";
			$where[] = "b.cooperate_state in ({$hz_status_s})";
		}


		$start_time = date('Y-m-01', strtotime('-1 month'));//上月
		$end_time   = date('Y-m-t', strtotime('-1 month'));//上月
		$s_time     = date('Y-m-01', strtotime('-2 month'));//上上个月
		$e_time     = date('Y-m-t', strtotime('-2 month'));//上上个月

		if($bb_type && $bb_type == 'yx'){
			$advid = M()->query("SELECT adverid FROM boss_daydata WHERE adddate >='".$start_time."' AND adddate <='".$end_time."' GROUP BY adverid");
			$adv_id = "";
			foreach($advid as $key=>$val){
				$adv_id .= $val['adverid'].",";
			}
			$adid = rtrim($adv_id, ",");
			if($adid){
				$where[] = "a.id in ($adid)";
			}else{
				$where[] ='1=0';
			}

		}elseif($bb_type && $bb_type == 'xz'){
			$BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));//当月
			$EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));//当月
			$xzData = M()->query("select id from boss_advertiser where DATE_FORMAT(add_time,'%Y-%m-%d') >='".$BeginDate."' and DATE_FORMAT(add_time,'%Y-%m-%d') <='".$EndDate."'");
			$advids = "";
			foreach($xzData as $key=>$val){
				$advids .= $val['id'].",";
			}
			$adid = rtrim($advids, ",");
			if($adid){
				$where[] = "a.id in ($adid)";
			}else{
				$where[] ='1=0';
			}


		}elseif($bb_type && $bb_type == 'zt'){
			$advData = M()->query("SELECT adverid FROM boss_daydata WHERE adddate >='".$s_time."' AND adddate <='".$e_time."' AND adverid NOT IN (SELECT adverid FROM boss_daydata WHERE adddate >='".$start_time."' AND adddate <='".$end_time."' GROUP BY adverid) GROUP BY adverid");
			$advid = "";
			foreach($advData as $key=>$val){
				$advid .= $val['adverid'].",";
			}
			$adid_zt = rtrim($advid, ",");
			if($adid_zt){
				$where[] = "a.id in ($adid_zt)";
			}else{
				$where[] ='1=0';
			}

		}

		$list = $this->lists('Advertiser', $where);
		$this->assign('list', $list);
		$this->assign('op_status', C('OPTION.function_status'));
		$this->assign('op_ad_type', C('OPTION.ad_type'));
		$this->assign('op_region',D('Region')->getRegion());
		$this->assign('op_is_check', C('OPTION.is_check'));
		$this->assign('op_ad_grade', C('OPTION.ad_grade'));
		$this->assign('hz_status', C('OPTION.hz_status'));

		Cookie('__forward__',$_SERVER['REQUEST_URI']);

		//检查当前用户有查看检查项权限 udpate 0707 tgd
		$isHas_check = $_SESSION["sec_/Home/Advertiser/auth_ischeck"];
		if(!$isHas_check){
			$isHas_check = isHasAuthToQuery("/Home/Advertiser/auth_ischeck",UID);
			$_SESSION["sec_/Home/Advertiser/auth_ischeck"]  = $isHas_check;
		}
		$this->assign('isHas_check',$isHas_check);

		$this->display();
	}


	/**
	 * 导出数据
	 */
	public function export() {

		$name = I('get.name', '', 'urldecode');
		$ad_type = I('get.ad_type',0);
		$province_id = I('get.province_id',0);
		$add_time = I('get.add_time','');

		$where = array();
		$where['status'] = 1;
		$adGrade = I('get.ad_grade','');
		if (!empty($adGrade)) {
			$where['ad_grade'] = array('like',"%{$adGrade}%");
		}
		$ad_credit = I('get.ad_credit','');
		if (!empty($ad_credit)) {
			$where['ad_credit'] = array('like',"%{$ad_credit}%");
		}
		if (!empty($name)) {
			$where['name'] = array('like',"%{$name}%");
		}
		if ($ad_type > 0) {
			$where['ad_type'] = $ad_type;
		}
		if ($province_id > 0) {
			$where['province_id'] = $province_id;
		}
		if ($add_time) {
			$where['_string'] = "DATE_FORMAT(add_time,'%Y-%m')='{$add_time}'";
		}
		$isCheck = I('get.is_check','','trim');
		if (in_array($isCheck,array('0','1'))) {
			$where['is_check'] = $isCheck;
		}

		$adList = M('advertiser')
			->field('id,ad_code,name,province_id,ad_type,is_internal,add_time,ad_grade,ad_credit,is_check,status,object_type,
			account_name,opening_bank,bank_no,invoice_type,invoice_remark,taxpayer_num,reg_address,reg_mobile')
			->where($where)
			->order('id desc')
			->select();

		foreach ($adList as $key=>$val) {
			$aids[] = $val['id'];

			$advers_products_list = D('Advertiser')->getProductsByadvers($val['id']);
			$product_list = $advers_products_list[$val["id"]];
			$hz_status = "";
			foreach ($product_list as $kp => $vp) {
				if($vp["cooperate_state"]==1 || $vp["cooperate_state"]==2){
					$hz_status = "合作中";
				}
				if($vp["cooperate_state"]==3){
					$hz_status = "已暂停";
				}
			}
			$adList[$key]['hz_status'] = $hz_status;
		}
		//对接人及电话
		$contactsData = M('advertiser_contacts')
			->field('ad_id,name,mobile,user')
			->where(array('ad_id'=>array('in',$aids)))
			->select();
		$contactArr = array();
		$userData = array();
		if(!empty($contactsData)) {
			foreach($contactsData as $val) {
				$contactArr[$val['ad_id']][] = $val['name'].'|'.$val['mobile'];
				$userData[$val['ad_id']][] = $val['user'];
			}
			unset($contactsData);
		}
		$adType = C('OPTION.ad_type');
		$op_region=D('Region')->getRegion();
		$opFinanceObject = C('OPTION.finance_object');
		$opInvoiceType = C('OPTION.invoice_type');
		foreach ($adList as &$val) {
			$val['province_id'] = $op_region[$val['province_id']];
			$val['contacts'] = empty($contactArr[$val['id']]) ? ' ' : implode(',',$contactArr[$val['id']]);
			$val['we_dock'] = empty($userData[$val['id']]) ? ' ' : implode(',',$userData[$val['id']]);
			$val['ad_type'] = $adType[$val['ad_type']];//广告主类型
			$val['status'] = $val['status'] == 0 ? '禁用' : '启用';
			$val['is_check'] = $val['is_check'] == 0 ? '否' : '是';
			$val['object_type'] = $opFinanceObject[$val['object_type']];//财务类型
			$val['invoice_type'] = $opInvoiceType[$val['invoice_type']];//发票类型
		}

		$title = array('id'=>'序号','ad_code'=>'广告主编码','name'=>'广告主名称','province_id'=>'地区','ad_type'=>'类型',
			'add_time'=>'添加时间','contacts'=>'对接人','we_dock'=>'我方对接人','is_check'=>'是否检查','status'=>'状态',
			'object_type'=>'账户类型','account_name'=>'账户名称','opening_bank'=>'开户行名称','bank_no'=>'开户行账号','invoice_type'=>'发票类型','invoice_remark'=>'开票内容',
			'taxpayer_num'=>'纳税人识别号','reg_address'=>'注册地址','reg_mobile'=>'注册电话','ad_grade'=>'实力评级','ad_credit'=>'信用评级','hz_status'=>'合作状态');
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($adList, $title, '广告主'.date('Y-m-d H:i:s'));

	}


	/**
	 * 地区选择
	 */
	public function region ($pid=1) {
		$pid = I('get.pid', 0 , 'intval');
		$res = D('Region')->getRegion($pid);
		$this->ajaxReturn($res);
	}


	public function edit() {

		$id = I('get.id', 0);
		$errorMsg = '';
		$adInfo = array();
		if ($id > 0) {
			//修改
			$admodel = D('Advertiser');
			$adInfo = $admodel->getById($id);
			if (empty($adInfo)) {
				$this->error('未找到相应广告主');
			}

			$adFinReceiver = M('advertiser_fireceiver')->where('ad_id='.$id)->select(); //财务收件人
			$adContactInfo = M('advertiser_contacts')->where('ad_id='.$id)->select();
			$this->assign('adInfo', $adInfo);
			$this->assign('adContactInfo', $adContactInfo);
			$this->assign('adFinReceiver', $adFinReceiver);
			//评级选项
			$this->assign('op_ad_rating', C('OPTION.ad_rating'));
			$this->assign('op_ad_capital', C('OPTION.ad_capital'));

		} else {

		}
 
		$this->assign('hasauth_edit_ne', $id<=0||$this->checkRule('/Home/adNameEmailEdit')?1:0);//修改时候 广告主名称邮箱修改权限
		$this->assign('has_check_auth', $this->checkRule('/Home/Advertiser/ischeck')); //权限 是否检查广告主
		$this->assign('op_is_check', C('OPTION.is_check'));
		$this->assign('op_region', D('Region')->getInitData($adInfo['province_id'],$adInfo['city_id']));
		$this->assign('op_adtype', C('OPTION.ad_type'));
		$this->assign('op_finance_object', C('OPTION.finance_object'));
		$this->assign('op_invoice_type', C('OPTION.invoice_type'));
		$this->assign('op_invoice_remark', D('DataDic')->getInvoiceRemark());
		$this->assign('op_bl', D('BusinessLine')->getField('id,name'));
		$this->assign('op_ad_rating', C('OPTION.ad_rating'));
		$this->display();

	}


	public function update() {
		$isRedirect = false;
		$editId = $adid = I('post.id', 0, 'intval');

		//基本信息
		$advModel = D('Advertiser');
		/*if(!empty($_FILES['qualif_img']['tmp_name'])){ //是否上传操作
			$qualiInfo = $this->uplaodfile('qualif_img', UPLOAD_INMONEY_ADVERTISER_PATH);
			if (!is_array($qualiInfo)) {
				$this->ajaxReturn(array('msg'=>$qualiInfo));
			}
			$filepath = UPLOAD_INMONEY_ADVERTISER_PATH .$qualiInfo['qualif_img']['savepath'].$qualiInfo['qualif_img']['savename'];
			$_POST['qualif_img'] = $filepath;
		}*/

		if ($advModel->create() === false) {
			$this->ajaxReturn(array('msg'=>$advModel->getError()));
		}

		if(empty($_POST['contacts'])) {
			$this->ajaxReturn(array('msg'=>'广告主对接人信息不能为空'));
		}

		//修改
		if ($adid > 0) {
			if ($advModel->save() === false) {
				$this->ajaxReturn(array('msg'=>$advModel->getError()));
			}
		} else { //新增
			$editId = $insertId = $advModel->add();
			if ($insertId === false) {
				$this->ajaxReturn(array('msg'=>$advModel->getError()));
			} else {
				//更新广告主编码
				$_map['id'] = $insertId;
				$_map['ad_code'] = $advModel->generalCode($insertId);
				if ($advModel->save($_map) === false) { //更新失败删除刚添加的广告主
					$advModel->delete($insertId);
					$this->ajaxReturn(array('msg'=>$advModel->getError()));
				}

			}
			$isRedirect = true;
		}
		//判断财务信息是否在白名单中 start 2017.06.02
		/*if($_POST['name'] && $_POST['opening_bank'] && $_POST['bank_no']) {
			$white_list = M('white_list')->field('opening_bank')->where("name='" . $_POST['name'] . "' && opening_bank='" . $_POST['opening_bank'] . "' && bank_no='" . $_POST['bank_no'] . "'")->find();
			if (empty($white_list)) {//为空则添加
				$add = array();
				$add['name'] = $_POST['name'];
				$add['opening_bank'] = $_POST['opening_bank'];
				$add['bank_no'] = $_POST['bank_no'];
				$add['type'] = 1;
				M('white_list')->add($add);
			}
		}*/
		//end

		$redirectUrl = $isRedirect ? U('edit?id='.$editId) : ''; //如果基本信息添加成功，后面的错误信息后跳转到已添加的编辑页

		//财务接受人信息
		$financeReceiver = (array)$_POST['finance_receiver'];
		if (!empty($financeReceiver)) {
			$frModel = M('advertiser_fireceiver');
			foreach ($financeReceiver as $item) {
				$item['ad_id'] = $editId;

				if ($frModel->validate($advModel->receiverRule)->create($item) === false) {
					$this->ajaxReturn(array('msg'=>$frModel->getError(),'go'=>$redirectUrl));
				}
				if ($item['id'] > 0) {
					$r = $frModel->save();
				} else {
					$r = $frModel->add();
				}
				if ($r === false) {
					$this->ajaxReturn(array('msg'=>$frModel->getError(),'go'=>$redirectUrl));
				}
			}
		}

		//联系人数据
		$contactsModel = D('AdvertiserContacts');
		foreach ($_POST['contacts'] as $val) {
			$val['ad_id'] = $editId;
			if ($contactsModel->create($val) === false) {
				$this->ajaxReturn(array('msg'=>$contactsModel->getError(),'go'=>$redirectUrl));
			}
			$isExist = $contactsModel->where('id='.intval($val['id']))->count();
			if ($isExist > 0) {
				$r = $contactsModel->save();
			} else {
				$r = $contactsModel->add();
			}
			if ($r === false) {
				$this->ajaxReturn(array('msg'=>$contactsModel->getError(),'go'=>$redirectUrl));
			}

		}

		$retMsg = $adid	> 0 ? '广告主修改成功' : '广告主添加成功';
		$goUrl = $adid > 0 ? Cookie('__forward__') : U('index');
		action_log('partner', 'info', $_SESSION['userinfo']['realname'], $retMsg.'adid='.$editId, CONTROLLER_NAME.'/'.ACTION_NAME);//日志
		$this->ajaxReturn(array('msg'=>$retMsg,'go'=>$goUrl));

	}


	public function detail() {

		$id = I('get.id',0);
		if ($id <=0) {
			$this->error('参数错误');
		}

		$adInfo = D('Advertiser')->getById($id);
		$adContactInfo = M('advertiser_contacts')->where('ad_id='.$id)->select();
		$adFinReceiver = M('advertiser_fireceiver')->where('ad_id='.$id)->select(); //财务收件人
		$this->assign('adFinReceiver',$adFinReceiver);
		//相关产品信息
		$productInfo = D('product')->field(true)->where('ad_id='.$id)->order('id desc')->limit(10)->select();
		//
		$this->assign('op_adtype', C('OPTION.ad_type'));
		$this->assign('op_finance_object', C('OPTION.finance_object'));
		$this->assign('op_invoice_type', C('OPTION.invoice_type'));
		$this->assign('op_product_type', C('OPTION.product_type'));
		//评级选项
		$this->assign('op_ad_rating', C('OPTION.ad_rating'));
		$this->assign('op_ad_capital', C('OPTION.ad_capital'));
		/*$this->assign('data_blname', D('BusinessLine')->where('id='.$proInfo['bl_id'])->getField('name'));
		$this->assign('data_sign_body', D('DataDic')->where('dic_type=4 AND id='.$proInfo['sb_id'])->getField('name'));//签订主体名称*/
		$this->assign('op_sb',D('DataDic')->getSignBody());
		$this->assign('op_bl',M('business_line')->getField('id,name'));
		$this->assign('op_region', D('Region')->getInitName($adInfo['province_id'], $adInfo['city_id'], $adInfo['district_id']) );
		$this->assign('adInfo', $adInfo);
		$this->assign('productInfo', $productInfo);
		$this->assign('adContactInfo', $adContactInfo);
		$this->display();

	}


	public function doUpload() {
		$info = uploadify(UPLOAD_INMONEY_ADVERTISER_PATH);
		$this->ajaxReturn($info);
	}


	//评级
	function doRating() {

		if(checkRule('/Home/advertiserRating')) {
			$cronAdv = A('Cron/Advertiser');
			$res = $cronAdv->adLevel();
			$ret = $res === true ? 0 : $res;
			$this->ajaxReturn($ret);
		}

	}


	//删除联系人
	public function deleteContact() {
		$id = I('post.id', 0, 'intval');
		if ($id > 0) {
			$adConModel = D('AdvertiserContacts');
			if ($adConModel->delete($id) === false) {
				$this->ajaxReturn(array('msg'=>$adConModel->getError(),'error'=>'1'),'JSON');
			}
			$this->ajaxReturn(array('msg'=>'联系人已经删除','error'=>'0'),'JSON');

		} else {
			$this->ajaxReturn(array('msg'=>'参数错误','error'=>'1'),'JSON');
		}
	}


	//删除财务收件人
	public function deleteReceiver() {
		$id = I('post.id', 0, 'intval'); //广告主id
		if ($id > 0) {
			$adFiRecModel = M('advertiser_fireceiver');
			$isExist = $adFiRecModel->where('id='.$id)->count();
			if($isExist > 0) {
				if ($adFiRecModel->delete($id) === false) {
					$this->ajaxReturn(array('msg'=>$adFiRecModel->getError(),'error'=>'1'),'JSON');
				}
				$this->ajaxReturn(array('msg'=>'收件人已经删除','error'=>'0'),'JSON');
			}
		}
		$this->ajaxReturn(array('msg'=>'参数错误','error'=>'1'),'JSON');
	}


	/*
	 * 删除广告主(禁用)
	 */
	public function delete() {

		$id = I('get.id', 0, 'intval');
		if ($id > 0) {
			$adModel = D('Advertiser');
			$status = $adModel->where('id='.$id)->getField('status');
			$_map['status'] = (int)$status > 0 ? 0 : 1;
			$_map['id'] = $id;
			if ($adModel->save($_map) === false) {
				$this->error($adModel->getError());
			}
			$_msg = $status==1 ? '禁用' : '启用';
			$ret = array(
				'msg'=>'广告主已'.$_msg,
				'status'=>$_map['status'],
			);
			action_log('partner', 'info', $_SESSION['userinfo']['realname'], $ret['msg'].'adid='.$id, CONTROLLER_NAME.'/'.ACTION_NAME);//日志
			$this->ajaxReturn($ret);
		} else {
			$ret = array(
				'msg'=>'参数错误',
				'status'=>-1,
			);
			$this->ajaxReturn($ret);
		}

	}


	public function chartView() {
		
		$item = I('get.item',0,'intval');
		$model = M('Advertiser');

		// print_r(1);exit;
		$res = array();
		$fields = '';
		switch ($item) {
			case 1:
				//广告主等级占比
				$typeArr = C('OPTION.ad_grade');
				$datatype = $model->group('ad_grade')->getField('ad_grade,COUNT(id) AS num');
				break;
			case 2:
				$getBl = I('get.bl', 0, 'intval');
				$whereStr = '';
				if($getBl > 0) {
					$whereStr .= ' AND lineid='.$getBl;
				}
				//大客户占比
				$sql = <<<EOF
				SELECT tab.level,COUNT(tab.level) AS num FROM
				(SELECT 
				  adverid,
				  CASE WHEN SUM(IFNULL(newmoney, money))>1000000 THEN 'big1'
				   WHEN SUM(IFNULL(newmoney, money))>500000 AND SUM(IFNULL(newmoney, money)) <1000000 THEN 'big2'
				   WHEN SUM(IFNULL(newmoney, money))>200000 AND SUM(IFNULL(newmoney, money)) <=500000 THEN 'big3'
				   WHEN SUM(IFNULL(newmoney, money))>100000 AND SUM(IFNULL(newmoney, money)) <2000000 THEN 'big4'
				  ELSE 'big5' END AS `level`
				FROM
				  `boss_daydata` 
				WHERE 1=1 {$whereStr}   
				GROUP BY adverid) AS tab GROUP BY tab.level
EOF;
				$ress = M()->query($sql);
				$typeArr = array('big1'=>'100+','big2'=>'50~100','big3'=>'20~50','big4'=>'10~20','big5'=>'10-',);
				foreach($ress as $val) {
					$datatype[$val['level']] = $val['num'];
				}
				break;
			case 3:
				//广告主行业分布 (个数)
				$typeArr = M('data_dic')->where('dic_type=7')->getField('id,name');
				$datatype = M('product')->group('category')->getField('category,COUNT(id) AS num');
				break;
			case 4:
				//客户遗失占比分析
				//合作中
				$hzNum = M()->query("SELECT COUNT(*) AS num FROM (
						SELECT id FROM boss_daydata WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())=0 GROUP BY adverid
						) AS tab");
				$hzNum = $hzNum[0]['num'];
				//一个月无量级
				$no1= M()->query("SELECT COUNT(*) AS num FROM
						 (SELECT adverid FROM boss_daydata WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())=1 GROUP BY adverid) AS aa RIGHT JOIN
						 (SELECT adverid  FROM boss_daydata WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())>1 GROUP BY adverid) AS bb
						  ON aa.adverid=bb.adverid WHERE aa.adverid IS NULL");
				$no1 = $no1[0]['num'];
				//1~3个月无量级
				$no13 = M()->query("SELECT COUNT(*) AS num FROM
							 (SELECT adverid FROM boss_daydata WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())>=1 AND TIMESTAMPDIFF(MONTH,`adddate`,NOW()) <=3 GROUP BY adverid) AS aa RIGHT JOIN
							 (SELECT adverid  FROM boss_daydata WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())>3 GROUP BY adverid) AS bb
							  ON aa.adverid=bb.adverid WHERE aa.adverid IS NULL");
				$no13 = $no13[0]['num'];
				//3+以上无量级
				$no3 = M()->query("SELECT COUNT(*) AS num FROM 
							 (SELECT adverid FROM boss_daydata WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())<4 GROUP BY adverid) AS aa RIGHT JOIN 
							 (SELECT adverid  FROM boss_daydata WHERE TIMESTAMPDIFF(MONTH,`adddate`,NOW())>=4 GROUP BY adverid) AS bb
							  ON aa.adverid=bb.adverid WHERE aa.adverid IS NULL");
				$no3 = $no3[0]['num'];

				$typeArr = array(1=>'合作中', 2=>'1月无量级', 3=>'1~3月无量级', 4=>'3+月无量级');
				$datatype = array(1=>$hzNum, 2=>$no1, 3=>$no13, 4=>$no3);
				break;
			case 5:
				//区域分布
				$datatype = $model->group('province_id')->getField('province_id,COUNT(id) AS num');
				$ids = implode(',',array_keys($datatype));
				$typeArr = D('Region')->where("id IN ({$ids})")->getField("id,name");
				break;
			case 6:
				//广告主类型占比
				$typeArr = C('OPTION.ad_type');
				$datatype = $model->group('ad_type')->getField('ad_type,COUNT(id) AS num');
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


	public function getAdContacts() {
		$adId = I('get.adid');
		$retData = array();
		if ($adId > 0) {
			$retData = M('advertiser_contacts')->field('name,mobile,qq,email')->where('ad_id='.$adId)->select();
		}
		$this->ajaxReturn($retData);
	}

	public function tag(){
		//标签管理
		$pid=I('get.pid',0);
		$count=M('tag')->where('pid='.$pid)->count();
        $this->getpagelist($count);
        $p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$this->list=M('tag')->where('pid='.$pid)->limit($str.',10')->select();
		$this->display();
	}
	public function addtag(){
		//添加标签
		$this->data=M('tag')->where("pid=0")->select();
		$this->display();
	}
	public function addtag_do(){
		M('tag')->add(array('tagname'=>I('post.tagname'),'pid'=>I('post.pid')));
		$this->success('添加成功','/Advertiser/tag');
	}
	public function edittag(){
		$this->pdata=M('tag')->where("pid=0")->select();
		$this->data=M('tag')->where("id=".I('get.id'))->find();
		$this->display();
	}
	public function edittag_do(){
		M('tag')->where("id=".I('post.id'))->save(array('pid'=>I('post.pid'),'tagname'=>I('post.tagname')));
		$this->success('修改成功','/Advertiser/tag');
	}
	public function deltag(){
		M('tag')->where("id=".I('get.id'))->delete();
		$this->success('删除成功','/Advertiser/tag');
	}

}