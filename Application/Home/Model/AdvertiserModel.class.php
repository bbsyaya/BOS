<?php
namespace Home\Model;
use Think\Model;
class AdvertiserModel extends Model {

	public $totalPage = 0;

	protected $_validate = array(

		array('ad_type','require','类型必须选择', self::MUST_VALIDATE , 'regex'),
		array('rating_level','require','实力评级必须选择', self::MUST_VALIDATE , 'regex'),
		array('name','require','广告主名称必填', self::MUST_VALIDATE , 'regex'),
		array('name','0,40', '广告主名称最长20个字符', self::MUST_VALIDATE , 'length'),
		array('name', 'unique', '广告主名称已存在！',self::MUST_VALIDATE,'unique'),
		/*array('email','require','广告主邮箱必填', self::MUST_VALIDATE , 'regex'),
		array('email', 'email', '广告主邮箱格式不正确', self::EXISTS_VALIDATE), //邮箱格式不正确
		array('email', 'unique', '邮箱已存在！',self::MUST_VALIDATE,'unique'),*/
		array('province_id','require','办公地区必填', self::MUST_VALIDATE , 'regex'),
		array('address','require','广告主地址必填', self::MUST_VALIDATE , 'regex'),
		array('contract_num','0,10', 'oa合同流水号最多10个字符', self::MUST_VALIDATE , 'length'),
		array('province_id','require','办公地区必填', self::MUST_VALIDATE , 'regex'),

		/*array('object_type','require','财务对象必填', self::MUST_VALIDATE , 'regex'),
		array('account_name','require','账户名称必填', self::MUST_VALIDATE , 'regex'),
		array('opening_bank','require','开户行必填', self::MUST_VALIDATE , 'regex'),
		array('bank_no','require','银行账号必填', self::MUST_VALIDATE , 'regex'),
		array('invoice_type','require','开票类型必填', self::MUST_VALIDATE , 'regex'),
		array('invoice_remark','require','发票内容必填', self::MUST_VALIDATE , 'regex'),
		array('taxpayer_num','require','纳税人识别号必填', self::MUST_VALIDATE , 'regex'),
		array('reg_address','require','注册地址必填', self::MUST_VALIDATE , 'regex'),
		array('reg_mobile','require','注册电话必填', self::MUST_VALIDATE , 'regex'),*/

	);

	//财务收件人验证信息
	public $receiverRule = array(
		array('name','require','财务收件人名字必填！', self::MUST_VALIDATE, 'regex'),
		array('name','0,50', '财务收件人名字最长50个字符', self::MUST_VALIDATE , 'length'),
		array('mobile','require','财务收件人电话必填！', self::MUST_VALIDATE, 'regex'),
		//array('mobile', '11', '财务收件人手机格式不正确', self::EXISTS_VALIDATE,'length'),
		array('address','0,200','财务收件人地址最长200个字符', self::EXISTS_VALIDATE , 'length'),
	);

	protected $_auto = array(
		array('status', '1', self::MODEL_BOTH),
		array('add_time', 'date', self::MODEL_INSERT,'function',array('Y-m-d H:i:s')), //添加时间
		array('established_time', 'defaultDate',  self::MODEL_BOTH, 'function'),
		array('cooperation_time', 'defaultDate',  self::MODEL_BOTH, 'function'),
	);

