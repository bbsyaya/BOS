<?php
namespace Api\Controller;
use Think\Controller\RestController;

/**
 * 接口基类
 * Class ApiController
 * @package Api\Controller
 */
class ApiController extends RestController  {

	protected $appInfo = array(
		//appid => appsecret
		'100' => 'ShHm4Eu/Y8IKv',//测试用
		'101' => 'b#o$s%sxv&3',//ssp
		'102' => 'b#o$sxxf#v&3',//adx
		'103' => 'b#a$b%s@v&*',//自有平台
		'104' => 'b#asb%svp&^',//分发平台
		'105' => 'b#ffb%xxp&^',//oa
		'106' => '5WemmnpacX8',//ssp2
		'107' => 'b#d#bsp#w%$',//发行平台
		'108' => '@#$%ESW@#?&*%$#',//营销平台
	);

	protected $appNameMap = array(
		'100' => '测试用',//
		'101' => 'ssp',//
		'102' => 'adx',//
		'103' => '自有平台(商业软件矩阵)',//
		'104' => '分发平台',//
		'105' => 'Oa',//
		'106' => 'ssp2',
		'107' => '发行平台',//发行平台
		'108' => '营销平台dsp',//营销平台dsp
	);

	protected $page = 1;
	protected $count = 20;
	protected $appId = 0;

	public function _initialize() {

		/*$_POST['sign'] = '702c8490576cae6e6d14e5515d4b42c2';
		$_POST['ts'] = '1476670082';
		$_POST['appid'] = '101';*/
		//安全验证
		
		$this->appId = I('param.appid',0,'intval');
		$this->page = I('param.page',1,'intval'); //当前页
		$this->count = I('param.count',20,'intval');//每页条数
		if ($this->count > 50) {
			$this->response(array('errcode'=>'4004','msg'=>'每页最多显示50条数据'));
		}
		$this->_checkSign();
		$excludeModule = array('Oa','ChargingLogo','Supplier','Product');
		if (!in_array(CONTROLLER_NAME, $excludeModule)) {
			$this->actionLog('info', '接口访问记录');
		}
	}

	//验证请求参数是否合法
	private function _checkSign() {

		$timestamp = I('param.ts',0,'intval');
		$sign = I('param.sign','');
		$expire = 120; //2分钟超时
		$appInfo = $this->appInfo;

		/*if (NOW_TIME-$timestamp > $expire) {
			$this->response(array('errcode'=>'4001','msg'=>'链接已过期,请重新请求'));
		}*/
		if (!isset($appInfo[$this->appId])) {
			$this->actionLog('error',$appInfo[$this->appId]);
			$this->response(array('errcode'=>'4002', 'msg'=>'appid错误','errorInfo'=>$appInfo[$this->appId]));
			exit;
		}

		$appSecret = $appInfo[$this->appId];
		if (md5($appSecret.$timestamp) != $sign) {
			$this->response(array('errcode'=>'4003','msg'=>'sign验证失败'));
			exit;
		}

	}


	//记录行为日志
	protected function actionLog($level='info', $content) {
		actionlog();
		/*
		$content = '参数：'.http_build_query(I('param.')).'--'.$content;
		$content = urldecode($content);
		action_log('api', $level, $this->appNameMap[$this->appId], $content, CONTROLLER_NAME.'/'.ACTION_NAME);
		*/
	}


	//返回业务系统数据
	protected function response($data,$type='json',$code=200) {
		ob_end_clean();
		echo json_encode($data);
	}

	protected function responseExit($data,$type='json',$code=200) {
		ob_end_clean();
		echo json_encode($data);
		exit;
	}
	/**
	 * 防止中文转码转换
	 * @param  [type]  $data [description]
	 * @param  string  $type [description]
	 * @param  integer $code [description]
	 * @return [type]        [description]
	 */
	protected function responseExit_Unicode($data,$type='json',$code=200) {
		ob_end_clean();
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
		exit;	
	}

	public function checkCS($cs){
		$arr=explode(',', $cs);
		foreach ($arr as $key => $value) {
			if(empty(I('post.'.$value))){
				$this->response(array('status'=>2,'msg'=>$value.'参数缺失'));
				exit();
			}
		}
	}

	/**
	 * api调用日志记录
	 * @param  [type] $url          [调用接口地址]
	 * @param  string $requestData  [接收数据]
	 * @param  string $responseData [响应数据]
	 * @return [type]               [description]
	 */
	public function apiLogWrite($responseData=""){
		//获取完整的url
		$data["url"]     = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$data["data"]    = json_encode($_REQUEST,JSON_UNESCAPED_UNICODE);
		$data["res"]     = json_encode($responseData,JSON_UNESCAPED_UNICODE);
		$data["addtime"] = date("Y-m-d H:i:s",time());
		$row = M("apilog")->add($data);
	}

}