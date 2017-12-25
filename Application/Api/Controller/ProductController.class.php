<?php
namespace Api\Controller;
//获取产品
class ProductController extends ApiController {

    //根据业务线获取产品信息
	public function by_blid() {
		//http://boss.cm/Api/Product/by_blid

		$blId = I('param.bl_id', 0, 'intval');
		$retdata = array();

		if ($blId <= 0) {
			$retdata['msg'] = '缺少参数';
			$retdata['error_code'] = 4001;
		} else {
			$model = D('Home/Product');
			$where="(pbl.bl_id=$blId || pro.bl_id=$blId) && cooperate_state<3";

			$data = $model
				->alias('pro')
				->field('pro.*')
				->join('left join boss_product_plane_bl as pbl ON pbl.pro_id=pro.id')
				->where($where)
				->page($this->page, $this->count)
				->order('pro.id DESC')
				->select();
			$total = $model
				->alias('pro')
				->join('left join boss_product_plane_bl as pbl ON pbl.pro_id=pro.id')
				->where($where)
				->count();

			$adids = array();
			$proids = array();
			$saler = array();
			if(!empty($data)){
				foreach ($data as $val) {
					$adids[] = $val['ad_id'];
					$proids[] = $val['id'];
					$saler[] = $val['saler_id'];
				}
				$adidsStr = implode(',', $adids);
				$proidsStr = implode(',', $proids);
				$salerStr = implode(',', $saler);
				//广告主名称
				$adnameArr = M('advertiser')->where("id IN ({$adidsStr})")->getField('id,name,ad_type,email');
				//产品对接人
				$contracts = M('product_contacts')->where("pro_id IN ({$proidsStr})")->getField('id,pro_id,name');
				//销售人员
				$salerInfo = M('user')->where("id IN ({$salerStr})")->getField('id,real_name,username');
				$conArr = array();
				foreach ($contracts as $val) {
					$conArr[$val['pro_id']] .= $val['name'].','; //多个用逗号隔开
				}
				foreach ($data as &$val) {
					$val['ad_name'] = $adnameArr[$val['ad_id']]['name'];
					$val['ad_type'] = $adnameArr[$val['ad_id']]['ad_type'];
					$val['ad_email'] = $adnameArr[$val['ad_id']]['email'];
					$val['contracts'] = $conArr[$val['id']];
					$val['saler_username'] = $salerInfo[$val['saler_id']]['username'];
					$val['saler_realname'] = $salerInfo[$val['saler_id']]['real_name'];
				}
			}

			$retdata = array(
				'errcode'=>'0',
				'msg'=>'操作成功',
				'data' => $data,
				'current_page' => $this->page,
				'total_number' => $total,
			);
		}
		$this->actionLog('info', '获取产品成功');//日志
		$this->responseExit($retdata);//返回数据

	}
	public function getStopProId(){
		$blId = I('param.bl_id', 0, 'intval');
		if ($blId <= 0) {
			$retdata['msg'] = '缺少参数';
			$retdata['error_code'] = 4001;
		} else {
			$res=M('product')->field('id as pro_id')->where('cooperate_state=3')->select();
			$retdata['data']=$res;
			$retdata['msg'] = '执行成功';
			$retdata['error_code'] = 0;
		}
		$this->response($retdata);
	}

}