	/**
	 * 广告主列表
	 * @param $where
	 * @return array|mixed
	 */
	public function getList($where) {

		$adList = array();

		// 临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"]);
		if($isRead){
			//先去查询所有广告主下的产品属于当前用的，然后再根据广告主id再次查询
			$adList = $this->field('a.id')->join("a left join (select category,ad_id,cooperate_state from boss_product group by category,ad_id) b on a.id=b.ad_id left join boss_region c on c.id=a.province_id")->where($where)->group('a.id')->order('a.id desc')->select();

			$adverids = $this->getAdverids($adList);
			$adverids = $this->getCurrentUsersAderList($adverids);
			$adverids = empty($adverids)?0:$adverids;
			$where[]  = " a.id in ({$adverids})";
			$adList   = $this->field('a.*,group_concat(b.category) as tag2,c.name as province_id')->join("a left join (select category,ad_id,cooperate_state from boss_product group by category,ad_id) b on a.id=b.ad_id left join boss_region c on c.id=a.province_id")->where($where)->group('a.id')->order('a.id desc')->page($_GET['p'],C('LIST_ROWS'))->select();
		}else{
			$adList = $this->field('a.*,group_concat(b.category) as tag2,c.name as province_id')->join("a left join (select category,ad_id,cooperate_state from boss_product group by category,ad_id) b on a.id=b.ad_id left join boss_region c on c.id=a.province_id")->where($where)->group('a.id')->order('a.id desc')->page($_GET['p'],C('LIST_ROWS'))->select();
		}
		
		if(I("showsql")=="showsql023"){
			print_r($this->getLastSql());
			print_r("<br>");
		}

		//$sql = "";
		//$count=$this->query("select count(*) as num from (select a.id from boss_advertiser a left join boss_product b on a.id=b.ad_id where ".implode(' && ', $where)." group by a.id)z");

		$subQuery = $this
			->field('a.id')
			->join("a left join (select category,ad_id,cooperate_state from boss_product group by category,ad_id) b on a.id=b.ad_id")->where($where)
			->buildSql();

		if(I("showsql")=="showsql023"){
			print_r($this->getLastSql());exit;
		}


		//$this->totalPage = $count[0]['num'];
		$this->totalPage = $this->table($subQuery.' z')->where()->count();

		//地区
		$adType     = C('OPTION.ad_type');
		$isInternal = C('OPTION.is_internal');

		//获取广告主id
		$ad_ids       = $this->getAdverids($adList);
		$productsList = $this->getAderHzStatus($ad_ids);
		foreach ($adList as $key=>$val) {
			//对接人及电话
			$contactsData = M('advertiser_contacts')->field('ad_id,name,mobile')->where("ad_id=".$val['id'])->find();

			//$adList[$key]['province_id'] = $regiondata['name'];
			$adList[$key]['contacts']    = $contactsData['name'].'|'.$contactsData['mobile'];
			$adList[$key]['ad_type']     = $adType[$val['ad_type']];//广告主类型
			$adList[$key]['is_internal'] = $isInternal[$val['is_internal']];//广告主归属

			//判断状态--该广告主下的产品只要有一款是正式上量，测试都表示合作中；
			$adList[$key]['hz_status'] = $productsList[$val["id"]]["no"]>0?"合作中":"已暂停";
		}
		return $adList;
	}

