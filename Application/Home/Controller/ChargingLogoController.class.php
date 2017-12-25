<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 计费标识管理 
 * Class ProductController
 * @package Home\Controller
 */
class ChargingLogoController extends BaseController {

	/**
	 * 计费标识
	 * @return [type] [description]
	 */
	public function index(){
		//条件查询
		$map              = array();
		$map["ad_name"]   = trim($_REQUEST["ad_name"]);
		$map["pro_name"]  = trim($_REQUEST["pro_name"]);
		$map["jf_name"]   = trim($_REQUEST["jf_name"]);
		$map["sup_name"]  = trim($_REQUEST["sup_name"]);
		$map["out_bl_id"] = trim($_REQUEST["out_bl_id"]);
		$map["status"]    = trim($_REQUEST["status"]);
		$map["sdate"] = I("sdate");
		$map["edate"] = I("edate");
		$map["cooperate_state"] = I("cooperate_state");
		$this->assign("map",$map);
	
		/*******2017-03-28修改********/
		$listCount = 0;
		$orderby = " order by c.code desc";//默认排序
		$sql = "select 
					  a.name as ad_id,
					  c.id,
					  c.code,
					  c.name,
					  c.is_check,
					  c.status,
					  c.price,
					  p.name as prot_id,
					  case 
						   WHEN p.cooperate_state  = 1  THEN '正式上量'
						   WHEN p.cooperate_state  = 2  THEN '测试'
						   WHEN p.cooperate_state  = 3  THEN '停推'
					  end
					  as cooperate_state_str
					from
					  `boss_charging_logo` as c 
					  LEFT JOIN `boss_product` AS p ON c.`prot_id`=p.id
					  left join `boss_advertiser` as a on a.`id` = p.`ad_id`";
		$sql_count = "select count(1) as num
					from
					  `boss_charging_logo` as c 
					  left join `boss_advertiser` as a on a.`id` = c.`ad_id`
					  LEFT JOIN `boss_product` AS p ON c.`prot_id`=p.id";
	  	
	  	//供应商名称
		$sup_name = I("sup_name");
		if($sup_name){
			$sql .= " LEFT JOIN `boss_supplier` AS s ON c.`superid`=s.id";
			$sql_count .= " LEFT JOIN `boss_supplier` AS s ON c.`superid`=s.id";
		}
		//分配业务线
		$out_bl_id = I("out_bl_id");
		//推广时间
		$sdate     = I("sdate");
		$edate     = I("edate");
		$status = $_REQUEST["status"]===""?"":$_REQUEST["status"];
		
		if($out_bl_id || $sdate || $edate || $status!=""){
			$sql .= " LEFT JOIN `boss_charging_logo_assign` AS cl ON cl.`cl_id`=c.id";
			$sql_count .= " LEFT JOIN `boss_charging_logo_assign` AS cl ON cl.`cl_id`=c.id";
		}

		//条件查询
		$where = " where 1=1";
	  	$where_count = " where 1=1";
		//广告主名称
		$ad_name = I('ad_name');
		if($ad_name){
			$where .= " and a.name like '%{$ad_name}%' ";
			$where_count .= " and a.name like '%{$ad_name}%' ";
		}
		//产品名称
		$pro_name = I("pro_name");
		if($pro_name){
			$where .= " and p.name like '%{$pro_name}%' ";
			$where_count .= " and p.name like '%{$pro_name}%' ";
		}
		//计费标识
		$jf_name = I("jf_name");
		if($jf_name){
			$where .= " and c.name like '%{$jf_name}%' ";
			$where_count .= " and c.name like '%{$jf_name}%' ";
		}
		//供应商名称
		if($sup_name){
			$where .= " and s.name like '%{$sup_name}%' ";
			$where_count .= " and s.name like '%{$sup_name}%' ";
		}
		//供应商名称
		if($sup_name){
			$where .= " and s.name like '%{$sup_name}%' ";
			$where_count .= " and s.name like '%{$sup_name}%' ";
		}
		//分配业务线
		if($out_bl_id){
			$where .= " and cl.bl_id={$out_bl_id}";
			$where_count .= " and cl.bl_id={$out_bl_id}";
		}
		//分配状态：
		// print_r($status);exit;
		if($status!=""){
			$where .= " and cl.status={$status}";
			$where_count .= " and cl.status={$status}";
		}
		//推广时间
		$sdate = I("sdate");
		$edate = I("edate");
		if($sdate && $edate==""){
			$where .= " and cl.promotion_stime>='{$sdate}'";
			$where_count .= " and cl.promotion_stime>='{$sdate}'";
		}
		if($edate && $sdate==""){
			$where .= " and cl.promotion_etime<='{$edate}'";
			$where_count .= " and cl.promotion_etime<='{$edate}'";
		}
		if($edate && $sdate){
			$where .= " and cl.promotion_stime>='{$sdate}' and  cl.promotion_etime<='{$edate}'";
			$where_count .= " and cl.promotion_stime>='{$sdate}' and  cl.promotion_etime<='{$edate}'";
		}
		//产品合作状态：
		$cooperate_state = I("cooperate_state");
		if($cooperate_state){
			$where .= " and p.cooperate_state={$cooperate_state}";
			$where_count .= " and p.cooperate_state={$cooperate_state}";
		}

		//数据权限
        $arr_name=array();
        $arr_name['line']=array('p.bl_id');
        $arr_name['user']=array('p.saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where .= " and $myrule_data";
        $where_count .= " and $myrule_data";




		//排序
		$sql = $sql.$where." group by c.id ".$orderby;
		$sql_count = $sql_count.$where_count." group by c.id ";
		$sql_count_ = "SELECT 
						  COUNT(1) as num
						FROM
						  ({$sql_count}) AS c ";
		// print_r($sql_count);exit;
		$model     = new \Think\Model();
		$listCount = $model->query($sql_count_);
		$listCount = $listCount[0]["num"];

		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$p        = !I("p") ? 1 : I("p");//当前页数

		$listIndex = ($p-1)*$listRows;
		$sql .= " limit ".$listIndex.",".$listRows;
		$list = $model->query($sql);

		//分页
		$page = new \Think\Page($listCount, $listRows);
		if($listCount>$listRows){
			$page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
		}
		$papge =$page->show();
		$this->assign('_page', $papge);
		//查询获取计费标识id集合
		$rList  = $this->getJfbsAssignList($list);
		$list_n = array();
		$index_ = 0;
		foreach ($list as $k => $v) {
			if($status!=""){
				$status_s = $rList[$v["id"]]["status"];
				if($status_s==$status){
					$list_n[$index_] = $v;
					$list_n[$index_]['status'] = $rList[$v["id"]]["status"];
					$list_n[$index_]['bid'] = $rList[$v["id"]]["id"];
					$index_++;
				}
			}else{
				$list_n[$index_] = $v;
				$list_n[$index_]['status'] = $rList[$v["id"]]["status"];
				$list_n[$index_]['bid'] = $rList[$v["id"]]["id"];
				$index_++;
			}
			// $list[$k]['status'] = $rList[$v["id"]]["status"];
			// $list[$k]['bid'] = $rList[$v["id"]]["id"];
		}
		unset($list);unset($index_);
		// print_r($listCount);exit;
		/*******end 2017-03-28修改********/
		$this->assign('list', $list_n);
		$this->assign('op_product_type', C('OPTION.product_type'));
		$this->assign('op_order_type', C('OPTION.order_type'));
		$this->assign('op_is_internal', C('OPTION.is_internal'));
		$this->assign('op_chargingLogo_status', C('OPTION.chargingLogo_status'));
		$this->assign('has_check_auth', $this->checkRule('/Home/ChargingLogo/ischeck')); //权限 是否检查供应商
		$this->assign('op_is_check', C('OPTION.is_check'));

		//附加数据
		if(!S("userlist2_cache")){
			$userlist2 = M('user')->field('a.id,a.real_name,b.name as groupname,d.title as posttype')->join('a join boss_user_department b on a.dept_id=b.id join boss_auth_group_access c on a.id=c.uid join boss_auth_group d on c.group_id=d.id')->where('d.id IN (6,7,55) || a.id=663 || a.id=633')->group('a.id')->select();
			S("userlist2_cache",$userlist2,300);
		}
		$this->assign('userlist2',S("userlist2_cache"));
		$this->assign('settlement_cycle', C('OPTION.settlement_cycle'));//结算周期
		$this->assign('return_cycle', C('OPTION.return_cycle'));//返量周期
		$this->assign('charging_mode', C('OPTION.charging_mode'));//计费模式
		$this->assign('op_price_type', C('OPTION.price_type'));//价格类型
		//结算主体
		if(!S("SignBody_cache")){
			$list = D('DataDic')->getSignBody();
			S("SignBody_cache",$list,300);
		}
		$this->assign('SignBody',S("SignBody_cache"));
		//业务线
		if(!S("business_line_cache")){
			$list = M('business_line')->field('id,name')->where('status=1')->select();
			S("business_line_cache",$list,300);
		}
		$this->assign('bl_id',S("business_line_cache"));
		//供应商
		if(!S("supplier_cache")){
			$list = M('supplier')->field('name,id')->where('status=1')->select();
			S("supplier_cache",$list,300);
		}
		$this->assign('superlist',S("supplier_cache"));
		$this->display();
	}

	/**
	 * 获取计费标识详细集合
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	private function getJfbsAssignList($list){
		$ids   = "";
		$rList =array();
		foreach ($list as $k => $v) {
			$ids .= $v["id"].",";
		}
		if($ids){
			$ids = substr($ids,0,strlen($ids)-1);
			$sql = "SELECT 
					  STATUS,id,cl_id
					FROM
					  `boss_charging_logo_assign` 
					WHERE id IN 
					  (SELECT 
					    MAX(id) 
					  FROM
					    `boss_charging_logo_assign` 
					  WHERE cl_id IN ({$ids})  GROUP BY cl_id
					  )";
			$model  = new \Think\Model();
			$list = $model->query($sql);
			if($list){
				foreach ($list as $k => $v) {
					$data["status"]     = $v["status"];
					$data["id"]         = $v["id"];
					$rList[$v["cl_id"]] = $data;
				}
			}
		}
		return $rList;
	}

	/**
	 * 延时加载分配业务线
	 * @return [type] [description]
	 */
	function lazyOutId(){
		$list = S("business_line_lazyOutId");
		if(!$list){
			$list = M('business_line')->field("id,name")->select();
			S("business_line_lazyOutId",$list,3600);
		}
		$this->ajaxReturn($list);
	}

	/**
	 * 延时加载状态
	 * @return [type] [description]
	 */
	function lazyCharlogStatus(){
		$list = S("lazyCharlogStatus_list");
		if(!$list){
			$list = C('OPTION.chargingLogo_status');
			S("lazyCharlogStatus_list",$list,3600);
		}
		$this->ajaxReturn($list);
	}


	/**
	 * 数据导出
	 * @return [type] [description]
	 */
	public function export(){
		//以产品的广告主为准
		//*******************************
		//针对需求按每月查看数据，解决一次性导出服务器内存溢出问题 2017-08-29
		//*******************************
		$sql = "SELECT 
				  a.name AS ad_id,
				  c.id,
				  c.code,
				  c.name,
				  c.is_check,
				  c.status AS c_status,
				  c.price,
				  p.name AS prot_id,
				  dd.name AS sb_id,
				  s.name AS sup_id,
				  bl.name AS bl_id,
				  cl.promotion_price,
				  cl.charging_mode,
				  cl.return_cycle,
				  cl.settlement_cycle,
				  cl.deduction_ratio,
				  cl.in_settlement_prate,
				  cl.promotion_stime,
				  cl.promotion_etime,
				  cl.status as cl_status,
				  CASE
				    WHEN p.cooperate_state = 1 
				    THEN '正式上量' 
				    WHEN p.cooperate_state = 2 
				    THEN '测试' 
				    WHEN p.cooperate_state = 3 
				    THEN '停推' 
				  END AS cooperate_state_str 
				FROM
				  `boss_charging_logo_assign` AS cl 
				  LEFT JOIN `boss_charging_logo` AS c ON c.id = cl.`cl_id` 
				  LEFT JOIN `boss_product` AS p ON c.`prot_id` = p.id 
				  LEFT JOIN `boss_advertiser` AS a  ON a.`id` = p.`ad_id` 
				  LEFT JOIN `boss_data_dic` AS dd ON dd.`id` = cl.sb_id 
				  LEFT JOIN `boss_supplier` AS s ON cl.`sup_id` = s.id 
				  LEFT JOIN `boss_business_line` AS bl ON bl.`id` = cl.bl_id";

		//推广时间
		$month = trim(I("month"));

		//条件查询
		$where = " where 1=1";
		if($month){
			$where .= " and (cl.promotion_stime like '%{$month}%' or  cl.promotion_etime like '%{$month}%' or cl.promotion_etime='' or cl.promotion_etime is null) ";
		}else{
			$this->success("必须选择推广开始时间");
			exit;
		}
		
		//排序
		$sql = $sql.$where;
		$model     = new \Think\Model();
		// print_r($sql);exit;
		if(I("showsql")=="showsql023"){
			print_r($sql);exit;
		}
		$list = $model->query($sql);
		if(!$list){
			$this->success("无数据导出或数据已导完,请检查！！");
			exit;
		}

		$op_charging_mode       =  C('OPTION.charging_mode');
		$op_return_cycle        = C('OPTION.return_cycle');
		$op_settlement_cycle    = C('OPTION.settlement_cycle');
		$op_chargingLogo_status = C('OPTION.chargingLogo_status');
		//获取计费标识明细最大id
		// $rList                  = $this->getJfbsAssignList($list);
		$shangwu_list = $this->getJfbsShangWu($list);
		//查询获取计费标识id集合
		foreach ($list as $key => $v) {
			$list[$key]['charging_mode']    = $op_charging_mode[$v['charging_mode']];
			$list[$key]['return_cycle']     = $op_return_cycle[$v['return_cycle']];
			$list[$key]['settlement_cycle'] = $op_settlement_cycle[$v['settlement_cycle']];
			$list[$key]['status']           = $op_chargingLogo_status[$v["cl_status"]];
			
			$list[$key]['real_name']        = $shangwu_list[$v["id"]]["real_name"];
		}
		unset($shangwu_list);
		unset($op_charging_mode);
		unset($op_settlement_cycle);
		unset($op_chargingLogo_status);

		//导出标题
		$title = array(
				'id'                  =>'计费标识ID',
				'code'                =>'交易记录编号',
				'name'                =>'名称',
				'prot_id'             =>'产品名称',
				'ad_id'               =>'广告主名称',
				'sb_id'               =>'结算主体',
				'sup_id'              =>'供应商名称',
				'bl_id'               =>'分配业务线',
				'price'               =>'接入单价',
				'promotion_price'     =>'推广价格',
				'charging_mode'       =>'计费模式',
				'return_cycle'        =>'返量周期',
				'settlement_cycle'    =>'结算周期',
				'deduction_ratio'     =>'扣量比例',
				'in_settlement_prate' =>'内部结算利润率',
				'promotion_stime'     =>'开始日期',
				'promotion_etime'     =>'结束日期',
				'status'              =>'分配状态',
				'cooperate_state_str' =>'产品合作状态',
				"real_name"           =>"商务",
		);
		$csvObj = new \Think\Csv();
		//文件名
		$fileName = $isPaging==true?"_page_".$nowPage:"";
		$csvObj->put_csv($list, $title, '计费标识'.date('YmdHis',time()).$fileName);
	}


	private function getJfbsShangWu($list){
		$jsid = "";
		foreach ($list as $k => $v) {
			if($v["id"]){ $jsid .= $v["id"].","; }
		}
		if($jsid){
			$jsid = substr($jsid, 0,strlen($jsid)-1);
		}
		$jsid = empty($jsid)?0:$jsid;
		$sql = "SELECT 
				c.id,
				  c.name,
				  b.real_name 
				FROM
				  boss_daydata_out a 
				  LEFT JOIN boss_user b 
				    ON a.businessid = b.id 
				  LEFT JOIN boss_charging_logo c 
				    ON a.jfid = c.id where c.id in ($jsid)
				GROUP BY jfid ";
				// print_r($sql);exit;
		$model = new \Think\Model();
		$out_list = $model->query($sql);
		$shangwuTree = array();
		foreach ($out_list as $k => $v) {
			$shangwuTree[$v["id"]]["real_name"] = $v["real_name"];
		}
		unset($sql);
		unset($out_list);
		unset($jsid);
		return $shangwuTree;
	}	





	/**
	 * 计费标识详情
	 */
	public function detail() {

		$id = I('get.id',0);
		if ($id <=0) {
			$this->error('参数错误');
		}

		$model = D('ChargingLogoDetail');
		$list = $model->getDetail($id);
		$total = $model->totalPage;
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page = new \Think\Page($total, $listRows);
		if($total>$listRows){
			$page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
		}
		$p =$page->show();
		$this->assign('_page', $p? $p: '');
		$this->assign('_total',$total);

		$this->assign('op_charging_mode', C('OPTION.charging_mode'));
		$this->assign('op_return_cycle', C('OPTION.return_cycle'));
		$this->assign('op_settlement_cycle', C('OPTION.settlement_cycle'));
		$this->assign('op_chargingLogo_status', C('OPTION.chargingLogo_status'));

		$this->assign('list', $list);
		$this->display();

	}


	/**
	 * 计费标识停止
	 */
	public function stop() {

		if (!$this->checkRule('/Home/ChargingLogo/stop')) {
			$this->error('您没有访问权限,请联系管理员');
		}

		$ids = I('post.ids', '');

		if (empty($ids)) {
			$this->error('参数错误');
		}

		if (is_array($ids)) {
			$ids = implode(',', $ids);
		} else {
			$ids = trim($ids,',');
		}

		$clModel = D('ChargingLogo');
		$detModel = M('charging_logo_assign');
		$idArr = explode(',', $ids);

		$notRecovery = '';
		$stopInfo = '';
		$sendDistError = '';

		$url = 'http://dist.youxiaoad.com/api.php/Alimamastop';

		$appsecret = "b#asb%svp&^";
		$data['ts'] = time();
		$data['sign'] = md5($appsecret.$data['ts']);

		foreach ($idArr as $key => $idval) {
			//TODO: 判断计费标识状态是否为已回收
			$status = $detModel->where('cl_Id='.$idval)->getField('status',true);
			$existFlag = false;
			foreach($status as $s) {
				if (in_array($s, array(1,2))) {
					$notRecovery .= $idval .',';
					$existFlag = true;
				}
			}
			if ($existFlag) {
				continue;
			} else {
				if($clModel->where("id={$idval}")->save(array('status'=>1)) === false) {
					action_log('partner', 'error', $_SESSION['userinfo']['realname'], $clModel->getError(), CONTROLLER_NAME.'/'.ACTION_NAME);//日志
					$this->error($clModel->getError());
				} else {
					$cur = $detModel->where('cl_Id='.$idval)->order('id DESC')->limit(1)->find();
					if (empty($cur['promotion_etime'])) { //默认一个时间
						$_map['promotion_etime'] = date('Y-m-d');
					}
					$_map['id'] = $cur['id'];
					$_map['status'] = 0;
					if($detModel->save($_map)===false){
						action_log('partner', 'error', $_SESSION['userinfo']['realname'], $detModel->getError(), CONTROLLER_NAME.'/'.ACTION_NAME);//日志
						$this->error($detModel->getError());
					}
				}
				$stopInfo = '计费标识已经停止';

				

				$json_data[$key]['promotion_id'] = $idval;
				$json_data[$key]['sup_id'] = $cur['sup_id'];
				$json_data[$key]['pr_id'] = $clModel->where('id='.$idval)->getField('prot_id');
				$json_data[$key]['bos_jfid'] = $cur['id'];
				$json_data[$key]['start_date'] = $cur['promotion_stime'];
				$json_data[$key]['end_date'] = date('Y-m-d');
				$data['data'] = json_encode($json_data);
				
				//同步分发平台
				//import('Common.Api.DistApi');
				/*
				$distApi = new \Common\Api\DistApi();
				$sendData = array(
					'cl_id' => $idval,
					'charging_logo' => $clModel->where('id='.$idval)->getField('name'),
				);
				if (!$distApi->stopChargingLogo($sendData)) {
					$sendDistError = ',通知分发平台失败';
				}
				*/
			}

		}
		$result = bossPostData($url, $data);//同步至分发
		foreach($result['data'] as $v){
			if($v['state']==0) $a=0;//未同步成功
		}
		$notRecovery = empty($notRecovery) ? '' : '，其中状态未回收clids:'.$notRecovery;
		$stopInfo .= $notRecovery . $sendDistError;

		//计费标识-同步至分发--回收接口
		$charSer       = new \Home\Service\ChargingLogoService();
		$riskResult    = $charSer->alimamaRecoveryService($data);
		
		action_log('partner', 'info', $_SESSION['userinfo']['realname'], $stopInfo, CONTROLLER_NAME.'/'.ACTION_NAME);//日志
		$this->success($stopInfo, '', 5);

	}


	public function checkItem() {

		$id = I('get.id',0,'intval');
		$val = I('get.value');

		if ($id <= 0 || !in_array($val,array(0,1))) {
			$this->ajaxReturn('参数错误');
		} else {
			$_map['is_check'] = $val;
			$model = M('charging_logo');
			if ($model->where('id='.$id)->save($_map) === false) {
				$this->ajaxReturn('修改失败'.$model->getError());
			} else {
				$this->ajaxReturn('ok');
			}

		}

	}


	/**
	 * 删除计费标识
	 */
	public function delete() {
		$id = I('post.id',0,'intval');
		if ($id > 0) {
			//检查是否存在数据 （收入成本）
			$tabDD = M('daydata');
			$tabDO = M('daydata_out');
			$ddCount = $tabDD->where('jfid='.$id)->count();
			$doCount = $tabDO->where('jfid='.$id)->count();
			if ($ddCount >0 || $doCount > 0) {
				$this->ajaxReturn(array('error'=>1,'msg'=>'该计费标识已产生数据，不允许删除'));
			} else {
				$clModel = M('charging_logo');
				$claModel = M('charging_logo_assign');
				//执行删除
				if ($clModel->where('id='.$id)->delete() === false) {
					action_log('partner', 'error', $_SESSION['userinfo']['realname'], '计费标识删除出错clid='.$id.'；错误信息：'.$clModel->getError(), CONTROLLER_NAME.'/'.ACTION_NAME);//日志
					$this->ajaxReturn(array('error'=>1,'msg'=>'删除出错'.$clModel->getError()));
				}
				$claModel->where('cl_id='.$id)->delete();
				action_log('partner', 'info', $_SESSION['userinfo']['realname'], '计费标识已删除clid='.$id, CONTROLLER_NAME.'/'.ACTION_NAME);//日志
				$this->ajaxReturn(array('error'=>0,'msg'=>'计费标识已删除'));
			}

		} else {
			$this->ajaxReturn(array('error'=>1,'msg'=>'参数错误'));
		}

	}

	//添加分配记录
	public function update(){
		$blaModel = M('charging_logo_assign');
		$priceType = $_POST['promotion_price_type'];
		$h_status = I('post.status', 0);
		$tieredPrice = $_POST['tiered_price'];
		
		$errGo = U('index');
		$goUrl = I('post.status') > 2 ? Cookie('__forward__') : U('index');
		//$this->ajaxReturn(array('msg'=>$priceType.'+'.$tieredPrice));
		if ($priceType == 2 && !empty($tieredPrice)) {
			$tpCode = tieredprice_encode($tieredPrice);
			if ($tpCode === false) {
				$this->ajaxReturn(array('msg'=>'产品阶梯价格格式错误'));
			}
			$_POST['promotion_price'] = $tpCode;
		}
		$_POST['status'] = 1;

		if ($blaModel->create() === false) {
			$this->ajaxReturn(array('msg'=>$blaModel->getError()));//数据错误
		}

		if($h_status == 2){//(未分配)
			//修改
			$res = $blaModel->save();
			//echo $blaModel->getLastSql();exit;

		}elseif($h_status == 3){//(回收)
			//新增
			$_POST['add_time'] = date('y-m-d h:i:s',time());

			M()->startTrans();
			$postid=I('post.id');
			unset($_POST['id']);
			$blaModel->create();
			$res = $blaModel->add();
			$end_t = $blaModel->field('promotion_etime')->where('id='.$postid.'')->find();

			if(strtotime($end_t['promotion_etime']) >=strtotime(I('post.promotion_stime'))){
				M()->rollback();
				$this->ajaxReturn(array('msg'=>'开始推广时间:'.I('post.promotion_stime').'不能晚于上次结束时间'.$end_t['promotion_etime']));
			}else{
				M()->commit();
				
			}
		}
		if ($res === false) {
			$this->ajaxReturn(array('msg'=>$blaModel->getError(),'go'=>$errGo));
		}else{
			$this->ajaxReturn(array('msg'=>'分配成功', 'go'=>$goUrl));
		}

	}
    
}