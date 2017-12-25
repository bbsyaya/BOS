<?php
namespace Api\Controller;
//计费标识接口
class ChargingLogoController extends ApiController {


	public $status = array(
		0 => '已停止',
		1 => '使用中',
		2 => '未分配',
		3 => '已回收'
	);


	/**
	 * 创建广告位 用于(SSP/adx/)
	 */
	public function create() {

		/*$_GET = array(
			'pro_id'=>4,
			'adp_name'=>'xfghf',
			'bl_id'=> 1,
			'sup_id'=> 1,
			'promotion_stime'=>'2016-1-1',
		);*/

		$param = array(
			'pro_id', //产品id
			'adp_name', //广告位名称（=计费标识名称）
			'sup_id', //供应商
			'bl_id',
			'promotion_stime', //开始推广时间
		);

		$get = I('param.');
		foreach ($param as $p) {
            if (!isset($get[$p])) {
	            $this->responseExit(array('errcode'=>'4004','msg'=>$p.'参数不能为空'));
            }
		}
		//供应商是否存在
		if(M('supplier')->where('id='.$get['sup_id'])->count() <= 0) {
			$this->actionLog('error', '供应商不存在：id>'.$get['sup_id']);//日志
			$this->responseExit(array('errcode'=>'4004','msg'=>'没有此供应商'));
		}

		//默认值
		$_defualt = array();
		$_defualt = array_merge($_defualt, $get);
		$_defualt['promotion_price_type']=1;//推广价格类型
		$_defualt['promotion_price']=0.0; //推广单价
		$_defualt['charging_mode']=1; //计费模式
		$_defualt['return_cycle']=1; //返量周期
		$_defualt['settlement_cycle']=1; //结算周期
		$_defualt['deduction_ratio']=0.0; //扣量比例
		$_defualt['in_settlement_prate']=0.0; //内部结算利润率
		$_defualt['sb_id']=2; //结算主体
		$proModel = D('Home/Product');
		$proId = I('param.pro_id', 0, 'intval');
		$product = $proModel->find($proId);
		if (empty($product)) {
			$this->actionLog('error', '产品不存在'.$proId);//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'产品不存在'));
		}

		$clModel = D('Home/ChargingLogo');
		$clDetModle = D('Home/ChargingLogoDetail');
		//计费标识是否停止
		$_map['name'] = I('param.adp_name');
		$_map['prot_id'] = $proId;
		$clInfo = $clModel->where($_map)->field('id,name,status')->find();
		if (!empty($clInfo) && $clInfo['status'] == 0) {
			$this->actionLog('error', '计费标识已停止');//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识已停止'));
		}
		//是否重复创建
		$existId = $clInfo['id'];
		if ((int)$existId > 0) {
			//$clid=$clDetModle->where('cl_id='.$existId)->order('id desc')->getField('id');
			$ret = array(
				'errcode'=>'0',
				'msg'=>'重复创建',
				data=>array(
					'CL_ID'=>$existId
				));

			$this->actionLog('error', "重复创建'CL_ID'=>{$existId}");
			$this->responseExit($ret);
		}

		$clData = array(
			'name' => I('param.adp_name'),
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
		//$addClData[] = $clData;
		//增加计费标识
		if ($clModel->create($clData) === false) {
			$this->actionLog('error', '计费标识分配错误:'.$clModel->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识分配错误:'.$clModel->getError()));
		}
		$insClId = $clModel->add();
		if ($insClId === false ) {
			$this->actionLog('error', '计费标识分配错误:'.$clModel->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识分配错误:'.$clModel->getError()));
		} else {
			//添加后更新计费标识编码
			$_upmap['id'] = $insClId;
			$_upmap['code'] = $clModel->generalCode($insClId);
			if ($clModel->save($_upmap) === false) { //更新失败删除刚添加的计费标识
				$this->delete($insClId);
				$this->actionLog('error', '更新计费标识编码错误:'.$clModel->getError());//日志
				$this->responseExit(array('errcode'=>'5000','msg'=>'更新计费标识编码错误:'));
			}
		}

		//增加分配记录
		$_defualt['cl_id'] = $insClId;
		$_defualt['status'] = 1; //状态使用中

		if ($clDetModle->create($_defualt) === false ) {
			$clModel->delete($insClId);//删除添加的计费标识
			$this->actionLog(SEASLOG_ERROR, '添加分配记录错误:'.$clDetModle->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>$clDetModle->getError()));
		}
		$detClId = $clDetModle->add();
		if ($detClId === false ) {
			$clModel->delete($insClId); //删除添加的计费标识
			$this->actionLog(SEASLOG_ERROR, '添加分配记录错误:'.$clDetModle->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>$clDetModle->getError()));
		} else {
			//添加后更新分配记录编码
			$_detmap['id'] = $detClId;
			$_detmap['code'] = $clDetModle->generalCode($insClId, $detClId);
			if ($clDetModle->save($_detmap) === false) { //更新失败删除刚添加的计费标识分配记录
				$clModel->delete($insClId);//删除添加的计费标识
				$clDetModle->delete($detClId);
				$this->actionLog(SEASLOG_ERROR, '分配记录编码错误');//日志
				$this->responseExit(array('errcode'=>'5000','msg'=>$clDetModle->getError()));
				return false;
			}
		}
		$this->actionLog('info', '计费标识分配成功:cl_Id=>'.$insClId);//日志
		$this->responseExit(array('errcode'=>'0','msg'=>'计费标识id','data'=>array('CL_ID'=>$insClId)));
	}


	/**
	 * 创建广告位 用于分发平台 (根据制定计费标识进行分配)
	 *
	 */
	public function createForDist() {
		/*$_GET = array(
		            'id'=>0,
					'bl_id'=> 1,
					'sb_id'=> 1,
					'sup_id'=> 1,
					'business_uid'=>1,
					'promotion_price_type'=>1,
					'promotion_price'=>12, //推广单价
					'charging_mode'=>1, //计费模式
					'return_cycle'=>1,
					'settlement_cycle'=>1,
					'deduction_ratio'=>1,
					'in_settlement_prate'=>0.4,
					'promotion_stime'=>'2016-1-1',
					'assign_clid'=>,//制定某条计费标识
				);*/
		$param = array(
			'id',
			'bl_id', //业务线
			'sb_id', //结算主体
			'sup_id', //供应商
			'business_uid',//商务人员id
			'promotion_stime', //开始推广时间
			'promotion_price_type',//推广价格类型
			'promotion_price', //推广单价
			'charging_mode', //计费模式
			'settlement_cycle', //结算周期
			'deduction_ratio', //扣量比例
			'in_settlement_prate', //内部结算利润率
			'assign_clid',//制定某条计费标识
		);

		$get = I('param.');
		foreach ($param as $p) {
			if (!isset($get[$p])) {
				$this->responseExit(array('errcode'=>'4004','msg'=>$p . '参数不能为空'));
			}
		}

		$clModel = D('Home/ChargingLogo');
		$clDetModle = D('Home/ChargingLogoDetail');

		$getid = I('param.id');
		if ($getid != 0) {
			$ret = array(
				'errcode'=>'5000',
				'msg'=>'重复创建',
			);
			$this->responseExit($ret);
		}
		// 供应商参数是否存在
		if(M('supplier')->where('id='.$get['sup_id'])->count() <= 0) {
			$this->responseExit(array('errcode'=>'4004','msg'=>'没有此供应商'));
		}
		//判断计费标识状态
		$assClId = I('param.assign_clid', 0, 'intval');
		$validWhere['cl_id'] = $assClId;
		$validWhere['status'] = array('ELT', 1);
		if ($clDetModle->where($validWhere)->count() > 0) {
			$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识不可用'));
		}
		//如果已回收的 且回收时间不等于今天
		$ldetInfo = $clDetModle->where("cl_id={$assClId} AND status=3")->order('id DESC')->limit(1)->find();
		if (!empty($ldetInfo) && strtotime($ldetInfo['promotion_etime']) == strtotime(date('Y-m-d'))) {
			$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识当天回收,无法分配'));
		}
		//开始推广时间验证
		if (strtotime($ldetInfo['promotion_etime']) >= strtotime($get['promotion_stime']) || ($ldetInfo['promotion_etime']=='' && $ldetInfo['promotion_etime']!='')) {
			$this->responseExit(array('errcode'=>'5000','msg'=>'所分配的计费标识clid:'.$assClId.'开始推广时间不能晚于上次结束时间:'.$ldetInfo['promotion_etime']));
		}

		//查询是否有未分配的状态
		$notAssignInfo = $clDetModle->where("cl_id={$assClId} AND status=2")->order('id DESC')->limit(1)->find();
		if (!empty($notAssignInfo)) {
			//未分配的(默认分配记录)将状态 修改为使用中
			$map = $get;
			$map['id'] = $notAssignInfo['id'];
			$map['status'] = 1;
			//return_cycle
			if ($clDetModle->save($map) === false ) {
				$this->actionLog('error', '分配记录(状态由未分配>使用中)cl_id>'.$notAssignInfo['id'].'错误:'.$clDetModle->getError());//日志
				$this->responseExit(array('errcode'=>'5000', 'msg'=>'分配错误：'.$clDetModle->getError()));
			}
			$dclid = $notAssignInfo['id'];
		} else {
			//增加分配记录
			$adddata = $get;
			$adddata['cl_id'] = $assClId;
			$adddata['status']= 1;
			if ($clDetModle->doAdd($adddata) === false ) {
				$this->actionLog('error', '分配记录添加失败:' . $clDetModle->getError());//日志
				$this->responseExit(array('errcode'=>'5000', 'msg'=>$clDetModle->getError()));
			}
			$dclid = $clDetModle->getLastInsID();
		}
		$clInfo = $clModel->find($assClId);
		$promUrl = '';
		//判断推广链接地址
		if ($clInfo['promotion_url_type'] == 2 && !empty($clInfo['promotion_url'])) {
			$promUrl = $clInfo['promotion_url'];
		} else if ($clInfo['promotion_url_type'] == 1 && !empty($clInfo['promotion_url'])) {
			$promUrl = 'http://'.$_SERVER['HTTP_HOST'].$clInfo['promotion_url'];
		}

		$ret = array(
			'errcode'=>'0',
			'msg'=>'创建成功',
			'data'=>array(
				'cl_id' =>   $assClId, //计费标识id
				'adp_id' =>  $dclid, //广告位
				'cl_name'=>  $clInfo['name'],//计费标识名称
				'url'=>      $promUrl,
				'url_type'=> $clInfo['promotion_url_type'],//推广链接地址类型 1安装包2连接地址
			)
		);
		$this->actionLog('info', '分配记录添加成功clid:'.$assClId.',广告位:'.$dclid);//日志
		$this->responseExit($ret);

	}

	/**
	* 创建广告位 用于发行平台 (根据制定计费标识,创建后就不能再次分配了)
	* tgd 2017-04-11
	*/
	public function createNotDist() {
		$param = array(
			'pro_id', //产品id
			'adp_name', //广告位名称（=计费标识名称）
			'sup_id', //供应商
			'bl_id',
			'promotion_stime', //开始推广时间
		);

		$get = I('param.');
		foreach ($param as $p) {
            if (!isset($get[$p])) {
	            $this->responseExit(array('errcode'=>'4004','msg'=>$p.'参数不能为空'));
            }
		}
		//供应商是否存在
		if(M('supplier')->where('id='.$get['sup_id'])->count() <= 0) {
			$this->actionLog('error', '供应商不存在：id>'.$get['sup_id']);//日志
			$this->responseExit(array('errcode'=>'4004','msg'=>'没有此供应商'));
		}

		//默认值
		$_defualt = array();
		$_defualt = array_merge($_defualt, $get);
		$_defualt['promotion_price_type']=1;//推广价格类型
		$_defualt['promotion_price']=0.0; //推广单价
		$_defualt['charging_mode']=1; //计费模式
		$_defualt['return_cycle']=1; //返量周期
		$_defualt['settlement_cycle']=1; //结算周期
		$_defualt['deduction_ratio']=0.0; //扣量比例
		$_defualt['in_settlement_prate']=0.0; //内部结算利润率

		$proModel = D('Home/Product');
		$proId = I('param.pro_id', 0, 'intval');
		$product = $proModel->find($proId);
		if (empty($product)) {
			$this->actionLog('error', '产品不存在'.$proId);//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'产品不存在'));
		}

		$clModel = D('Home/ChargingLogo');
		$clDetModle = D('Home/ChargingLogoDetail');
		//计费标识是否停止
		$_map['name'] = I('param.adp_name');
		$_map['prot_id'] = $proId;
		$clInfo = $clModel->where($_map)->field('id,name,status')->find();
		if (!empty($clInfo) && $clInfo['status'] == 0) {
			$this->actionLog('error', '计费标识已停止');//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识已停止'));
		}
		//是否重复创建
		$existId = $clInfo['id'];
		if ((int)$existId > 0) {
			//$clid=$clDetModle->where('cl_id='.$existId)->order('id desc')->getField('id');
			$assClId = $existId;
			$validWhere['cl_id'] = $assClId;
			$validWhere['status'] = array('ELT', 1);
			if ($clDetModle->where($validWhere)->count() > 0) {
				$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识不可用'));
			}
			//如果已回收的 且回收时间不等于今天
			$ldetInfo = $clDetModle->where("cl_id={$assClId} AND status=3")->order('id DESC')->limit(1)->find();
			if (!empty($ldetInfo) && strtotime($ldetInfo['promotion_etime']) == strtotime(date('Y-m-d'))) {
				$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识当天回收,无法分配'));
			}
			//开始推广时间验证
			if (strtotime($ldetInfo['promotion_etime']) >= strtotime($get['promotion_stime']) || ($ldetInfo['promotion_etime']=='' && $ldetInfo['promotion_etime']!='')) {
				$this->responseExit(array('errcode'=>'5000','msg'=>'所分配的计费标识clid:'.$assClId.'开始推广时间不能晚于上次结束时间:'.$ldetInfo['promotion_etime']));
			}

			//查询是否有未分配的状态
			$notAssignInfo = $clDetModle->where("cl_id={$assClId} AND status=2")->order('id DESC')->limit(1)->find();
			if (!empty($notAssignInfo)) {
				//未分配的(默认分配记录)将状态 修改为使用中
				$map = $get;
				$map['id'] = $notAssignInfo['id'];
				$map['status'] = 1;
				//return_cycle
				if ($clDetModle->save($map) === false ) {
					$this->actionLog('error', '分配记录(状态由未分配>使用中)cl_id>'.$notAssignInfo['id'].'错误:'.$clDetModle->getError());//日志
					$this->responseExit(array('errcode'=>'5000', 'msg'=>'分配错误：'.$clDetModle->getError()));
				}
				$dclid = $notAssignInfo['id'];
			} else {
				//增加分配记录
				$adddata = $get;
				$adddata['cl_id'] = $assClId;
				$adddata['status']= 1;
				if ($clDetModle->doAdd($adddata) === false ) {
					$this->actionLog('error', '分配记录添加失败:' . $clDetModle->getError());//日志
					$this->responseExit(array('errcode'=>'5000', 'msg'=>$clDetModle->getError()));
				}
				$dclid = $clDetModle->getLastInsID();
			}
			$clInfo = $clModel->find($assClId);
			$promUrl = '';
			//判断推广链接地址
			if ($clInfo['promotion_url_type'] == 2 && !empty($clInfo['promotion_url'])) {
				$promUrl = $clInfo['promotion_url'];
			} else if ($clInfo['promotion_url_type'] == 1 && !empty($clInfo['promotion_url'])) {
				$promUrl = 'http://'.$_SERVER['HTTP_HOST'].$clInfo['promotion_url'];
			}

			$ret = array(
				'errcode'=>'0',
				'msg'=>'创建成功',
				'data'=>array(
					'CL_ID' =>   $assClId, //计费标识id
					'adp_id' =>  $dclid, //广告位
					'cl_name'=>  $clInfo['name'],//计费标识名称
					'url'=>      $promUrl,
					'url_type'=> $clInfo['promotion_url_type'],//推广链接地址类型 1安装包2连接地址
				)
			);
			$this->actionLog('info', '分配记录添加成功clid:'.$assClId.',广告位:'.$dclid);//日志
			$this->responseExit($ret);
		}

		$clData = array(
			'name' => I('param.adp_name'),
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
		//$addClData[] = $clData;
		//增加计费标识
		if ($clModel->create($clData) === false) {
			$this->actionLog('error', '计费标识分配错误:'.$clModel->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识分配错误:'.$clModel->getError()));
		}
		$insClId = $clModel->add();
		if ($insClId === false ) {
			$this->actionLog('error', '计费标识分配错误:'.$clModel->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识分配错误:'.$clModel->getError()));
		} else {
			//添加后更新计费标识编码
			$_upmap['id'] = $insClId;
			$_upmap['code'] = $clModel->generalCode($insClId);
			if ($clModel->save($_upmap) === false) { //更新失败删除刚添加的计费标识
				$this->delete($insClId);
				$this->actionLog('error', '更新计费标识编码错误:'.$clModel->getError());//日志
				$this->responseExit(array('errcode'=>'5000','msg'=>'更新计费标识编码错误:'));
			}
		}

		//增加分配记录
		$_defualt['cl_id'] = $insClId;
		$_defualt['status'] = 1; //状态使用中

		if ($clDetModle->create($_defualt) === false ) {
			$clModel->delete($insClId);//删除添加的计费标识
			$this->actionLog(SEASLOG_ERROR, '添加分配记录错误:'.$clDetModle->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>$clDetModle->getError()));
		}
		$detClId = $clDetModle->add();
		if ($detClId === false ) {
			$clModel->delete($insClId); //删除添加的计费标识
			$this->actionLog(SEASLOG_ERROR, '添加分配记录错误:'.$clDetModle->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>$clDetModle->getError()));
		} else {
			//添加后更新分配记录编码
			$_detmap['id'] = $detClId;
			$_detmap['code'] = $clDetModle->generalCode($insClId, $detClId);
			if ($clDetModle->save($_detmap) === false) { //更新失败删除刚添加的计费标识分配记录
				$clModel->delete($insClId);//删除添加的计费标识
				$clDetModle->delete($detClId);
				$this->actionLog(SEASLOG_ERROR, '分配记录编码错误');//日志
				$this->responseExit(array('errcode'=>'5000','msg'=>$clDetModle->getError()));
				return false;
			}
		}
		$this->actionLog('info', '计费标识分配成功:cl_Id=>'.$insClId);//日志
		$this->responseExit(array('errcode'=>'0','msg'=>'计费标识id','data'=>array('CL_ID'=>$insClId)));
	}

	/**
	 * @deprecated 分发平台 创建广告位 ----------弃用
	 
	public function createForDist_old() {
		$_GET = array(
			'pro_id'=>5,
			'access_price'=>12,
			'bl_id'=> 1,
			'sb_id'=> 1,
			'sup_id'=> 1,
			'business_uid'=>1,
			'promotion_price_type'=>1,
			'promotion_price'=>12, //推广单价
			'charging_mode'=>1, //计费模式
			'return_cycle'=>1,
			'settlement_cycle'=>1,
			'deduction_ratio'=>1,
			'in_settlement_prate'=>0.4,
			'promotion_stime'=>'2016-1-1',
			'assign_clid'=>,//制定某条计费标识
		);
		$param = array(
			'id',
			'pro_id', //产品id
			'access_price',//接入单价
			'bl_id', //业务线
			'sb_id', //结算主体
			'sup_id', //供应商
			'promotion_stime', //开始推广时间
			'promotion_price_type',//推广价格类型
			'promotion_price', //推广单价
			'charging_mode', //计费模式
			//'return_cycle', //返量周期
			'settlement_cycle', //结算周期
			'deduction_ratio', //扣量比例
			'in_settlement_prate', //内部结算利润率
		);

		$get = I('param.');
		foreach ($param as $p) {
			if (!isset($get[$p])) {
				$this->responseExit(array('errcode'=>'4004','msg'=>$p . '参数不能为空'));
			}
		}

		$clModel = D('Home/ChargingLogo');
		$clDetModle = D('Home/ChargingLogoDetail');

		$getid = I('param.id');
		if ($getid != 0) {
			$ret = array(
				'errcode'=>'5000',
				'msg'=>'重复创建',
			);
			$this->responseExit($ret);
		}

		if(M('supplier')->where('id='.$get['sup_id'])->count() <= 0) {
			$this->responseExit(array('errcode'=>'4004','msg'=>'没有此供应商'));
		}

		//根据分配记录最后一条的状态
		$_proId = I('param.pro_id');
		$_price = I('param.access_price', 0, 'floatval');
		//获取分配记录状态为未使用或已回收的 计费标识
		$avaiExistData = M()->query("SELECT tab_cla.status,tab_cl.id AS cl_id,tab_cla.id,tab_cla.promotion_etime,tab_cl.name,tab_cl.promotion_url_type,tab_cl.promotion_url FROM boss_charging_logo tab_cl
LEFT JOIN boss_charging_logo_assign tab_cla ON tab_cl.id=tab_cla.cl_id
WHERE tab_cl.prot_id={$_proId} AND tab_cl.price={$_price} AND NOT EXISTS(SELECT a.id FROM boss_charging_logo a JOIN boss_charging_logo_assign b ON a.id=b.cl_id WHERE b.`status`<=1 AND tab_cl.id=a.id)
");
		//去重，过滤出 cl_id 相同且最大的一条
		$_tmp = array();
		foreach ($avaiExistData as $aed) {
			if(isset($_tmp[$aed['cl_id']])){
				if ($_tmp[$aed['cl_id']]['id'] < $aed['id']) {
					$_tmp[$aed['cl_id']] = $aed;
				}
			} else {
				$_tmp[$aed['cl_id']] = $aed;
			}
		}

		$avaiClData = array_values($_tmp);
		if (empty($avaiClData)) {
			$this->responseExit(array('errcode'=>'5000','msg'=>'根据产品id+接入单价,没有可分配的计费标识'));
		}

		$lastInfo = array();
		//是否指定分配到某条计费标识
		$assignTo = I('param.assign_clid', 'intval', 0);
		if ($assignTo > 0) {
			foreach ($avaiClData as $cl) {
				if($cl['cl_id'] == $assignTo) {
					$lastInfo = $cl;
					break;
				}
			}
			if (empty($lastInfo)) {
				$this->actionLog('error', '指定的计费标识不存在或不可用clid:'.$assignTo);//日志
				$this->responseExit(array('errcode'=>'5000', 'msg'=>'制定的计费标识不存在或不可用'));
			}

		}

		if (empty($lastInfo)) {
			foreach ($avaiClData as $cl) {
				if($cl['status'] == 2) { //优先使用未分配
					$lastInfo = $cl;
					break;
				}
			}
		}

		if (empty($lastInfo)) { //随机分配一条,且回收时间不等于今天
			while(!empty($avaiClData)) {
				$key = array_rand($avaiClData,1);
				$lastInfo = $avaiClData[$key];
				$flagClId = $lastInfo['cl_id'];
				//查询状态 已回收 最后一条分配记录
				$ldetInfo = $clDetModle->where("cl_id={$lastInfo['cl_id']} AND status=3")->order('id DESC')->limit(1)->find();
				if (strtotime($ldetInfo['promotion_etime']) == strtotime(date('Y-m-d'))) {
					unset($avaiClData[$key]);
					$lastInfo = array();
					continue;
				} else {
					!empty($ldetInfo) && $lastInfo = array_merge($lastInfo, $ldetInfo);
					break;
				}
			}

		}

		if (empty($lastInfo)) {
			$this->responseExit(array('errcode'=>'5000','msg'=>'由于计费标识clid:'.$flagClId.'回收时间等于今天,无法分配计费标识'));
		}

		if (strtotime($lastInfo['promotion_etime']) >= strtotime($get['promotion_stime']) ) {
			$this->responseExit(array('errcode'=>'5000','msg'=>'所分配的计费标识clid:'.$lastInfo['cl_id'].'开始推广时间不能晚于上次结束时间:'.$lastInfo['promotion_etime']));
		}

		if ($lastInfo['status'] == 2) {
			//未分配的(默认分配记录)将状态 修改为使用中
			$map = $get;
			$map['id'] = $lastInfo['id'];
			$map['status'] = 1;
			//return_cycle
			if ($clDetModle->save($map) === false ) {
				$this->actionLog('error', '分配记录(状态由未分配>使用中)cl_id>'.$lastInfo['id'].'错误:'.$clDetModle->getError());//日志
				$this->responseExit(array('errcode'=>'5000', 'msg'=>'分配错误：'.$clDetModle->getError()));
			}
			$dclid = $lastInfo['id'];
		} else {
			//增加分配记录
			$adddata = $get;
			$adddata['cl_id'] = $lastInfo['cl_id'];
			$adddata['status']= 1;
			if ($clDetModle->doAdd($adddata) === false ) {
				$this->actionLog('error', '分配记录添加失败:' . $clDetModle->getError());//日志
				$this->responseExit(array('errcode'=>'5000', 'msg'=>$clDetModle->getError()));
			}
			$dclid = $clDetModle->getLastInsID();
		}

		$promUrl = '';
		if ($lastInfo['promotion_url_type'] == 2 && !empty($lastInfo['promotion_url'])) {
			$promUrl = $lastInfo['promotion_url'];
		} else if ($lastInfo['promotion_url_type'] == 1 && !empty($lastInfo['promotion_url'])) {
			$promUrl = 'http://'.$_SERVER['HTTP_HOST'].$lastInfo['promotion_url'];
		}

		$ret = array(
			'errcode'=>'0',
			'msg'=>'创建成功',
			'data'=>array(
				'cl_id' =>   $lastInfo['cl_id'], //计费标识地址
				'adp_id' =>  $dclid, //广告位
				'cl_name'=>  $lastInfo['name'],//计费标识名称
				'url'=>      $promUrl,
				'url_type'=> $lastInfo['promotion_url_type'],//推广链接地址类型 1安装包2连接地址
			)
		);
		$this->actionLog('info', '分配记录添加成功clid:'.$lastInfo['cl_id'].',广告位:'.$dclid);//日志
		$this->responseExit($ret);
	}

*/
	//计费标识回收
	public function recycle() {

		/*$_GET = array(
			'cl_id'   => '10573',//计费标识id
			'end_date' => '2016-02-02',//回收时间(结束时间)
			'reason'    => '张三, 回收了',//回收原因
		);*/

		$param = array(
			'cl_id',   //计费标识id
			'end_date',//回收时间(结束时间)
			'reason'//回收原因
		);

		$get = I('param.');
		foreach ($param as $p) {
			if (!isset($get[$p])) {
				$this->responseExit(array('errcode'=>'4004','msg'=>$p . '参数不能为空'));
			}
		}

		$clModel = D('Home/ChargingLogo');
		$clDetModle = D('Home/ChargingLogoDetail');
		$clId = $get['cl_id'];
		$clData = $clModel->find($clId);
		if(empty($clData)) {
			$this->responseExit(array('errcode'=>'4004','msg'=>'计费标识不存在'));
		}

		if ($clData['status'] == 0) {
			$this->responseExit(array('errcode'=>'5000','msg'=>'计费标识已停止'));
		}

		$where['cl_id'] = $clId;
		$where['status'] = 1;
		//最后一条
		$detInfo = $clDetModle->where($where)->order('id DESC')->limit(1)->find();
		if (empty($detInfo)) {
			$this->actionLog('error', '回收失败：未找到相应分配记录cl_id='.$clId);//日志
			$this->responseExit(array('errcode'=>'4004','msg'=>'未找到相应分配记录'));
		} else if (!in_array($detInfo['status'], array(1,2))) {
			$this->actionLog('error', '回收失败：状态：'.$this->status[$detInfo['status']]);//日志
			$this->responseExit(array('errcode'=>'4004', 'msg'=>'分配记录'.$this->status[$detInfo['status']]));
		}
		if (strtotime($detInfo['promotion_stime']) > strtotime($get['end_date']) ) {
			$this->actionLog('error', '回收失败：结束时间不能小于开始时间：'. $detInfo['promotion_stime']);//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'结束时间不能小于开始时间:' . $detInfo['promotion_stime']));
		}

		$_map['promotion_etime'] = $get['end_date'];
		$_map['remark'] = $get['reason'];
		$_map['status'] = 3; //已回收

		if($clDetModle->where('id='.$detInfo['id'])->save($_map)===false) {
			$this->actionLog('error', '回收失败：'.$clDetModle->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>'回收失败:'.$clDetModle->getError()));
		}

		$retdata = array(
			'errcode'=>0,
			'msg'=>'计费标识回收成功',
		);
		$this->actionLog('info', '回收成功');//日志
		$this->responseExit($retdata);

	}


	/**
	 * 根据产品id 获取可用的计费标识 (库存)
	 */
	public function stock() {

		$proId = I('param.pro_id','');

		if(empty($proId)) {
			$this->responseExit(array('errcode'=>'4004','msg'=>'pro_id 参数错误'));
		}

		$clDetModle = D('Home/ChargingLogoDetail');

		$p = $this->page;
		if($p<1) $p=1;
		$offset = ($p-1)*$this->count;
		//根据产品id获取 未分配或已回收 的计费标识
		$cldata = M()->query("SELECT tab_cl.id,tab_cl.name,tab_cl.promotion_url,tab_cl.price_type,tab_cl.price,tab_cl.charging_mode,tab_cl.prot_id,tab_cl.back_url,tab_cl.account,tab_cl.password FROM boss_charging_logo tab_cl
WHERE tab_cl.prot_id IN ({$proId}) AND NOT EXISTS(SELECT a.id FROM boss_charging_logo a JOIN boss_charging_logo_assign b ON a.id=b.cl_id WHERE b.`status`<=1 AND tab_cl.id=a.id) 
			LIMIT {$offset}, {$this->count}");

		$total = M()->query("SELECT COUNT(id) AS num FROM boss_charging_logo tab_cl
WHERE tab_cl.prot_id IN ({$proId}) AND NOT EXISTS(SELECT a.id FROM boss_charging_logo a JOIN boss_charging_logo_assign b ON a.id=b.cl_id WHERE b.`status`<=1 AND tab_cl.id=a.id)");
		$total = $total[0]['num'];

		$_tmp = array();
		if (!empty($cldata)) {
			foreach($cldata as $cl) {
				$_tmp[] = array(
					'back_url'=>$cl['back_url'],
					'account'=>$cl['account'],
					'password'=>$cl['password'],
					'pro_id'=>$cl['prot_id'],
					'cl_id'=>$cl['id'],
					'cl_name'=>$cl['name'],
					'promotion_url'=>$cl['promotion_url'],
					'price_type' => $cl['price_type'],
					'price' => $cl['price'],
					'charging_mode' => $cl['charging_mode']
				);
			}
		}
		$retdata = array(
			'errcode'=>0,
			'msg'=>'操作成功',
			'data' => $_tmp,
			'current_page'=>$this->page,
			'total_number'=>$total,
		);
		$this->actionLog('info', '获取库存成功');//日志
		$this->responseExit($retdata);
	}


	/**
	 * 分发，库存历史记录
	 *
	 */
	public function stockHistory() {

		$blId = I('param.bl_id',0,'intval');

		if($blId <= 0) {
			$this->responseExit(array('errcode'=>'4004','msg'=>'pro_id 参数错误'));
		}

		$clDetModle = D('Home/ChargingLogoDetail');
		$cldata = $clDetModle->field('b.`prot_id`,
							  b.id,
							  IFNULL (a.promotion_price_type,0) AS promotion_price_type,
							  IFNULL (a.promotion_price,0) AS promotion_price,
							  IFNULL (a.status,2) AS `status`,       
					          b.charging_mode,
							  b.price_type AS in_price_type,
							  b.price AS in_price')
							->alias('a')
							->join('RIGHT JOIN `boss_charging_logo` b 
							    ON a.`cl_id` = b.id 
							    JOIN boss_product c 
							    ON c.id=b.prot_id')
							->where("c.bl_id={$blId}")
							->order("b.id DESC")
			                ->page($this->page, $this->count)
							->select();

		$total = $clDetModle
			->alias('a')
			->join('RIGHT JOIN `boss_charging_logo` b 
			    ON a.`cl_id` = b.id 
			    JOIN boss_product c 
			    ON c.id=b.prot_id')
			->where("c.bl_id={$blId}")
			->count();

		$_tmp = array();
		if (!empty($cldata)) {
			foreach($cldata as $cl) {
				$_tmp[] = array(
					'cl_id'=>$cl['id'],
					'pro_id'=>$cl['prot_id'],
					'promotion_price_type' => $cl['promotion_price_type'],
					'promotion_price' => $cl['promotion_price'],
					'status'=>$cl['status'],
					'charging_mode' => $cl['charging_mode'],
					'in_price_type' => $cl['in_price_type'],
					'in_price' => $cl['in_price'],
				);
			}
		}
		$retdata = array(
			'errcode'=>0,
			'msg'=>'操作成功',
			'data' => $_tmp,
			'current_page'=>$this->page,
			'total_number'=>$total,
		);
		$this->actionLog('info', '成功获取库存历史记录');//日志
		$this->responseExit($retdata);
	}
	public function getStopClId(){
		$blId = I('param.bl_id', 0, 'intval');
		if ($blId <= 0) {
			$retdata['msg'] = '缺少参数';
			$retdata['error_code'] = 4001;
		} else {
			$res=M('charging_logo')->field('id as cl_id')->where('status=0')->select();
			$retdata['data']=$res;
			$retdata['msg'] = '执行成功';
			$retdata['error_code'] = 0;
		}
		$this->response($retdata);
	}

}


