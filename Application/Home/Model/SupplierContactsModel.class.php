<?php
namespace Home\Model;
use Think\Model;

/**
 * 供应商联系人
 * Class AdvertiserContactsModel
 * @package Home\Model
 */
class SupplierContactsModel extends Model {

	protected $_validate = array(

		array('sp_id', 'require', '供应商id不能为空', self::MUST_VALIDATE , 'regex'),
		array('bl_id', 'require', '业务线id不能为空', self::MUST_VALIDATE , 'regex'),

		array('name','require', '联系人姓名不能为空', self::MUST_VALIDATE , 'regex'),
		array('name','0,10', '联系人姓名最长10个字符', self::MUST_VALIDATE , 'length'),
		array('email', 'email', '联系人邮箱格式不正确', self::EXISTS_VALIDATE), //邮箱格式不正确
		// array('mobile', 'require', '联系人手机不能为空', self::MUST_VALIDATE, 'regex'),
		array('address','0,50', '联系人地址最长50个字符', self::EXISTS_VALIDATE , 'length'),
		array('qq', '0,15', 'qq号不能超过15个字符', self::EXISTS_VALIDATE , 'length'),
		array('business_uid', 'require', '联系人商务不能为空', self::MUST_VALIDATE,'regex'),
		array('business_uid', 'integer', '联系人商务格式为id', self::MUST_VALIDATE,'regex'),
	);


	public function getonedata($where){
		$res=$this->where($where)->find();
		return $res;
	}


}