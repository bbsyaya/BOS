<?php
namespace Home\Model;
use Think\Model;
class AdvertiserFinanceModel extends Model {

	protected $_validate = array(
		array('id','require','广告主id不能为空', self::MUST_VALIDATE , 'regex'),
		array('object_type','number','账户类型不能为空', self::MUST_VALIDATE),

	);

    //财务收件人验证信息
	public $receiverRule = array(
		array('name','require','财务收件人名字必须！', self::MUST_VALIDATE, 'regex'),
		array('name','0,10', '财务收件人名字最长10个字符', self::MUST_VALIDATE , 'length'),
		array('mobile','require','财务收件人电话必须！', self::MUST_VALIDATE, 'regex'),
		array('mobile', '11', '财务收件人手机格式不正确', self::EXISTS_VALIDATE,'length'),
		array('address','0,30','财务收件人地址最长30个字符', self::EXISTS_VALIDATE , 'length'),
	);

	public function getdata($where){
		$M=M('advertiser_finance');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where){
		$M=M('advertiser_finance');
		$res=$M->where($where)->find();
		return $res;
	}


	/**
	 * 保存财务接受人
	 * @param $adId
	 * @param $data 接受人数组
	 * @return bool
	 */
	public function saveReceiver($adId, $data) {
		if($this->encodeReceiver($data)===false){
			return false;
		}
		$_map = array(
			'id' => $adId,
			'receiver'=>$this->encodeReceiver($data),
		);
		return $this->save($_map);
	}


	public function encodeReceiver($data) {
		foreach($data as $val) {
			if ($this->validate($this->receiverRule)->create($val)===false) {
				return false;
			}
		}
		return serialize($data);
	}

	public function decodeReceiver($receiver) {
		$receiverArr = unserialize($receiver);
		if(!is_array($receiverArr)) {
			return array();
		} else {
			return $receiverArr;
		}
	}

	/**
	 * 获取财务接受人
	 * @param int $adId　广告主id
	 * @return array|mixed　数组
	 */
	public function getReciever($adId=0) {
		$receiver = $this->where('id='.$adId)->getField('receiver');
		$receiverArr = $this->decodeReceiver($receiver);
		return $receiverArr;
	}

	public function getInfo($adId) {
		$res = $this->find($adId);
		$res['receiver'] = $this->decodeReceiver($res['receiver']);
		return $res;
	}

}