	/**
	 * 获取属于当前用户的广告主
	 * @param  [type] $adverid [description]
	 * @return [type]          [description]
	 */
	function getCurrentUsersAderList($adverid){
		$adverid     = empty($adverid)?0:$adverid;
		$uid = empty($_SESSION["userinfo"]["uid"])?0:$_SESSION["userinfo"]["uid"];
		$sql = "SELECT 
				  ad_id 
				FROM
				  `boss_product` 
				WHERE ad_id IN ({$adverid}) 
				  AND saler_id = {$uid} ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list) return false;
		$adverids = "";
		foreach ($list as $k => $v) {
			$adverids .= $v["ad_id"].",";
		}
		if($adverids){
			$adverids = substr($adverids, 0,strlen($adverids)-1);
		}
		unset($list);
		return $adverids;
	}

	function getAdverids($adlist){
		$ad_ids = "";
		if(!$adlist){ return false; }
		foreach ($adlist as $k => $v) {
			$ad_ids .= $v["id"].",";
		}
		if($ad_ids){
			$ad_ids = substr($ad_ids, 0,strlen($ad_ids)-1);
		}
		return $ad_ids;
	}

	/**
	 * 获取广告合作状态
	 * @param  [type] $adverid [description]
	 * @return [type]          [description]
	 */
	function getAderHzStatus($adverid){
		$adverid     = empty($adverid)?0:$adverid;
		$sql = "SELECT 
				  COUNT(1) AS NO,
				  ad_id 
				FROM
				  `boss_product` 
				WHERE ad_id IN ({$adverid}) 
				  AND cooperate_state IN (1, 2)
				GROUP BY ad_id ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list) return false;
		$newList = array();
		foreach ($list as $k => $v) {
			$newList[$v['ad_id']] = $v;
		}
		return $newList;
	}

	/**
	 * [getProductsByadvers description]
	 * @param  [type] $adver_ids [description]
	 * @return [type]            [description]
	 */
	public function getProductsByadvers($adver_ids){
		$list = array();
		if(!$adver_ids) return false;
		$list = M("product")->field("id,cooperate_state,ad_id")->where(array("ad_id"=>array("in",$adver_ids)))->select();
		unset($adver_ids);
		if(!$list) return false;

		foreach ($list as $k => $v) {
			$list[$v["ad_id"]]["no"] = $v;
		}
		return $list;
	}


	public function getById($id) {

		$info = $this->find($id);
		//$info['receiver'] = $this->decodeReceiver($info['receiver']);
		return $info;
	}


	/**
	 * 保存财务接受人 ------------弃用
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


	//------------弃用
	public function encodeReceiver($data) {
		foreach($data as $val) {
			if ($this->validate($this->receiverRule)->create($val)===false) {
				return false;
			}
		}
		return serialize($data);
	}


	//------------弃用
	public function decodeReceiver($receiver) {
		$receiverArr = unserialize(htmlspecialchars_decode($receiver));
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

	public function generalCode($id) {
		return 'GG' . str_pad(intval($id), 7, 0, STR_PAD_LEFT);
	}


	public function getdata($where){
		$M=M('advertiser');
		$res=$M->where($where)->select();
		return $res;
	}

	public function getonedata($where){
		$M=M('advertiser');
		$res=$M->where($where)->find();
		return $res;
	}

	//广告主可视化报表 start
	public function getAll(){
		$data = array();
		$allData = $this->field('COUNT(id) as all_count')->select();
		foreach($allData as $key=>$val){
			$data[$key]['all'] = $val['all_count'];
		}
		$start_time = date('Y-m-01', strtotime('-1 month'));//上月
		$end_time = date('Y-m-t', strtotime('-1 month'));//上月

		$s_time = date('Y-m-01', strtotime('-2 month'));//上上个月
		$e_time = date('Y-m-t', strtotime('-2 month'));//上上个月

		$yxData = M()->query("SELECT COUNT(*) AS yx_count from( SELECT adverid FROM boss_daydata WHERE adddate >='".$start_time."' AND adddate <='".$end_time."' GROUP BY adverid) a");
		foreach($yxData as $key=>$val){
			$data[$key]['yx'] = $val['yx_count'];
		}

		$BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));//当月
		$EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));//当月
		$xzData = $this->field('COUNT(id) as xz_count')->where("DATE_FORMAT(add_time,'%Y-%m-%d') >='".$BeginDate."' and DATE_FORMAT(add_time,'%Y-%m-%d') <='".$EndDate."'")->select();
		foreach($xzData as $key=>$val){
			$data[$key]['xz'] = $val['xz_count'];
		}

		$ztData = M()->query("SELECT COUNT(*) AS zt_count from(SELECT adverid FROM boss_daydata WHERE adddate >='".$s_time."' AND adddate <='".$e_time."' AND adverid NOT IN (SELECT adverid FROM boss_daydata WHERE adddate >='".$start_time."' AND adddate <='".$end_time."' GROUP BY adverid) GROUP BY adverid) b");
		foreach($ztData as $key=>$val){
			$data[$key]['zt'] = $val['zt_count'];
		}

		return $data;
	}

	//占比分析
	public function proportion(){

		$item = I('get.item',0,'intval');
		$model = M('Advertiser');
		$pl_id = I('get.pl_id');
		$where = '1=1';
		if($pl_id){
			$idData = M('daydata')->field('adverid')->group('adverid')->where("lineid=".$pl_id."")->select();
			$aid = "";
			foreach($idData as $key=>$val){
				if($val['adverid']){
					$aid .=$val['adverid'].",";
				}
			}
			$aid = rtrim($aid,",");
			$where .=" and id in ($aid)";
		}


		$res = array();
		$fields = '';
		switch ($item) {
			case 1:

				//广告主等级占比
				$typeArr = C('OPTION.ad_grade');
				$datatype = $model->group('ad_grade')->where($where)->getField('ad_grade,COUNT(id) AS num');
				break;
			case 2:
				$s_time = I('get.start_time');
				$e_time = I('get.end_time');

				$start_time = date('Y-m-01', strtotime('-1 month'));//上月
				$end_time = date('Y-m-t', strtotime('-1 month'));//上月
				//$getBl = I('get.bl', 0, 'intval');
				if($s_time && $e_time){
					$whereStr ="adddate >='".$s_time."' AND adddate <='".$e_time."'";
				}else{
					$whereStr ="adddate >='".$start_time."' AND adddate <='".$end_time."'";
				}

				if($pl_id > 0) {
					$whereStr .= ' AND lineid='.$pl_id;
				}
				//大客户占比
				$sql = <<<EOF
				SELECT tab.level,COUNT(tab.level) AS num FROM
				(SELECT
				  adverid,
				  CASE WHEN SUM(IFNULL(newmoney, money))>500000 THEN 'big2'
				   WHEN SUM(IFNULL(newmoney, money))>200000 AND SUM(IFNULL(newmoney, money)) <=500000 THEN 'big3'
				   WHEN SUM(IFNULL(newmoney, money))>100000 AND SUM(IFNULL(newmoney, money)) <=200000 THEN 'big4'
				   WHEN SUM(IFNULL(newmoney, money))<100000 THEN 'big5' END AS `level`
				FROM
				  `boss_daydata`
				WHERE {$whereStr}
				GROUP BY adverid) AS tab GROUP BY tab.level
EOF;
				$ress = M()->query($sql);
				$typeArr = array('big2'=>'50w以上','big3'=>'20w~50w','big4'=>'10w~20w','big5'=>'10w以下',);
				foreach($ress as $val) {
					$datatype[$val['level']] = $val['num'];
				}
				break;
			case 3:
				//广告主行业分布 (个数)
				$typeArr = M('data_dic')->where('dic_type=7')->getField('id,name');
				$datatype = M('product')->group('category')->getField('category,COUNT(id) AS num');
				break;
			case 4:

				//区域分布
				$datatype = $model->group('province_id')->where($where)->getField('province_id,COUNT(id) AS num');
				$ids = implode(',',array_keys($datatype));
				$typeArr = D('Region')->where("id IN ({$ids})")->getField("id,name");
				$max_num=0;
				break;
		}
		$fields = array_values($typeArr);
		foreach ($typeArr as $key=>$val) {
			if($item==4 && $datatype[$key]>$max_num)$max_num=$datatype[$key];
			if($item==4){
				$val_c = mb_substr($val,0,-1,'utf-8');
			}else{
				$val_c = $val;
			}
			$res[] = array(
				'name' => $val_c,//.':'.empty($datatype[$key]) ? 0 : $datatype[$key]
				'value'=> empty($datatype[$key]) ? 0 : (int)$datatype[$key],
			);
		}

		$ret = array(
			'item'=> $item,
			'fields'=>$fields,
			'data'=>$res
		);
		if($item==4)$ret['max']=$max_num;
		return $ret;
	}

	//获取广告主id
	function getAciId(){
		$type = I('get.type');
		$pl_id = I('get.pl_id');
		$s_time = I('get.s_time');
		$e_time = I('get.e_time');
		$start_time = date('Y-m-01', strtotime('-1 month'));//上月
		$end_time = date('Y-m-t', strtotime('-1 month'));//上月
		$whereStr = "1=1";
		if($s_time && $e_time){
			$whereStr .=" AND adddate >='".$s_time."' AND adddate <='".$e_time."'";
		}else{
			$whereStr .=" AND adddate >='".$start_time."' AND adddate <='".$end_time."'";
		}

		if($pl_id > 0) {
			$whereStr .= ' AND lineid='.$pl_id;
		}

		if($type== 0){
			$group =' adverid HAVING SUM(newmoney)>500000';
		}elseif($type== 1){
			$group =' adverid HAVING SUM(newmoney)>200000 AND SUM(newmoney) <=500000';
		}elseif($type== 2){
			$group =' adverid HAVING SUM(newmoney)>100000 AND SUM(newmoney) <=200000';
		}elseif($type== 3){
			$group =' adverid HAVING SUM(newmoney)<100000';
		}
		$aciData = M('daydata')->field('adverid')->where($whereStr)->group($group)->select();
		$advid = "";
		//echo M('daydata')->getLastSql();exit;
		foreach($aciData as $key=>$val){
			if($val['adverid']){
				$advid .= $val['adverid'].",";
			}
		}
		$advid = rtrim($advid,",");

		return array('aciId'=>$advid);
	}

	//趋势分析
	public function trend(){
		//新增
		$pl_id = I('get.pl_id');
		$xzData = $this->query("SELECT COUNT(id) AS adv_count,DATE_FORMAT(add_time,'%m') AS adv_month FROM boss_advertiser WHERE add_time !='' AND DATE_FORMAT(add_time,'%Y-%m-%d')>='".date('Y-01-01')."' AND DATE_FORMAT(add_time,'%Y-%m-%d') <='".date('Y-m-d')."' GROUP BY DATE_FORMAT(add_time,'%Y-%m')");
		foreach ($xzData as $key=>$val) {
			if($val['adv_count']){
				$a[] = (int)$val['adv_count'];
			}else{
				$a[] = 0;
			}
		}

		//暂停
		$DataList = array();
		$now_m = date('m');//当前月份
		$re_mon = array();
		for($i=1;$i<=$now_m;$i++){
			if($i<10){
				$mon = '0'.$i;
			}else{
				$mon = $i;
			}
			$re_mon[] = $mon.'月';
			$start_time = date("Y-m-d", strtotime("-2 months", strtotime(date('Y-'.$mon.'-d'))));//上上个月
			$end_time = date("Y-m-d", strtotime("-1 months", strtotime(date('Y-'.$mon.'-d'))));//上个月

			$BeginDate = date('Y-m-01', strtotime($start_time));//上上个月第一天
			$EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));//上上个月最后一天

			$s_BeginDate = date('Y-m-01', strtotime($end_time));//上个月第一天
			$s_EndDate = date('Y-m-d', strtotime("$s_BeginDate +1 month -1 day"));//上个月最后一天
			$where = " and 1=1";
			if($pl_id){
				$where .= " and lineid=".$pl_id."";
			}
			$dayList = M()->query("SELECT COUNT(*) AS zt_count from(SELECT adverid FROM boss_daydata WHERE adddate >='".$BeginDate."' AND adddate <='".$EndDate."' AND adverid NOT IN (SELECT adverid FROM boss_daydata WHERE adddate >='".$s_BeginDate."' AND adddate <='".$s_EndDate."' GROUP BY adverid) ".$where." GROUP BY adverid) b");//统计当年1月至当前月 暂停广告主个数
			if($dayList){
				foreach($dayList as $val){
					$DataList[] = (int)$val['zt_count'];
				}
			}else{
				$DataList[] = 0;
			}
		}
		$ret = array(
			'a'=>$a,
			'b'=>$DataList,
			'mon'=>$re_mon
		);
		return $ret;
	}

	//广告主区域分布
	public function region(){
		$item = I('get.item',0,'intval');
		/*$qyData = $this->field('COUNT(a.id) AS fb_count,b.`name`')->join('a join boss_region b ON a.province_id=b.id')->group('a.province_id')->select();*/
		$datatype = $this->group('province_id')->getField('province_id,COUNT(id) AS num');
		$ids = implode(',',array_keys($datatype));
		$typeArr = D('Region')->where("id IN ({$ids})")->getField("id,name");
		$fields = array_values($typeArr);
		foreach ($typeArr as $key=>$val) {
			$res[] = array(
				'name' => $val,
				'value'=> empty($datatype[$key]) ? 0 : $datatype[$key]
			);
		}

		$ret = array(
			'item'=> $item,
			'fields'=>$fields,
			'data'=>$res
		);
		return $ret;
	}

	//覆盖行业Top10
	public function coverTop10(){
		//$typeArr = C('OPTION.product_type');
		$typeArr = D('DataDic')->where('dic_type=7')->getField('id,name');
		$fgData = M()->query("select count(*) AS coun,category from (select category from boss_product group by ad_id,category)z group by category ORDER BY coun DESC");
		foreach($fgData as $key=>$val){
			$a[] = $typeArr[$val['category']];
			$b[] = (int)$val['coun'];
		}
		$ret = array(
			'a'=>$a,
			'b'=>$b
		);
		return $ret;
	}
	//广告主可视化报表 end
}