<?php
namespace Home\Model;
use Think\Model;
class SupplierFinanceModel extends Model {

	protected $_validate = array(
		array('sp_id','require','供应商id不能为空', self::MUST_VALIDATE , 'regex'),
		array('bl_id','require','业务线id不能为空', self::MUST_VALIDATE , 'regex'),
		array('invoice_type','1,2,3,4,5','发票类型id错误', self::MUST_VALIDATE , 'in'),
		array('object_type','1,2','财务对象id错误', self::MUST_VALIDATE , 'in'),
		array('payee_name','require','收款方名称不能为空', self::MUST_VALIDATE , 'regex'),
		array('opening_bank','require','开户行名称不能为空', self::MUST_VALIDATE , 'regex'),
		array('bank_no','require','银行账号不能为空', self::MUST_VALIDATE , 'regex'),

	);

	public function getdata($where){
		$M=M('supplier_finance');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where){
		$M=M('supplier_finance');
		$res=$M->where($where)->find();
		return $res;
	}

}