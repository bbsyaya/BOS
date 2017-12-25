<?php
namespace Home\Model;
use Think\Model;

/**
 * 广告主联系人
 * Class AdvertiserContactsModel
 * @package Home\Model
 */
class AdvertiserContactsModel extends Model {

	protected $_validate = array(

		array('ad_id','require','广告主id必填', self::MUST_VALIDATE , 'regex'),
		array('name','require','对接人姓名必填', self::MUST_VALIDATE , 'regex'),
		array('name','0,10', '对接人姓名最长10个字符', self::MUST_VALIDATE , 'length'),
		array('email', 'email', '对接人邮箱格式不正确', self::VALUE_VALIDATE), //邮箱格式不正确
		array('mobile', 'require', '对接人手机格式不正确', self::VALUE_VALIDATE,'regex'),
		//array('address','0,50','对接人地址最长50个字符', self::VALUE_VALIDATE , 'length'),
		array('qq','0,15','qq号不能超过15个字符', self::VALUE_VALIDATE , 'length'),
		array('user','require', '我方对接人必填', self::MUST_VALIDATE , 'regex'),
	);

	public function getdata($where){
		$M=M('advertiser_contacts');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where){
		$M=M('advertiser_contacts');
		$res=$M->where($where)->find();
		return $res;
	}
}