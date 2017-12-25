<?php
namespace Api\Controller;
//用户
class UserController extends ApiController {

    //根据用户姓名获取uid
	public function getIdByName() {

		$realName = I('param.real_name',''); //真实姓名(中文)
		$retdata = array();

		if (empty($realName)) {
			$retdata['msg'] = '参数错误';
			$retdata['error_code'] = 4001;
		} else {
			$model = M('user');
			$where['real_name'] = $realName;
			$uid = (int)$model->where($where)->getField('id');
			if ($uid <= 0) {
				$retdata['msg'] = '没有此用户的uid';
				$retdata['error_code'] = 5000;
			} else {
				$retdata = array(
					'errcode'=>'0',
					'msg'=>'操作成功',
					'data' => array(
						'uid'=>$uid,
					)
				);
			}
		}
		$this->responseExit($retdata);

	}

}


