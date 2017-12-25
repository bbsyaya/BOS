<?php
namespace Home\Model;
use Think\Model;
use Common\Service;
class ProductModel extends Model {
	const TAB_PLANE_BL = 'product_plane_bl';
	public $totalPage = 0;

	protected $_validate = array(
		array('name','require','产品名称不能为空', self::MUST_VALIDATE , 'regex'),
		array('name','0,50', '产品名称最长50个字符', self::MUST_VALIDATE , 'length'),
		array('type','require','产品类型不能为空', self::MUST_VALIDATE , 'regex'),
		array('ad_id','require','广告主名称不能为空', self::MUST_VALIDATE , 'regex'),
		array('bl_id','require','业务线(收入)不能为空', self::MUST_VALIDATE , 'regex'),
		array('sb_id','require','签订主体不能为空', self::MUST_VALIDATE , 'regex'),
		array('source_type','require','产品来源不能为空', self::MUST_VALIDATE , 'regex'),
		array('saler_id','require','责任销售不能为空', self::MUST_VALIDATE , 'regex'),
		array('category','require','产品明细分类不能为空', self::MUST_VALIDATE , 'regex'),

		array('cooperate_state','require','合作状态不能为空', self::MUST_VALIDATE , 'regex'),
		array('cooperate_state','check_cooperate_state_stop','产品无法停推,存在未收回计费标识', self::MUST_VALIDATE , 'callback'),
		array('contract_num','checkCtractData','合同oa流水号或网签不能为空', self::MUST_VALIDATE , 'callback', 0, array('cnum')),
		array('contract_webinfo','checkCtractData','合同oa流水号或网签不能为空', self::MUST_VALIDATE , 'callback', 0, array('cwebinfo')),
		array('contract_s_duration','checkCtractData','合同开始时间不能为空', self::MUST_VALIDATE , 'callback'),
		array('contract_e_duration','checkCtractData','合同结束时间不能为空', self::MUST_VALIDATE , 'callback'),
		array('order_type','require','订单类型不能为空', self::MUST_VALIDATE , 'regex'),
		array('invoice_type','require','发票类型不能为空', self::MUST_VALIDATE , 'regex'),
		array('return_cycle','require','返量周期不能为空', self::MUST_VALIDATE , 'regex'),
		array('settle_cycle','require','结算周期不能为空', self::MUST_VALIDATE , 'regex'),
		array('reconciliation_day','require','对账时间不能为空', self::MUST_VALIDATE , 'regex'),
		array('bill_day','require','开票时间不能为空', self::MUST_VALIDATE , 'regex'),
		array('receivables_day','require','收款时间不能为空', self::MUST_VALIDATE , 'regex'),
		array('charging_mode','require','产品计费模式不能为空', self::MUST_VALIDATE , 'regex'),
		array('price_type','require','产品价格类型不能为空', self::MUST_VALIDATE , 'regex'),
		array('price','require','产品价格不能为空', self::MUST_VALIDATE , 'regex'),

		array('package_return_type','require','返量方式不能为空', self::MUST_VALIDATE , 'regex'),
		array('package_return_account','check_package_return_backurl','返量后台账号不能为空', self::MUST_VALIDATE , 'callback'),
		array('package_return_passwd','check_package_return_backurl','返量密码不能为空', self::MUST_VALIDATE , 'callback'),
		array('package_return_email','check_package_return_email','返量密码不能为空', self::MUST_VALIDATE , 'callback'),
		array('package_size','require','安装包大小不能为空', self::MUST_VALIDATE , 'regex'),
		array('quality_requirements','require','质量要求不能为空', self::MUST_VALIDATE , 'regex'),
		array('qr_check_rule','require','核减规则不能为空', self::MUST_VALIDATE , 'regex'),

		array('order_test_quota','check_test_quota','测试时间最多30天,测试金额最多5000元', self::VALUE_VALIDATE , 'callback'),
	);

	//对接人验证信息
	public $contactRule = array(
		array('name','require','对接人名字必填！', self::MUST_VALIDATE, 'regex'),
		array('name','0,10', '对接人名字最长10个字符', self::MUST_VALIDATE , 'length'),
		array('pro_id','require','对接人供应商id不能为空！', self::VALUE_VALIDATE, 'regex'),
		array('mobile','require','对接人电话必填！', self::MUST_VALIDATE, 'regex'),
		array('mobile', '11', '对接人手机格式不正确', self::VALUE_VALIDATE,'length'),
		array('qq','require','对接人qq必填！', self::VALUE_VALIDATE, 'regex'),
		array('email', 'email', '对接人邮箱格式不正确', self::VALUE_VALIDATE), //邮箱格式不正确

	);

