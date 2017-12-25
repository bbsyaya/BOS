<?php
namespace Home\Model;
use Think\Model;
class ChargingLogoDetailModel extends Model {
	protected $tableName = 'charging_logo_assign';
	const TAB_CL = 'charging_logo';
	public $totalPage = 0;

	protected $_validate = array(

		array('cl_id','require','计费标识id不能为空', self::MUST_VALIDATE , 'regex'),
		array('bl_id','require','业务线id不能为空', self::MUST_VALIDATE , 'regex'),
		array('sup_id','require','供应商id不能为空', self::MUST_VALIDATE , 'regex'),
		array('promotion_price','require','推广价格不能为空', self::MUST_VALIDATE , 'regex'),
		array('return_cycle','require','返量周期不能为空', self::MUST_VALIDATE , 'regex'),
		array('settlement_cycle','require','结算周期不能为空', self::MUST_VALIDATE , 'regex'),
		//array('promotion_stime','require','开始日期不能为空', self::MUST_VALIDATE , 'regex'),

	);

	protected $_auto = array(
		array('add_time', 'date', self::MODEL_INSERT, 'function', array('Y-m-d H:i:s')), //添加时间
	);


	public function getDetail($id) {
		$prefix = C('DB_PREFIX');
		$cl = $prefix.self::TAB_CL;
		$tabDetail = $prefix.$this->tableName;

		$this->totalPage = $this->table($cl.' AS a')
			->join(' JOIN '. $tabDetail.' AS b ON a.id=b.cl_id')
			->where("a.id={$id}")
			->count();

		$list = $this->table($cl.' AS a')
			->field('b.id,b.`code`,a.name,b.`cl_id`,a.prot_id,a.`ad_id`,b.`sb_id`,b.`sup_id`,b.`bl_id`,b.`business_uid`,a.`price`,b.`promotion_price`,
                     b.`charging_mode`,b.`return_cycle`,b.`settlement_cycle`,b.`deduction_ratio`,b.`in_settlement_prate`,b.`promotion_stime`,b.`promotion_etime`,b.`status`')
			->join(' JOIN '. $tabDetail.' AS b ON a.id=b.cl_id')
			->where("a.id={$id}")
			->order('b.id desc')
			->page($_GET['p'],10)
			->select();

		if (!empty($list)) {
			$ids = $clids = $proids = $adids = $sbids = $supids = $blids = $businessUids = '';
			foreach ($list as $val) {
				$tmp[] = $val['id'];
				$tmp1[] = $val['cl_id'];
				$tmp2[] = $val['prot_id'];
				$tmp3[] = $val['ad_id'];
				$tmp4[] = $val['sb_id'];
				$tmp5[] = $val['sup_id'];
				$tmp6[] = $val['bl_id'];
				$tmp7[] = $val['business_uid'];
			}
			$ids = implode(',', $tmp);
			$clids = implode(',', $tmp1);
			$proids = implode(',', $tmp2);
			$adids = implode(',', $tmp3);
			$sbids = implode(',', $tmp4);
			$supids = implode(',', $tmp5);
			$blids = implode(',', $tmp6);
			$businessUids = implode(',', $tmp7);

			$clArr =     empty($clids) ? array() : M(self::TAB_CL)->where("id IN ({$clids})")->getField('id,code');
			$proArr =    empty($proids) ? array() : M('product')->where("id IN ({$proids})")->getField('id,name');
			$advArr =    empty($adids) ? array() : M('advertiser')->where("id IN ({$adids})")->getField('id,name');
			$sbArr =     empty($sbids) ? array() : M('DataDic')->where("id IN ({$sbids})")->getField('id,name');
			$supArr =    empty($supids) ? array() : M('supplier')->where("id IN ({$supids})")->getField('id,name');
			$blArr =     empty($blids) ? array() : M('BusinessLine')->where("id IN ({$blids})")->getField('id,name');
			$busuidArr = empty($businessUids) ? array() : M('user')->where("id IN ({$businessUids})")->getField('id,real_name');

			//接入业务线
			$proId = (int)$list[0]['prot_id'];
			$proBlId = M('product')->where('id='.$proId)->getField('bl_id');
			$proBlName = M('BusinessLine')->where("id=$proBlId")->getField('name');
			foreach ($list as &$val) {
				$val['cl_id'] = $clArr[$val['cl_id']];
				$val['prot_id'] = $proArr[$val['prot_id']];
				$val['ad_id'] = $advArr[$val['ad_id']];
				$val['sb_id'] = $sbArr[$val['sb_id']];
				$val['sup_id'] = $supArr[$val['sup_id']];
				$val['bl_id'] = $blArr[$val['bl_id']];
				$val['business_uid'] = $busuidArr[$val['business_uid']];
				$val['pro_bl_name'] = $proBlName;
			}
		}

		//没有数据
		if ($this->totalPage == 0) {
			return array();
		}

		return $list;

	}

	public function generalCode($clid,$id) {
		return 'JFFP_'.$clid.'_'.$id;
	}

	public function doAdd($data) {
		if ($this->create($data) === false) {
			return false;
		}
		$insId = $r = $this->add();
		if ($r !== false) {
			//添加后更新计费标识编码
			$_map['id'] = $insId;
			$_map['code'] = $this->generalCode($data['cl_id'], $insId);
			if ($this->save($_map) === false) { //更新失败删除刚添加的计费标识
				$this->delete($insId);
				$this->error = '分配记录编码更新失败';
				return false;
			}

		}
		return true;
	}




	/**
	 * 根据产品id获取
	 * @param        $proId
	 * @param string $where
	 * @return mixed
	 */
	public function getByProId($proId, $where = '') {
		$prefix = C('DB_PREFIX');
		$cl = $prefix.self::TAB_CL;
		$whereStr = " b.prot_id=$proId ";
		$whereStr .= ' AND ' . $where;

		return $this->field('a.*')->join(" AS a JOIN {$cl} b ON a.cl_id=b.id")->where($whereStr)->select();
	}

}