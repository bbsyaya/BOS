<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 产品管理
 * Class ProductController
 * @package Home\Controller
 */
class ProductController extends BaseController {

	public function index(){
		$this->assign('sb_id',D('DataDic')->getSignBody());
		$this->assign('product_type', C('OPTION.product_type'));
		$this->assign('order_type', C('OPTION.order_type'));
		$this->assign('source_type', C('OPTION.order_source_type'));
		$this->assign('order_cooperate_state', C('OPTION.order_cooperate_state'));
		$this->assign('is_check', C('OPTION.is_check'));

		/*$options = array(
			array('type'=>'a', 'id'=>'', 'class'=>'', 'title'=>'新增', 'url'=>U('edit')),
			array('type'=>'a', 'id'=>'doExport', 'class'=>'', 'title'=>'导出', 'url'=>'javascript:;'),
		);
		$this->assign('toolOptions', $options);*/

		/*$search1Options = array(
			array('title'=>'广告主名称','name'=>'ad_name','type'=>'text'),
			array('title'=>'产品名称','name'=>'name','type'=>'text'),
			array('type'=>'select','title'=>'结算主体','name'=>'sb_id','class'=>'selectpicker','options'=>D('DataDic')->getSignBody(),'value'=>$_REQUEST['sb_id']),
			array('title'=>'归属销售','name'=>'saler_id','type'=>'text'),
		);
		$this->assign('search1Options', $search1Options);*/

		/*$searchOptions = array(

			array('title'=>'产品类型','name'=>'product_type','type'=>'select','class'=>'selectpicker','options'=>C('OPTION.product_type'), 'value'=>$_REQUEST['product_type']),
			array('title'=>'订单类型','name'=>'order_type','type'=>'select','class'=>'selectpicker','options'=>C('OPTION.order_type'), 'value'=>$_REQUEST['order_type']),
			array('title'=>'来源类型','name'=>'source_type','type'=>'select','class'=>'selectpicker','options'=>C('OPTION.order_source_type'), 'value'=>$_REQUEST['order_source_type']),
			array('title'=>'合作状态','name'=>'cooperate_state','type'=>'select','class'=>'selectpicker','options'=>C('OPTION.order_cooperate_state'), 'value'=>$_REQUEST['cooperate_state']),
			array('title'=>'添加时间','name'=>'add_time','type'=>'date', 'format'=>'yyyy-MM', 'value'=>$_REQUEST['add_time'] ),
			array('title'=>'是否检查','name'=>'is_check','type'=>'select','class'=>'selectpicker','options'=>C('OPTION.is_check'), 'value'=>$_REQUEST['cooperate_state']),
		);
		$this->assign('searchOptions', $searchOptions);*/

		$where = array();
		$id = I('get.id');
		if($id){
			$where[] = "id in ($id)";
		}
		//个别销售只能看自己的数据
		
		//ad_id 广告主更多链接
		$getAdid = I('get.ad_id',0,'intval');
		if ($getAdid) {
			$where['ad_id'] = $getAdid;
		}
		$getAdName = I('get.ad_name','');
		if ($getAdName) {
			$adid = M('advertiser')->where("name Like '%{$getAdName}%'")->getField('id',true);
			$adid = empty($adid) ? array(0) : $adid;
			$where['ad_id'] = array('in', $adid);
		}
		$getProName = I('get.name','');
		if ($getProName) {
			$where['name'] = array('LIKE',"%{$getProName}%");
		}
		$sbid = I('get.sb_id','');
		if ($sbid) {
			//$sbid = M('data_dic')->where("dic_type=4 AND name Like '%{$getAdName}%'")->getField('id');
			$where['sb_id'] = $sbid;
		}
		$getSalerName = I('get.saler_id','');
		if ($getSalerName) {
			$salerid = M('user')->where("real_name Like '%{$getSalerName}%'")->getField('id');
			$where['saler_id'] = $salerid;
		}
		$getType = I('get.product_type',0,'intval');
		if ($getType > 0) {
			$where['type'] = $getType;
		}
		$getOrderType = I('get.order_type',0,'intval');
		if ($getOrderType > 0) {
			$where['order_type'] = $getOrderType;
		}
		$getSourceType = I('get.source_type',0,'intval');
		if ($getSourceType > 0) {
			$where['source_type'] = $getSourceType;
		}
		$getCooState = I('get.cooperate_state',0,'intval');
		if ($getCooState > 0) {
			$where['cooperate_state'] = $getCooState;
		}
		$getAddTime = I('get.add_time','');
		if ($getAddTime) {
			$where['_string'] = "DATE_FORMAT(add_time,'%Y-%m')='{$getAddTime}'";
		}
		$isCheck = I('get.is_check','','trim');
		if (in_array($isCheck,array('0','1'))) {
			$where['is_check'] = $isCheck;
		}
		$bb_type = I('get.bb_type','');
		$BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));//当月
		$EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));//当月
		if($bb_type == 'yx'){
			$where[] = "cooperate_state IN (1,2)";
		}elseif($bb_type == 'xz'){
			$xzData = M()->query("select id from boss_product where cooperate_state=2 AND DATE_FORMAT(add_time,'%Y-%m-%d')>='".$BeginDate."' AND DATE_FORMAT(add_time,'%Y-%m-%d')<='".$EndDate."'");
			$advids = "";
			foreach($xzData as $key=>$val){
				$advids .= $val['id'].",";
			}
			$adid = rtrim($advids, ",");
			if($adid){
				$where[] = "id in ($adid)";
			}else{
				$where['id'] ='';
			}


		}elseif($bb_type == 'zt'){//当月测试通过产品
			$csData = M('product_state')->field('pr_id')->where("cooperate_state=1 AND DATE_FORMAT(add_date,'%Y-%m-%d')>='".$BeginDate."' AND DATE_FORMAT(add_date,'%Y-%m-%d')<='".$EndDate."'")->select();
			$advids = "";
			foreach($csData as $key=>$val){
				$advids .= $val['pr_id'].",";
			}
			$adid = rtrim($advids, ",");
			if($adid){
				$where[] = "id in ($adid)";
			}else{
				$where['id'] ='';
			}


		}elseif($bb_type == 'wtg'){
			$ttData = M('product_state')->field('pr_id')->where("cooperate_state=3 AND DATE_FORMAT(add_date,'%Y-%m-%d')>='".$BeginDate."' AND DATE_FORMAT(add_date,'%Y-%m-%d')<='".$EndDate."'")->select();
			$advids = "";
			foreach($ttData as $key=>$val){
				$advids .= $val['pr_id'].",";
			}
			$adid = rtrim($advids, ",");
			if($adid){
				$where[] = "id in ($adid)";
			}else{
				$where['id'] = '';
			}


		}elseif($bb_type == 'lj_wtg'){
			$ljData = M('product_state')->field('pr_id')->where("cooperate_state=3")->group('pr_id')->select();
			$advids = "";
			foreach($ljData as $key=>$val){
				$advids .= $val['pr_id'].",";
			}
			$adid = rtrim($advids, ",");
			if($adid){
				$where[] = "id in ($adid)";
			}else{
				$where['id'] = '';
			}
		}


		//临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"]);
		if($isRead){
			$where['saler_id'] = $_SESSION["userinfo"]["uid"];
		}
		//数据权限
        $arr_name=array();
        $arr_name['line']=array('bl_id');
        $arr_name['user']=array('saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where['_string']= $myrule_data;
		// print_r($where);exit;

		$list = $this->lists('Product', $where);
		//数据显示过滤
		if(!empty($list)){
			$adid = $saler = array();
			foreach($list as $val) {
				$adid[] = $val['ad_id'];
				$saler[] = $val['saler_id'];
				$sb[] = $val['sb_id'];
			}
			$adid = implode(',',$adid);
			$saler = implode(',',$saler);
			$sb = implode(',',$sb);
			$adNames = M('advertiser')->where("id IN ({$adid})")->getField('id,name');
			$salerNames = M('user')->where("id IN ({$saler})")->getField('id,real_name');
			$sbNames = M('data_dic')->where("id IN ({$sb})")->getField('id,name');
			foreach ($list as &$val) {
				$val['sb_id'] = $sbNames[$val['sb_id']];
				$val['ad_id'] = $adNames[$val['ad_id']];
				$val['saler_id'] = $salerNames[$val['saler_id']];
			}
		}

		$this->assign('list', $list);

		$this->assign('op_order_cooperate_state', C('OPTION.order_cooperate_state'));
		$this->assign('op_product_type', C('OPTION.product_type'));
		$this->assign('op_order_type', C('OPTION.order_type'));
		$this->assign('op_order_source_type', C('OPTION.order_source_type'));
		$this->assign('op_is_internal', C('OPTION.is_internal'));
		$this->assign('op_category', D('DataDic')->where('dic_type=7')->getField('id,name'));//明细分类
		Cookie('__forward__',$_SERVER['REQUEST_URI']);

		//检查当前用户有查看检查项权限 udpate 0707 tgd
		$isHas_check = $_SESSION["sec_/Home/Product/auth_ischeck"];
		if(!$isHas_check){
			$isHas_check = isHasAuthToQuery("/Home/Product/auth_ischeck",UID);
			$_SESSION["sec_/Home/Product/auth_ischeck"] = $isHas_check;
		}
		$this->assign('isHas_check',$isHas_check);
		
		$this->display();

	}


	public function export() {

		$where = array();
		//ad_id 广告主更多链接
		$getAdid = I('get.ad_id',0,'intval');
		if ($getAdid) {
			$where['ad_id'] = $getAdid;
		}
		$getAdName = I('get.ad_name','');
		if ($getAdName) {
			$adid = M('advertiser')->where("name Like '%{$getAdName}%'")->getField('id');
			$where['ad_id'] = $adid;
		}
		$getProName = I('get.name','');
		if ($getProName) {
			$where['name'] = array('LIKE',"%{$getProName}%");
		}
		$getSbName = I('get.sb_id','');
		if ($getSbName) {
			$sbid = M('data_dic')->where("dic_type=4 AND name Like '%{$getAdName}%'")->getField('id');
			$where['sb_id'] = $sbid;
		}
		$getSalerName = I('get.saler_id','');
		if ($getSalerName) {
			$salerid = M('user')->where("real_name Like '%{$getSalerName}%'")->getField('id');
			$where['saler_id'] = $salerid;
		}
		$getType = I('get.product_type',0,'intval');
		if ($getType > 0) {
			$where['type'] = $getType;
		}
		$getOrderType = I('get.order_type',0,'intval');
		if ($getOrderType > 0) {
			$where['order_type'] = $getOrderType;
		}
		$getCooState = I('get.cooperate_state',0,'intval');
		if ($getCooState > 0) {
			$where['cooperate_state'] = $getCooState;
		}
		$getAddTime = I('get.add_time','');
		if ($getAddTime) {
			$where['_string'] = "DATE_FORMAT(add_time,'%Y-%m')='{$getAddTime}'";
		}

		$model = M('product');
		$list = $model->field('id,`code`,`name`,sb_id,ad_id,`type`,category,`order_type`,saler_id,cooperate_state,add_time,source_type')->where($where)->select();
		//数据显示过滤
		if(!empty($list)){
			$adid = $saler = array();
			foreach($list as $val) {
				$adid[] = $val['ad_id'];
				$saler[] = $val['saler_id'];
				$sb[] = $val['sb_id'];
			}
			$adid = implode(',',$adid);
			$saler = implode(',',$saler);
			$sb = implode(',',$sb);
			$adNames = M('advertiser')->where("id IN ({$adid})")->getField('id,name');
			$salerNames = M('user')->where("id IN ({$saler})")->getField('id,real_name');
			$sbNames = M('data_dic')->where("id IN ({$sb})")->getField('id,name');
			$op_product_type = C('OPTION.product_type');
			$op_category = D('DataDic')->where('dic_type=7')->getField('id,name');
			$op_order_cooperate_state = C('OPTION.order_cooperate_state');
			$op_order_type = C('OPTION.order_type');
			$order_source_type = C('OPTION.order_source_type');
			foreach ($list as &$val) {
				$val['sb_id'] = $sbNames[$val['sb_id']];
				$val['ad_id'] = $adNames[$val['ad_id']];
				$val['saler_id'] = $salerNames[$val['saler_id']];
				$val['type'] = $op_product_type[$val['type']];
				//$val['category'] = $op_category[$val['category']];
				$val['order_type'] = $op_order_type[$val['order_type']];
				$val['cooperate_state'] = $op_order_cooperate_state[$val['cooperate_state']];
				$val['source_type'] = $order_source_type[$val['source_type']];
			}
		}
		$title = array('id'=>'序号','code'=>'产品编码','name'=>'产品名称','sb_id'=>'结算主体','ad_id'=>'广告主名称',
			'type'=>'产品类型','source_type'=>'来源类型','category'=>'产品明细分类','order_type'=>'订单类型','saler_id'=>'归属销售','cooperate_state'=>'合作状态','add_time'=>'添加时间');
		$csvObj = new \Think\Csv();
		$csvObj->put_csv($list, $title, '产品'.date('Y-m-d H:i:s'));

	}

	/**
	 * [edit description]
	 * @return [type] [description]
	 */
	public function edit() {
		$id = I('get.id', 0, 'intval');
		$errorMsg = '';
		if ($id > 0) {//修改
			$proInfo = M('product')->field(true)->find($id);
			if (empty($proInfo)) {
				$this->error('未找到相应产品');
			}
			if ($proInfo['price_type'] == 2) {
				//用于页面显示
				$proInfo['tiered_price'] = tieredprice_decode($proInfo['price'], true);//阶梯价格
				$proInfo['price'] = 0;//其他价格
			}
			$this->assign('data', $proInfo);
			$this->assign('data_adname', D('Advertiser')->where('id='.$proInfo['ad_id'])->getField('name'));//广告主名称
			$this->assign('data_blname', D('BusinessLine')->where('id='.$proInfo['bl_id'])->getField('name'));//业务线
			$this->assign('data_sign_body', D('DataDic')->where('dic_type=4 AND id='.$proInfo['sb_id'])->getField('name'));//签订主体名称
			$this->assign('data_saler', D('user')->where('id='.$proInfo['saler_id'])->getField('real_name'));//责任销售
			$this->assign('data_contacts', D('product_contacts')->where('pro_id='.$proInfo['id'])->select());//对接人信息
			//业务规划
			$this->assign('data_plane_bl', D('Product')->getPlaneBlAssoName($id));
			//获取业务线名称
			$this->assign('proid', $id);
		}
		$this->viewAssign();
		$alltag=array(
				array(
					'name'=>'金融',
					'lists'=>array('信用卡','贷款','股票','理财')
					),
				array(
					'name'=>'电商',
					'lists'=>array('一类电商','二类电商')
					),
				array(
					'name'=>'阅读',
					'lists'=>array('小说','漫画','笑话')
					),
				array(
					'name'=>'资讯',
					'lists'=>array('财经','军事','母婴','旅游','体育','汽车','门户')
					),
				array(
					'name'=>'社交',
					'lists'=>array('微博','交友')
					),
				array(
					'name'=>'游戏',
					'lists'=>array('单机','手游','页游','H5')
					),
				array(
					'name'=>'视频',
					'lists'=>array('直播','电影','视频站')
					),
				array(
					'name'=>'工具',
					'lists'=>array('播放器','浏览器','办公类','生活类')
					),
				array(
					'name'=>'平台',
					'lists'=>array('平台')
					),
				array(
					'name'=>'网址导航',
					'lists'=>array('网址导航')
					),
				array(
					'name'=>'品牌宣传',
					'lists'=>array('品牌宣传')
					),
				array(
					'name'=>'中小企业'
					)
			);
		$this->assign('alltag',json_encode($alltag));

		//检查当前用户有导出修改产品记录权限 udpate 0707 tgd
		$isHas_check = $_SESSION["sec_/Home/Product/edit/auth_exportrecord"];
		if(!$isHas_check){
			$isHas_check = isHasAuthToQuery("/Home/Product/edit/auth_exportrecord",UID);
			$_SESSION["sec_/Home/Product/edit/auth_exportrecord"] = $isHas_check;
		}
		$this->assign('has_export_record_auth',$isHas_check);
		$this->display();
	}


	private function viewAssign(){
		$this->assign('has_check_auth', $this->checkRule('/Home/Product/ischeck')); //权限 是否检查产品

		$this->assign('op_is_check', C('OPTION.is_check'));
		$this->assign('op_adtype', C('OPTION.ad_type'));
		$this->assign('op_is_internal', C('OPTION.is_internal'));
		$this->assign('op_finance_object', C('OPTION.finance_object'));
		$this->assign('op_invoice_type', C('OPTION.invoice_type'));
		$this->assign('op_product_type', C('OPTION.product_type'));
		$this->assign('op_order_type', C('OPTION.order_type'));
		$this->assign('op_order_cooperate_state', C('OPTION.order_cooperate_state'));
		$this->assign('op_order_test_type', C('OPTION.order_test_type'));
		$this->assign('op_order_source_type', C('OPTION.order_source_type'));
		$this->assign('op_return_cycle', C('OPTION.return_cycle'));
		$this->assign('op_settlement_cycle', C('OPTION.settlement_cycle'));
		$this->assign('op_charging_mode', C('OPTION.charging_mode'));
		$this->assign('op_price_type', C('OPTION.price_type'));
		$this->assign('op_return_type', C('OPTION.return_type'));
		$this->assign('op_package_size', C('OPTION.package_size'));
		//业务线类型
		$this->assign('data_bl_type', D('DataDic')->where('dic_type=6')->getField('id,name'));
		//产品分类

	}
	//详情
	public function detail() {
		$id = I('get.id', 0, 'intval');
		if ($id > 0) {
			$data = D('Product')->find($id);
			$this->assign('data', $data);
			$this->assign('data_adname', D('Advertiser')->where('id='.$data['ad_id'])->getField('name'));//广告主名称
			$this->assign('data_blname', D('BusinessLine')->where('id='.$data['bl_id'])->getField('name'));
			$this->assign('data_saler', D('user')->where('id='.$data['saler_id'])->getField('real_name'));//责任销售
			$this->assign('data_contacts', D('product_contacts')->where('pro_id='.$id)->select());//对接人信息
			$this->assign('data_sign_body', D('DataDic')->where('dic_type=4 AND id='.$data['sb_id'])->getField('name'));//签订主体名称
			//业务规划
			$this->assign('data_plane_bl', D('Product')->getPlaneBlAssoName($id));
			//计费标识
			// $chargingLogo = M('charging_logo')->where('prot_id='.$id)->select();
			// $this->assign('clList', $chargingLogo);
			$this->assign('proid', $id);
			$this->viewAssign();
			$this->display();
		} else {
			$this->error('参数错误');
		}

	}

	/**
	 * 导出产品修改日志
	 * @return [type] [description]
	 */
	function exportUpdateProductRec(){
		$productSer = new \Home\Service\ProductService();
		$return = $productSer->explortProductUpdateRecSer();
		if($return["msg"]){
			$this->success("暂无修改记录");exit;
		}
	}

	/**
	 * 产品保存修改
	 * @return [type] [description]
	 */
	public function update() {

		$proId        = I('post.id', 0);
		$retMsg       = $proId > 0 ? '修改' : '添加';
		$goUrl        = $proId > 0 ? Cookie('__forward__') : U('index');
		$proModel     = D('Product');
		$clModel      = D('ChargingLogo');
		$planeBlModel = M('product_plane_bl');
		$isRedirect   = false;

		$proSer = new \Home\Service\ProductService();
		$_REQUEST["cur_uid"] = UID;
		$proSer->listenProductsUpdates($_REQUEST);
		
		// exit;

		// //阶梯价格
		$priceType   = I('post.price_type', 0);
		$tieredPrice = $_POST['tiered_price'];
		if ($priceType == 2 && !empty($tieredPrice)) {
			$tpCode = tieredprice_encode($tieredPrice);
			if ($tpCode === false) {
				$this->ajaxReturn(array('msg'=>'产品阶梯价格格式错误'));
			}
			$_POST['price'] = $tpCode;
		}
		/*------先检查数据，防止基础信息保存成功 其他信息失败时页面跳转到编辑页面------*/
		//产品名称、广告主、结算主体均相同
		//echo '1';exit;
		$uniqMap['name']  = I('post.name');
		$uniqMap['ad_id'] = I('post.ad_id');
		$uniqMap['sb_id'] = I('post.sb_id');
		$exist = (int)$proModel->where($uniqMap)->getField('id');
		if (($proId > 0 && $exist>0 && $exist!=$proId) || ($proId == 0 && $exist>0)) {
			$this->ajaxReturn(array('msg'=>'该产品已存在！'));
		}
		if ($proModel->create() === false) {
			$this->ajaxReturn(array('msg'=>$proModel->getError()));//数据错误
		}

		//检查联系人
		$ctData       = I('post.procontacts');
		$contactModel = M('product_contacts');
		if (!empty($ctData)) {
			foreach ($ctData as $val) {
				$val['pro_id'] = 0;
				if ($contactModel->validate($proModel->contactRule)->create($val) === false) {
					$this->ajaxReturn(array('msg'=>$contactModel->getError()));
				}
			}
		} else {
			$this->ajaxReturn(array('msg'=>'对接人信息不能为空'));
		}

		//计费标识检查
		$clData = I('post.charginglogo','');
		if (empty($clData)) {
			if (I('post.bl_id',0) != 2) { //如果业务线不是ssp
				$this->ajaxReturn(array('msg'=>'计费标识不能为空'));
			}
		} else {
			if (I('post.bl_id',0) != 2) { //如果业务线不是ssp
				foreach ($clData as $key=>$val) {
					//阶梯价格
					$priceType = $val['price_type'];
					if ($priceType == 2) {
						if (!check_tpstr($val['price'])) {
							$this->ajaxReturn(array('msg'=>'计费标识阶梯价格格式错误'));
						}
					}
					$val['ad_id']   = 0;
					$val['prot_id'] = 0;
					if ($clModel->create($val) === false) {
						$this->ajaxReturn(array('msg'=>$clModel->getError()));
					}
				}
			}
		}
		/*----------------操作-----------------*/
		//基础信息
		if ($proId > 0) { //修改
			$r = $proModel->save();
			//2017.02.21 当月状态由测试变成正式上量等修改明细
			$outData = M('product_state')->field('cooperate_state')->where("pr_id=".$proId."")->find();
			if($outData['cooperate_state'] != $_POST['cooperate_state']){
				$addData                    = array();
				$addData['pr_id']           = $proId;
				$addData['bl_id']           = I('post.bl_id');
				$addData['cooperate_state'] = $_POST['cooperate_state'];
				$addData['add_date']        = date('Y-m-d H:i:s',time());
				M('product_state')->add($addData);
			}

		} else {
			$proId = $r = $proModel->add();
			if ($r !== false) {

				$addData = array();
				$addData['pr_id'] = $proId;
				$addData['bl_id'] = I('post.bl_id');
				$addData['cooperate_state'] = $_POST['cooperate_state'];
				$addData['add_date'] = date('Y-m-d H:i:s',time());
				M('product_state')->add($addData);
				//添加后更新产品编码
				$_map['id']   = $proId;
				$_map['code'] = $proModel->generalCode($proId);
				if ($proModel->save($_map) === false) { //更新失败删除刚添加的产品
					$proModel->delete($proId);
					$this->ajaxReturn(array('msg'=>$proModel->getError()));
				}
			}
			$isRedirect = true;
		}
		//产品停推同步至分发
		$url                    = 'http://dist.youxiaoad.com/api.php/Productstop';
		$appsecret              = "b#asb%svp&^";
		$data['ts']             = time();
		$data['sign']           = md5($appsecret.$data['ts']);
		$json_data[0]['pr_id']  = $proId;
		$json_data[0]['status'] = I('post.cooperate_state');
		$data['data']           = json_encode($json_data);
		$result                 = bossPostData($url, $data);
		foreach($result['data'] as $v){
			if($v['state']==0) exit('不成功');//未同步成功
		}
		M('Product')->where("id=".$proId)->save(array('laststoptime'=>date('Y-m-d')));
		if ($r === false) {
			$this->ajaxReturn(array('msg'=>$proModel->getError()));
		}

		$errGo = $isRedirect ? U('edit?id='.$proId) : ''; //新增的时候 基本信息保存成功，其他信息错误，则跳转到编辑
		//计费标识
		if (!empty($clData)) {
			if($clModel->doAdd(I('post.charginglogo'), I('post.ad_id'), $proId)===false) {
				$this->ajaxReturn(array('msg'=>$clModel->getError(),'go'=>$errGo));
			}
		}

		//联系人
		$pro_id = $proId;
		foreach ($ctData as $val) {
			$val['pro_id'] = (int)$pro_id;
			if ($contactModel->validate($proModel->contactRule)->create($val) === false) {
				$this->ajaxReturn(array('msg'=>$contactModel->getError(),'go'=>$errGo));
			}
			if (intval($val['id']) > 0) {
				$r = $contactModel->save();
			} else { //添加
				$r = $contactModel->add();
			}
			if ($r === false) {
				$this->ajaxReturn(array('msg'=>$contactModel->getError(),'go'=>$errGo));
			}
		}

		//业务线规划
		$blData = I('post.plane_bl');
		if (!empty($blData)) {
			foreach ($blData as $val) {
				$val['pro_id'] = (int)$proId;
				if ($planeBlModel->create($val) === false) {
					$this->ajaxReturn(array('msg'=>$planeBlModel->getError(),'go'=>$errGo));
				}
				if (intval($val['id']) > 0) {
					$r = $planeBlModel->save();
				} else { //添加计费标识
					$r = $planeBlModel->add();
				}
				if ($r === false) {
					$this->ajaxReturn(array('msg'=>$planeBlModel->getError(),'go'=>$errGo));
				}
			}
		}
		$_retMsg = '产品'.$retMsg.'成功';
		action_log('partner', 'info', $_SESSION['userinfo']['realname'], $_retMsg.'proid='.$proId, CONTROLLER_NAME.'/'.ACTION_NAME);//日志
		$this->ajaxReturn(array('msg'=>$_retMsg, 'go'=>$goUrl));
	}



	public function doUpload() {
		$info = uploadify(CL_PACKAGE_PATH);
		$this->ajaxReturn($info);
	}


	//获取选项
	public function optionTable() {

		$type = I('get.type', '');
		$kw   = I('get.kw','','urldecode');
		$model = '';
		$where = '1=1';
		$field = array('id'=>1,'name'=>1);
		$title = array('id','名称');
		$opData = array();
		$keywords = '';
		if ($kw) {
			$keywords = " AND %s LIKE '%%{$kw}%%'";
		}

		switch ($type) {
			case 'ad':  //广告主名称
				$model = 'Advertiser';
				$where .= sprintf($keywords,'name');
				$where .= " AND status=1" . sprintf($keywords,'name');
				$title = array('id','名称','邮箱');
				$field = array('id'=>1,'name'=>1,'email'=>1);
				break;
			case 'bl':  //业务线
				$model = 'business_line';
				$where .= sprintf($keywords,'name');
				break;
			case 'sb': //(签订主体)
				$model = 'DataDic';
				$where .= " AND dic_type=4" . sprintf($keywords,'name');
				break;
			case 'ba'://收款账户
				$model = 'company_bankaccount';
				$where .= sprintf($keywords,'name');
				break;
			case 'user': //责任销售
				$model = 'user';
				$field = array('id'=>1,'real_name'=>1);
				$where .= " AND id>0". sprintf($keywords,'real_name');
				break;
			case 'c_num': //合同编号
				$model = 'oa_45';
				$field = array('id'=>1,'x72668e_3'=>1,'x72668e_6'=>1,'x72668e_7'=>1,'x72668e_12'=>0,'x72668e_13'=>0);
				$nowDate = strtotime(date('Y-m-d'));
				// $keywords  公司名称
				$kwstr = '';
				if ($kw) $kwstr = " AND CONCAT(x72668e_6,x72668e_3) LIKE '%{$kw}%'";
				$where .= " AND UNIX_TIMESTAMP(x72668e_13)>{$nowDate} AND x72668e_8 <> ''" . $kwstr;
				$opData = array('x72668e_12','x72668e_13');
				$title = array('id','OA流水号','广告主名称','结算主体');
				break;
			default:
				$this->ajaxReturn('');
				break;
		}

		$listRows = 5;
		$queryField = array_keys($field);
		$data = M($model)->where($where)->field($queryField)->page($_GET['p'],$listRows)->select();
		$total = M($model)->where($where)->count();

		$showField = $field;
		foreach ($showField as $k=>$v) { //是否显示字段
			if($v == 0) {
				unset($showField[$k]);
			}
		}

		$this->assign('show_field', array_keys($showField));
		$this->assign('list',$data);
		$this->assign('op_kw', $kw);
		$this->assign('extraField', $opData);
		$page = new \Think\Page($total, $listRows);
		if($total>$listRows){
			$page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
		}
		$p =$page->show();
		$this->assign('_page', $p? $p: '');
		$this->assign('_total',$total);
		$this->assign('title',$title);
		$this->ajaxReturn($this->fetch('Public/optiontable'));

	}


	//根据分类获取业务线
	public function getbl() {
		$pid = I('pid', 0, 'intval');
		if ($pid > 0) {
			$this->ajaxReturn(D('BusinessLine')->where('type='.$pid)->getField('id,bl_code,name'),'JSON');
		} else {
			$this->ajaxReturn(array());
		}
	}


	//删除产品阶梯价格
	public function deleteTieredPrice() {
		$id = I('post.id', 0, 'intval'); //产品id
		$idx = I('post.idx', 0, 'intval');//数组序号(第一位是1)
		if ($id > 0 || $idx > 0) {
			$proModel = D('Product');
			//取出财务接受人信息
			$tPrice = $proModel->where('id='.$id)->getField('price');
			$tPriceArr = tieredprice_decode($tPrice);
			if(count($tPriceArr) > 0) {
				unset($tPriceArr[$idx]);
				$_map['price'] = tieredprice_encode($tPriceArr);
				$_map['id'] = $id;
				if ($proModel->save($_map) === false) {
					$this->ajaxReturn(array('msg'=>$proModel->getError(),'error'=>'1'),'JSON');
				}
				$this->ajaxReturn(array('msg'=>'产品阶梯价格已删除','error'=>'0'),'JSON');
			}

		}
		$this->ajaxReturn(array('msg'=>'参数错误','error'=>'1'),'JSON');
	}

	//删除联系人
	public function deleteContact() {
		$id = I('post.id', 0, 'intval');
		if ($id > 0) {
			$proConModel = M('product_contacts');
			if ($proConModel->delete($id) === false) {
				$this->ajaxReturn(array('msg'=>$proConModel->getError(),'error'=>'1'),'JSON');
			}
			$this->ajaxReturn(array('msg'=>'产品联系人已经删除','error'=>'0'),'JSON');

		} else {
			$this->ajaxReturn(array('msg'=>'参数错误','error'=>'1'),'JSON');
		}
	}

	//删除规划业务线
	public function deletePBl() {
		$id = I('post.id', 0, 'intval');
		if ($id > 0) {
			$pblModel = M('product_plane_bl');
			if ($pblModel->delete($id) === false) {
				$this->ajaxReturn(array('msg'=>$pblModel->getError(),'error'=>'1'),'JSON');
			}
			$this->ajaxReturn(array('msg'=>'产品规划业务线已经删除','error'=>'0'),'JSON');

		} else {
			$this->ajaxReturn(array('msg'=>'参数错误','error'=>'1'),'JSON');
		}
	}


	public function chartView() {

		$item = I('get.item',0,'intval');
		$model = M('Product');

		$res = array();
		$fields = '';
		switch ($item) {
			case 1:
				//来源类型
				$typeArr = C('OPTION.order_source_type');
				$datatype = $model->group('source_type')->getField('source_type,COUNT(id) AS num');
				break;
			case 2:
				//合作状态占比
				$typeArr = C('OPTION.order_cooperate_state');
				$datatype = $model->group('cooperate_state')->getField('cooperate_state,COUNT(id) AS num');
				break;
			case 3:
				//产品类型
				$typeArr = C('OPTION.product_type');
				$datatype = $model->group('type')->getField('type,COUNT(id) AS num');
				break;
			case 4:
				//产品明细分类(量级)
				$typeArr = M('data_dic')->where('dic_type=7')->getField('id,name');
				$datatype = M('daydata')->alias('dd')->join('boss_product AS pro ON dd.`comid`=pro.`id`')->group('pro.category')->getField('pro.`category`,COUNT(dd.id) AS num');
				break;
			case 5:
				//业务线
				$typeArr = M('business_line')->getField('id,name');
				$datatype = $model->group('bl_id')->getField('bl_id,COUNT(id) AS num');
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


	//转换阶梯价格为字符串显示
	public function convertTPrice() {
		$tp = I('post.tiered_price','');
		$tpstr = '';
		if (!empty($tp)) {
			$tpstr = tieredprice_encode($tp);
		}
		$this->ajaxReturn($tpstr);
	}

	/**
	 * 延时加载计费标识数据
	 * @return [type] [description]
	 */
	public function lazyChargLog(){
		$prot_id = !empty($_REQUEST["id"])?trim($_REQUEST["id"]):"";
		$row = 10;
		$count = D('ChargingLogo')->field("id")->where(array("prot_id"=>$prot_id))->count();
		$page = new \Think\AjaxPage($count,$row,"J.lazyCharLog");
		$show = $page->show();
		$chargingLogo = D('ChargingLogo')->where(array("prot_id"=>$prot_id))->order('id desc')->limit($page->firstRow.','.$page->listRows)->select();
		$list = array(
						"logo_list"=>$chargingLogo,
						"price_type_list"=>C('OPTION.price_type'),
						"op_charging_mode"=>C('OPTION.charging_mode'),
						"page"=>$show,
						);
		$this->ajaxReturn($list);
	}

}