	protected $_auto = array(
		array('contract_s_duration', 'defaultDate',  self::MODEL_BOTH, 'function'),
		array('contract_e_duration', 'defaultDate',  self::MODEL_BOTH, 'function'),
		array('add_time', 'date', self::MODEL_INSERT,'function',array('Y-m-d H:i:s')), //添加时间
	);


	//产品合作状态为停推提示所有计费标识已收回。
	public function check_cooperate_state_stop($data) {
		$d_p=$this->where("id=".I('post.id', 0))->find();
		if($d_p['bl_id']==2)return true;
		$proId= I('param.id', 0, 'intval');
		if ($proId > 0) {
			if ($data == 3) {
				$existNum = M()->query("SELECT COUNT(*) AS num FROM `boss_charging_logo` AS cl JOIN boss_charging_logo_assign AS cla ON cl.id=cla.`cl_id` 
						WHERE cl.prot_id={$proId} AND cla.`status`=1");
				$existNum = $existNum[0]['num'];
				if ($existNum > 0) {
					return false;
				}else{
					//停推时修改该产品下的所有的计费标识详细信息状态为已停止状态 tgd 20170329
					$this->stopPushModifyChargdetailStatus($proId);
				}
			}
		}
		return true;
	}

	/**
	* 停推时修改该产品下的所有的计费标识详细信息状态为已停止状态 tgd 0329
	* @return [type] [description]
	*/
	private function stopPushModifyChargdetailStatus($proId){
		$cList = M("charging_logo")->field("id")->where(array("prot_id"=>$proId))->select();
		if($cList){
			foreach ($cList as $k => $v) {
				$sdata["status"] = 0;
				$row = M("charging_logo_assign")->where(array("cl_id"=>$v["id"]))->save($sdata);
			}
		}
	}


	public function checkCtractData($data, $args) {
		$coState = I('param.cooperate_state');
		if ($coState == 1 && !empty($args)) {  //正式上量 合同OA流水号或网签 不能为空
			$num    =  I('param.contract_num');
			$weinfo =  I('param.contract_webinfo');
       	    if (empty($weinfo) && empty($num)) {
				return false;
            }

		} else if ($coState == 1 && empty($data)) { //正式上量
			return false;
		}

		return true;
	}


	public function check_test_quota($data) {
		$cs = I('param.cooperate_state'); //合作类型
		$tType = I('param.order_test_type'); //测试指标
		if($cs == 2 && !is_numeric($data)) {
			return false;
		} else if ($cs == 2 && $tType == 1 && $data>30) { //测试时间
			return false;
		} else if ($cs == 2 && $tType == 3 && $data>5000) { //测试金额
			return false;
		}
		return true;
	}

	public function check_package_return_email($data){ //返量邮箱
		$type = I('param.package_return_type');
		if($type == 2 && empty($data)) {
			return false;
		}
		return true;
	}

	public function check_package_return_backurl($data){ //返量后台地址 账号密码
		$type = I('param.package_return_type');
		if($type == 1 && empty($data)) {
			return false;
		}
		return true;
	}

	public function getdata($where){
		$M=M('product');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where){
		$M=M('product');
		$res=$M->where($where)->find();
		return $res;
	}
	public function getbankdataforcom($id){
		return M('product')->field('d.name as jszt,e.real_name as sealname')->join('a join boss_business_line b on a.bl_id=b.id join boss_company_bankaccount c on b.sb_id=c.sb_id join boss_data_dic d on b.sb_id=d.id join boss_user e on a.saler_id=e.id')->where("a.id=".$id)->find();
	}

	/**
	 * 产品列表
	 * @param $where
	 * @return array|mixed
	 */
	public function getList($where, $field=true, $page, $count) {
		$page = empty($page) ? $_GET['p'] : $page;
		$count = empty($count) ? C('LIST_ROWS') : $count;
		$proList = $this->field($field)->where($where)->order('id desc')->page($page,$count)->select();
		$this->totalPage = $this->where($where)->count();

		//没有数据
		if ($this->totalPage == 0) {
			return array();
		}
		return $proList;

	}

	/**
	 * 获取产品计划业务线关联业务线名称
	 */
	public function getPlaneBlAssoName($proId) {
		$tabpBl = $this->tablePrefix . self::TAB_PLANE_BL;
		$tabBL = $this->tablePrefix . 'business_line';
		return $this->query("SELECT a.id,a.pro_id,a.bl_type,a.bl_id,b.name FROM {$tabpBl} AS a JOIN {$tabBL} as b ON a.bl_id=b.id WHERE a.pro_id={$proId}");
	}


	public function generalCode($id) {
		return 'CP' . str_pad(intval($id), 7, 0, STR_PAD_LEFT);
	}

	/*
	 * 产品可视化报表 start
	 * */
	//产品分析table数据
	public function productTable(){
		$where =' 1=1';
		if(I('get.pl_id')){//条件查询
			$where .= ' and bl_id='.I('get.pl_id');
		}
		$where_item=$where;
		//数据权限
        $arr_name=array();
        $arr_name['line']=array('bl_id');
        $arr_name['user']=array('saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where.= " && $myrule_data";


		//累计产品
		$allData = $this->field('COUNT(id) as all_count')->where($where)->find();

		//有效产品
		$yxData = $this->field('COUNT(id) as yx_count')->where("cooperate_state IN (1,2) and".$where)->find();

		//当月新增测试产品
		$BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));//当月
		$EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));//当月
		$xzData = $this->field('COUNT(id) as xz_count')->where("cooperate_state=2 AND DATE_FORMAT(add_time,'%Y-%m-%d')>='".$BeginDate."' AND DATE_FORMAT(add_time,'%Y-%m-%d')<='".$EndDate."' and".$where)->find();
		$where=$where_item;
		//当月测试通过产品
		$csData = M('product_state')->field('count(*) as tg_count')->where("cooperate_state=1 AND DATE_FORMAT(add_date,'%Y-%m-%d')>='".$BeginDate."' AND DATE_FORMAT(add_date,'%Y-%m-%d')<='".$EndDate."' and".$where)->find();

		//当月测试不通过产品
		$ttData = M('product_state')->field('count(*) as btg_count')->where("cooperate_state=3 AND DATE_FORMAT(add_date,'%Y-%m-%d')>='".$BeginDate."' AND DATE_FORMAT(add_date,'%Y-%m-%d')<='".$EndDate."' and".$where)->find();

		//累计测试不通过
		$ljData = M('product_state')->field('count(*) as lj_count')->where("cooperate_state=3 and".$where)->find();

		return array('leiji'=>!empty($allData['all_count']) ? $allData['all_count'] : 0,'youxiao'=>!empty($yxData['yx_count'])? $yxData['yx_count'] : 0,'xinzeng'=>$xzData['xz_count'],'tongguo'=>!empty($csData['tg_count']) ? $csData['tg_count'] :0,'butongguo'=>!empty($ttData['btg_count']) ? $ttData['btg_count'] : 0,'leijibutongguo'=>!empty($ljData['lj_count']) ? $ljData['lj_count'] : 0);
	}

	//产品占比分析 (有日期查询)
	public function productProportion(){
		$where =' 1=1';
		if(I('get.pl_id')){//条件查询
			$where .= ' and bl_id='.I('get.pl_id');
		}
		if(I('get.start_time') && I('get.end_time')){
			$where .= " and DATE_FORMAT(add_time,'%Y-%m-%d')>='".I('get.start_time')."' and DATE_FORMAT(add_time,'%Y-%m-%d')<='".I('get.end_time')."' ";
		}

		//数据权限
        $arr_name=array();
        $arr_name['line']=array('bl_id');
        $arr_name['user']=array('saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where.= " && $myrule_data";

		$item = I('get.item',0,'intval');

		$res = array();
		switch ($item) {
			case 1:
				//合作状态占比
				$typeArr = C('OPTION.order_cooperate_state');
				$datatype = $this->where($where)->group('cooperate_state')->getField('cooperate_state,COUNT(id) AS num');
				break;
			case 2:
				//产品来源占比
				$typeArr = array(1=>'官方',2=>'代理',3=>'直客');
				$datatype = $this->where($where)->group('source_type')->getField('source_type,COUNT(id) AS num');
				break;
		}
		foreach ($typeArr as $key=>$val) {
			$res[] = array(
				'name' => $val,
				'value'=> empty($datatype[$key]) ? 0 : $datatype[$key],
			);
		}

		$ret = array(
			'item'=> $item,
			'data'=>$res
		);
		return $ret;
		/*//合作状态占比
		$ztData = $this->field('COUNT(id) as zt_count,cooperate_state')->where($where)->group('cooperate_state')->select();
		//产品来源占比
		$lyData = $this->field('COUNT(id) as ly_count,source_type')->where($where)->group('source_type')->select();

		return array('zhuangtai'=>$ztData,'laiyuan'=>$lyData);*/
	}
	//覆盖行业top10
	public function productCoverTop10(){
		$where =' 1=1';
		if(I('get.pl_id')){//条件查询
			$where .= ' and bl_id='.I('get.pl_id');
		}
		//$typeArr = C('OPTION.product_type');
		$typeArr = D('DataDic')->where('dic_type=7')->getField('id,name');
		$fgData = $this->field('COUNT(id) as fg_count,category')->where('cooperate_state IN (1,2) and'.$where)->group('category')->order('fg_count desc')->limit(10)->select();
		foreach($fgData as $key=>$val){
			$a[] = $typeArr[$val['category']];
			$b[] = (int)$val['fg_count'];
		}
		$ret = array(
			'a'=>$a,
			'b'=>$b
		);
		return $ret;
	}

	//产品可视化报表 结束
}