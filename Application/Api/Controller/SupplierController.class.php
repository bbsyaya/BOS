<?php
namespace Api\Controller;
//供应商相关
class SupplierController extends ApiController {

    //添加修改供应商基本信息
	public function base() {

		/*$_GET = array(
			'id'=>106,
			'email' => 'tstexx@testccff.com',
			'name' => 'ttyyqq',
			'type' => '2',
			'mobile' => '15125125',
		);*/

		//获取供应商region
		$region_name = I("region");
		$address = I("address");
		$type = I("type");
		if($type == 2){//类型为企业必填
			if(empty($region_name) || empty($address)){
				$this->responseExit(array('errcode'=>'5000','msg'=>"供应商地址【address】和地区【region】都不能为空，请联系管理员核对"));exit;
			}
		}

		$region_one = M("region")->field("id")->where(array("name"=>$region_name))->find();
		if($region_one){
			$region_id = $region_one["id"];
			$_POST["region"] = $region_id;
		}else{
			$this->responseExit(array('errcode'=>'5000','msg'=>"地区字段region：".$region_name."在boss中未找到，请联系管理员核对"));exit;
		}
		

		$supModel = D('Home/Supplier');
		$id = I('param.id',0,'intval');
		$_meta = '';
		$data_sup=$supModel->where("name='".I('param.name')."' && email='".I('param.email')."'")->find();
		if($data_sup){
			if ($id > 0) { //修改
				$_meta = '供应商修改成功';
				if ($supModel->save() === false) {
					$this->actionLog(SEASLOG_ERROR, '供应商修改失败:'.$supModel->getStrError());//日志
					$this->responseExit(array('errcode'=>'5000','msg'=>$supModel->getStrError()));
				}
			}else{
				$id=$data_sup['id'];
				$_meta = '供应商已存在';
			}
		}elseif($supModel->create(I('param.')) === false ) {
			$errCode = (int)$supModel->getError();
			if ($errCode == -1) { //供应商邮箱重复
				$extWhere['email'] = I('param.email','');
				$existSpId = $supModel->where($extWhere)->getField('id');
				$this->actionLog(SEASLOG_ERROR, '供应商修改失败:'.$supModel->getStrError());//日志
				$this->responseExit(array('errcode'=>'5000','msg'=>$supModel->getStrError(),'data'=>array('spid'=>$existSpId)));
			}
			$this->actionLog(SEASLOG_ERROR, '供应商修改失败:'.$supModel->getStrError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>$supModel->getStrError()));
		} else {
			if ($id > 0) { //修改
				$_meta = '供应商修改成功';
				if ($supModel->save() === false) {
					$this->actionLog(SEASLOG_ERROR, '供应商修改失败:'.$supModel->getStrError());//日志
					$this->responseExit(array('errcode'=>'5000','msg'=>$supModel->getStrError()));
				}
			} else {
				$_meta = '供应商添加成功';
				
				if ($supModel->add() === false) {
					$this->actionLog(SEASLOG_ERROR, '供应商添加失败:'.$supModel->getStrError());//日志
					$this->responseExit(array('errcode'=>'5000','msg'=>$supModel->getStrError()));
				} else {
					$id = $insId = $supModel->getLastInsID();
					$_map['code'] = $supModel->generalCode($insId);
					$_map['id'] =   $insId;
					if ($supModel->save($_map) === false) {
						$supModel->delete($insId);
						$this->actionLog(SEASLOG_ERROR, '更新供应商编码失败:'.$supModel->getStrError());//日志
						$this->responseExit(array('errcode'=>'5000','msg'=>'更新编码失败,请从新操作'));
					}

				}
			}
		}
		$retData = array(
			'errcode'=>'0',
			'msg'=>$_meta,
			'data'=>array('spid'=>$id),
		);
		$this->actionLog('info', $_meta.'spid>'.$id);//日志
		$this->responseExit($retData);

	}

	//添加修改供应商财务信息
	public function finance() {

		/*$_GET = array(
			'id'=>6,
			'sp_id' => 1,
			'bl_id' => '1',
			'invoice_type' => 1,
			'object_type' => 1,
			'payee_name' => '重庆市华宇科技11',
			'opening_bank' => '重庆市渝中区',
			'bank_no' => '234234234234',
		);*/

		$id = I('param.id');
		$spId = I('param.sp_id','');
		$_meta = '';
		$addData = I('param.');

		if (empty($spId)) {
			$this->responseExit(array('errcode'=>'4004','msg'=>'供应商id不能为空'));
		}

		$supplierId = D('Home/Supplier')->where("id='{$spId}'")->getField('id');
		if ((int)$supplierId<=0) {
			$this->responseExit(array('errcode'=>'5000','msg'=>'无对应供应商信息'));
		}

		if ($id > 0) {
			if (D('Home/SupplierFinance')->where('id='.$id)->count()<=0) {
				$this->responseExit(array('errcode'=>'5000','msg'=>'无对应供应商财务信息'));
			}
		}

		//如果有重复数据，覆盖
		//$repMap['payee_name'] = I('param.payee_name');
		$repMap['bl_id'] = I('param.bl_id');
		$repMap['sp_id'] = I('param.sp_id');
		$exist = D('Home/SupplierFinance')->where($repMap)->find();
		if (!empty($exist)) {
			$id=$exist['id'];
			$addData['id']=$exist['id'];
		}

		$addData['sp_id'] = $supplierId;
		$supFinModel = D('Home/SupplierFinance');
		if($supFinModel->create($addData) === false ) {
			$this->actionLog(SEASLOG_ERROR, '供应商财务数据错误:'.$supFinModel->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>$supFinModel->getError()));
		} else {
			if ($id > 0) {
				$r = $supFinModel->save($addData);
				$_meta = '修改';
			} else {
				unset($addData['id']);
				$id = $r = $supFinModel->add($addData);
				$_meta = '添加';
			}
			if ($r === false) {
				$this->actionLog(SEASLOG_ERROR, '供应商财务'.$_meta.'失败:'.$supFinModel->getError());//日志
				$this->responseExit(array('errcode'=>'5000','msg'=>$supFinModel->getError()));
			}
		}
		$supinfo=M('supplier')->where("id='{$spId}'")->find();
		if($supinfo['type']!=1){
			M('supplier')->where("id='{$spId}'")->save(array('fukuanname'=>I('post.payee_name')));
		}
		$retData = array(
			'errcode'=>'0',
			'data'=>array('spfid'=>$id),
			'msg'=>'供应商财务信息'.$_meta.'成功',
		);
		$this->actionLog('info', '供应商财务信息'.$_meta.'成功'.'spfid>'.$id);//日志
		$this->responseExit($retData);
	}

	/**
	 * 添加修改供应商联系人
	 */
	public function contract() {
		/*$_GET = array(
			'id'=>8,
			'sp_id' => 1,
			'bl_id' => 1,
			'name' => 'xxxx',
			'mobile' => '15086732658',
			'qq' => '40555545',
			'email' => 'test@tt.com',
			'address' => '234234234234',
			'business_uid' => 1,
		);*/

		$id = I('param.id');
		$spId = I('param.sp_id','');
		$adddata = I('param.');
		$_meta = '';
		if (empty($spId)) {
			$this->responseExit(array('errcode'=>'4004','msg'=>'供应商id不能为空'));
		}

		$supplierId = D('Home/Supplier')->where("id='{$spId}'")->getField('id');
		if ((int)$supplierId<=0) {
			$this->responseExit(array('errcode'=>'5000','msg'=>'无对应供应商信息'));
		}
		//如果有重复数据，覆盖
		$repMap['name'] = I('param.name');
		$repMap['business_uid'] = I('param.business_uid');
		$repMap['sp_id'] = I('param.sp_id');
		$exist = D('Home/SupplierContacts')->where($repMap)->find();
		if (!empty($exist)) {
			$id=$exist['id'];
			$adddata['id']=$exist['id'];
		}

		$adddata['sp_id'] = $supplierId;
		$supConModel = D('Home/SupplierContacts');
		if($supConModel->create($adddata) === false ) {
			$this->actionLog(SEASLOG_ERROR, '供应商联系人数据错误:'.$supConModel->getError());//日志
			$this->responseExit(array('errcode'=>'5000','msg'=>$supConModel->getError()));
		} else {
			if ($id > 0) {
				$_meta = '修改';
				$r = $supConModel->save($adddata);
			} else {
				$_meta = '添加';
				unset($adddata['id']);
				$id = $r = $supConModel->add($adddata);
			}

			if ($r === false) {
				$this->actionLog(SEASLOG_ERROR, '供应商联系人'.$_meta.'失败:'.$supConModel->getError());//日志
				$this->responseExit(array('errcode'=>'5000','msg'=>$supConModel->getError()));
			}
		}

		$retData = array(
			'errcode'=>'0',
			'data'=>array('spcid'=>$id),
			'msg'=>'供应商联系人'.$_meta.'成功',
		);
		$this->actionLog('info', '供应商联系人'.$_meta.'成功'.'spcid>'.$id);//日志
		$this->responseExit($retData);

	}

}


