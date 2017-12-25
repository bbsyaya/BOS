<?php
/**
 * 分发平台api
 */
namespace Common\Api;

define('APPSECRET', "b#asb%svp&^");
define('URL_BASE','http://dist.youxiaoad.com/api.php/'); // 192.168.7.77   ; 正式 dist.youxiaoad.com

define('URL_STOP_CHARGING_LOGO','Promotionstop');

class DistApi { //extends api

	private $signData = array();
	private $postHeader = array();
	public $retInfo = array();

	public function __construct() {
		$this->_init();
	}


	protected function _init() {

		$this->signData['ts']   = time();
		$this->signData['sign'] = md5(APPSECRET.$this->signData['ts']);

		$this->postHeader = array('Host: dist.youxiaoad.com');
    }


	/**
		"data" :[{
		"cl_id" : 1,			//计费标识id
		"charging_logo" : 0,		//计费标识名称
		},...]
	 */
	public function stopChargingLogo($data) {

		$datas[] = $data;
		$this->postData(URL_STOP_CHARGING_LOGO, $datas);
		return $this->responseOk();
	}


//-------------------------------------------------------------------

	public function postData($url,$data,$retType='json') {

		$this->signData['data'] = json_encode($data);
		if ($retType == 'json') {
			$this->retInfo = json_decode(bossPostData(URL_BASE.$url, $this->signData, $this->postHeader),true);
		}
		$this->actionLogo();
		return $this->retInfo;
	}


	public function actionLogo() {
		$retInfo = $this->retInfo;
		$paramStr = http_build_query(json_decode($this->signData['data'],true));
		$info = '参数:'. $paramStr . '返回：code:'.$retInfo['code'].'msg:'.$retInfo['msg'];
		if ($retInfo['code'] == 'B200') { //成功
			action_log('partner', 'info', $_SESSION['userinfo']['realname'], $info, 'DistApi/Promotionstop');//日志
		} else { //失败
			action_log('partner', 'error', $_SESSION['userinfo']['realname'], $info, 'DistApi/Promotionstop');//日志
		}
	}


	public function responseOk() {
		if ($this->retInfo['code'] == 'B200') { //成功
			return true;
		} else {
			return false;
		}
	}

	public function getErrorInfo() {

	}
}
