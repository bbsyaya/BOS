<?php
namespace Home\Model;
use Think\Model;
class BusinessLineModel extends Model {

	protected $tableName = 'business_line';
	public $totalPage = 0;

	protected $_validate = array(
		array('name','require','业务线名称不能为空', self::MUST_VALIDATE , 'regex'),
	);

	public function getdata($where){
		$M=M('business_line');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where){
		$M=M('business_line');
		$res=$M->where($where)->find();
		return $res;
	}
	public function grouplist(){//获取所有业务线名称及ID
		$M=M('business_line');
		$res=$M->field('name,id')->find();
		return $res;
	}


	/**
	 * 列表
	 * @param $where
	 * @return array|mixed
	 */
	public function getList($where) {

		$blList = $this->where($where)->order('id desc')->page($_GET['p'],10)->select();
		$this->totalPage = $this->where($where)->count();

		//没有数据
		if ($this->totalPage == 0) {
			return array();
		}

		$typeIds = $sbIds = '';
		foreach ($blList as $val) {
			$_tmp[] = $val['type'];
			$_tmp1[] = $val['sb_id'];
		}
		$typeIds = implode(',', $_tmp);
		$sbIds = implode(',', $_tmp1);

		$typeArr = M('data_dic')->where("id IN ({$typeIds})")->getField('id,name');
		$sbArr = M('data_dic')->where("id IN ({$sbIds})")->getField('id,name');

		foreach ($blList as &$val) {
			$val['type'] = $typeArr[$val['type']];
			$val['sb_id'] = $sbArr[$val['sb_id']];
		}

		return $blList;

	}

	public function generalCode($id) {
		return 'BL' . str_pad(intval($id), 7, 0, STR_PAD_LEFT);
	}

}