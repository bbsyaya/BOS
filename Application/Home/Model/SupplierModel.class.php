<?php
namespace Home\Model;
use Think\Model;
class SupplierModel extends Model {

	public $totalPage = 0;

	protected $_validate = array(
		array('name','require','供应商名称不能为空', self::MUST_VALIDATE , 'regex'),
		array('email','require','供应商邮箱不能为空', self::MUST_VALIDATE , 'regex'),
		array('email', 'email', '供应商邮箱格式不对', self::EXISTS_VALIDATE), //邮箱格式不正确
		array('email', 'unique', -1, self::MUST_VALIDATE, 'unique'),
		array('type','require','供应商类型不能为空', self::MUST_VALIDATE , 'regex'),
		//array('region','require','供应商地区不能为空', self::MUST_VALIDATE , 'regex'),
	);

	protected $_auto = array(
		array('add_time', 'date', self::MODEL_INSERT,'function',array('Y-m-d H:i:s')), //添加时间
	);

	public $contactRule = array(
		array('name','require','对接人名字必填！', self::MUST_VALIDATE, 'regex'),
		array('name','0,10', '对接人名字最长10个字符', self::MUST_VALIDATE , 'length'),
		array('address','require','联系地址不能为空！', self::VALUE_VALIDATE, 'regex'),
		/*array('mobile','require','对接人电话必填！', self::MUST_VALIDATE, 'regex'),*/
		array('mobile', '11', '对接人手机格式不正确', self::VALUE_VALIDATE,'length'),
		array('qq','require','对接人qq必填！', self::VALUE_VALIDATE, 'regex'),
		array('email', 'email', '对接人邮箱格式不正确', self::VALUE_VALIDATE), //邮箱格式不正确

	);

	public function getStrError() {
		$errCode = (int)$this->error;
		$_msg = '';
		switch ($errCode) {
			case -1:
				$_msg = '供应商邮箱已存在！';
				break;
			default:
				$_msg = $this->error;
				break;
		}
		return $_msg;
	}

	public function getdata($where){
		$M=M('supplier');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where){
		$M=M('supplier');
		$res=$M->where($where)->find();
		return $res;
	}


	public function generalCode($id) {
		return 'GYS' . str_pad(intval($id), 7, 0, STR_PAD_LEFT);
	}

	/**
	 * 供应商列表
	 * @param $where
	 * @return array|mixed
	 */
	public function getList($where) {

		$star_date = date("Y-m-d",strtotime("-3 month"));
		$end_time  = date("Y-m-d",time());

		$hz_status = trim(I("hz_status"));
		if($hz_status==1){
			$where .= " and su_cb.outdata>0";
		}
		if($hz_status==2){
			$where .= " and su_cb.outdata<=0";
		}
		$order = "order by su.id desc";
		// $supList   = $this->where($where)->order('id desc')->page($_GET['p'], C('LIST_ROWS'))->select();
		$p        = empty($_GET['p'])>0?1:trim($_GET['p']);
		if(C('LIST_ROWS')){
			$listRows = empty(C('LIST_ROWS'))?10:C('LIST_ROWS');
			$firstRow = "limit ".($p-1)*$listRows.',';
		}else{
			$listRows = '';
			$firstRow = '';
		}

		$sql = "SELECT
				su.*,
				su_cb.outdata,
				GROUP_CONCAT('|', CONCAT(b.name, b.mobile)) AS lxr 
				 FROM `boss_supplier` AS su

				LEFT JOIN 
				(
				    SELECT
				  b.out_superid AS superid,
				  SUM(
				    IF(
				      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
				      0,
				      b.out_newmoney
				    )
				  ) AS outdata
				FROM
				  boss_charging_logo a
				  LEFT JOIN boss_daydata_inandout b
				    ON b.jfid = a.id
				WHERE (
				    (
				      (b.in_status != 0 && b.in_status != 9) || b.in_status IS NULL
				    ) || (
				      (
				        b.out_status != 0 && b.out_status != 9
				      ) || b.out_status IS NULL
				    )
				  )
				  AND b.adddate >= '{$star_date}' && b.adddate <= '{$end_time}'
				  AND b.`out_superid` > 0
				GROUP BY b.out_superid
				  
				) AS su_cb ON su_cb.superid=su.id  
				left JOIN boss_supplier_contacts b   ON su.id = b.sp_id 

				{$where} GROUP BY su.id  {$order}  {$firstRow} {$listRows}";

		$model = new \Think\Model();
		$supList  = $model->query($sql);

		if(I("showsql")=="showsql023"){
			print_r($sql);exit;
		}

		// $this->totalPage = $this->where($where)->count();
		$sql_count = "SELECT
				count(1) as no
				 FROM `boss_supplier` AS su
				LEFT JOIN 
				(
				    SELECT
				  b.out_superid AS superid,
				  SUM(
				    IF(
				      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
				      0,
				      b.out_newmoney
				    )
				  ) AS outdata
				FROM
				  boss_charging_logo a
				  LEFT JOIN boss_daydata_inandout b
				    ON b.jfid = a.id
				WHERE (
				    (
				      (b.in_status != 0 && b.in_status != 9) || b.in_status IS NULL
				    ) || (
				      (
				        b.out_status != 0 && b.out_status != 9
				      ) || b.out_status IS NULL
				    )
				  )
				  AND b.adddate >= '{$star_date}' && b.adddate <= '{$end_time}'
				  AND b.`out_superid` > 0
				GROUP BY b.out_superid
				) AS su_cb ON su_cb.superid=su.id  {$where}";


		if(I("showsql")=="showsql023"){
			echo $this->getLastSql();
			print_r("<br/>");
		}
		if(I("showsql")=="showsql023"){
			print_r($sql_count);
			exit;
		}
		$list_count = $model->query($sql_count);
		unset($sql_count);
		$this->totalPage = $list_count[0]["no"];

		//没有数据
		/*if ($this->totalPage == 0) { return array(); }
		$aids      = array();
		foreach ($supList as $val) {
			$aids[]      = $val['id'];
		}
		$contactsData = M('supplier_contacts')->field('sp_id,name,mobile')->where(array('sp_id'=>array('in',$aids)))->select();
		foreach($contactsData as $val) {
			$contactArr[$val['sp_id']][] = $val['name'].'|'.$val['mobile'];
		}
		unset($contactsData);*/
		$arr=array('电脑客户端'=>'安装量','网站'=>'日独立用户数','加粉设备'=>'用户覆盖量','电脑预装'=>'安装量','移动预装'=>'安装量','网红推广'=>'粉丝量','电脑端拓展工具'=>'日活跃用户数','移动端拓展工具'=>'日活跃用户数','社群推广'=>'日活跃用户数','微博/博客'=>'用户访问量','群控'=>'微信账号量','ASO刷榜'=>'设备量','下载站'=>'日活跃用户数','竞价排名'=>'指标为空','移动应用'=>'日活跃用户数','商业WiFi'=>'用户覆盖量','公众号'=>'粉丝量','平台联盟'=>'广告展现量','应用商店'=>'日活跃用户数');
	
		foreach ($supList as $key=>$val) {
			//供应商合作状态
			$supList[$key]['hz_status']       = $val["outdata"]>0?"合作中":"暂停中";

			//$supList[$key]['contacts'] = implode(',',$contactArr[$val['id']]);
			$tag                       =json_decode(htmlspecialchars_decode($val['tag']),true);
			$str                       =$tag[0]['media_type'].'('.$arr[$tag[0]['media_type']].'):'.$tag[0]['resource_scale'];
			if(count($tag)>1)$str.=" ...";
			$supList[$key]['tag'] = $str;


		}
		return $supList;

	}


	/*
	 * 供应商可视化报表 开始
	 * */
	public function supTable(){
		$pl_id = I('get.pl_id');
		if($pl_id){
			$where = " and lineid=".$pl_id;
		}else{
			$where = " and 1=1";
		}
		//累计供应商
		$allData = $this->field('COUNT(id) as all_count')->find();

		//有效供应商(统计上月有数据的供应商)
		$start_time = date('Y-m-01', strtotime('-1 month'));//上月
		$end_time = date('Y-m-t', strtotime('-1 month'));//上月
		$yxData = $this->query("SELECT COUNT(*) AS yx_count from( SELECT superid FROM boss_daydata_out WHERE adddate >='".$start_time."' AND adddate <='".$end_time."' ".$where." GROUP BY superid) a");
		foreach($yxData as $key=>$val){
			$yx = $val['yx_count'];
		}
		//当月新增
		$BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));//当月
		$EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));//当月
		$xzData = $this->field('COUNT(id) as xz_count')->where("DATE_FORMAT(add_time,'%Y-%m-%d') >='".$BeginDate."' and DATE_FORMAT(add_time,'%Y-%m-%d') <='".$EndDate."'")->find();

		//当月暂停合作
		$s_time = date('Y-m-01', strtotime('-2 month'));//上上个月
		$e_time = date('Y-m-t', strtotime('-2 month'));//上上个月
		$ztData = M()->query("SELECT COUNT(*) AS zt_count from (SELECT superid FROM boss_daydata_out WHERE adddate >='".$s_time."' AND adddate <='".$e_time."' AND superid NOT IN (SELECT superid FROM boss_daydata_out WHERE adddate >='".$start_time."' AND adddate <='".$end_time."' GROUP BY superid) ".$where." GROUP BY superid) z");
		foreach($ztData as $key=>$val){
			$zt = $val['zt_count'];
		}
		return array('leiji'=>$allData['all_count'],'youxiao'=>$yx,'xinzeng'=>$xzData['xz_count'],'zanting'=>$zt);
	}

	//供应商占比分析
	public function supProportion(){
		$item = I('get.item',0,'intval');
		$pl_id = I('get.pl_id');
		$res = array();

		$where = '1=1';
		if($pl_id){
			$idData = M('daydata_out')->field('superid')->group('superid')->where("lineid=".$pl_id."")->select();
			$aid = "";
			foreach($idData as $key=>$val){
				if($val['superid']){
					$aid .=$val['superid'].",";
				}
			}
			$aid = rtrim($aid,",");
			$where .=" and id in ($aid)";
		}

		switch ($item) {
			case 1:
				//供应商类型占比
				$typeArr = C('OPTION.supplier_type');
				$datatype = $this->group('type')->where($where)->getField('type,COUNT(id) AS num');
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
				  superid,
				  CASE WHEN SUM(IFNULL(newmoney, money))>500000 THEN 'big2'
				   WHEN SUM(IFNULL(newmoney, money))>200000 AND SUM(IFNULL(newmoney, money)) <=500000 THEN 'big3'
				   WHEN SUM(IFNULL(newmoney, money))>100000 AND SUM(IFNULL(newmoney, money)) <=200000 THEN 'big4'
				   WHEN SUM(IFNULL(newmoney, money))<100000 THEN 'big5' END AS `level`
				FROM
				  `boss_daydata_out`
				WHERE {$whereStr}
				GROUP BY superid) AS tab GROUP BY tab.level
EOF;
				$ress = M()->query($sql);
				$typeArr = array('big2'=>'50w以上','big3'=>'20w~50w','big4'=>'10w~20w','big5'=>'10w以下',);
				foreach($ress as $val) {
					$datatype[$val['level']] = $val['num'];
				}
				break;
			case 3:
				$datatype = $this->group('region')->where($where)->getField('region,COUNT(id) AS num');
				$ids = implode(',',array_keys($datatype));
				$typeArr = D('Region')->where("id IN ({$ids})")->getField("id,name");
				$max_num=0;
				break;
		}
		foreach ($typeArr as $key=>$val) {
			if($item==3 && $datatype[$key]>$max_num)$max_num=$datatype[$key];
			if($item==3){
				$val_c = mb_substr($val,0,-1,'utf-8');
			}else{
				$val_c = $val;
			}
			$res[] = array(
				'name' => $val_c,
				'value'=> empty($datatype[$key]) ? 0 : $datatype[$key],
			);
		}

		$ret = array(
			'item'=> $item,
			'data'=>$res
		);
		if($item==3)$ret['max']=$max_num;
		return $ret;
	}

	/*获取供应商id*/
	public function getSupId(){
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
			$whereStr .=" and adddate >='".$start_time."' AND adddate <='".$end_time."'";
		}

		if($pl_id > 0) {
			$whereStr .= ' AND lineid='.$pl_id;
		}

		if($type== 0){
			$group =' superid HAVING SUM(newmoney)>500000';
		}elseif($type== 1){
			$group =' superid HAVING SUM(newmoney)>200000 AND SUM(newmoney) <=500000';
		}elseif($type== 2){
			$group =' superid HAVING SUM(newmoney)>100000 AND SUM(newmoney) <=200000';
		}elseif($type== 3){
			$group =' superid HAVING SUM(newmoney)<100000';
		}
		$aciData = M('daydata_out')->field('superid')->where($whereStr)->group($group)->select();
		$advid = "";
		foreach($aciData as $key=>$val){
			if($val['superid']){
				$advid .= $val['superid'].",";
			}
		}
		$advid = rtrim($advid,",");

		return array('supId'=>$advid);
	}

	//供应商趋势分析
	public function supTrend(){
		//新增
		$pl_id = I('get.pl_id');

		$xzData = $this->query("SELECT COUNT(id) AS adv_count,DATE_FORMAT(add_time,'%m') AS adv_month FROM boss_supplier WHERE add_time !='' AND DATE_FORMAT(add_time,'%Y-%m-%d')>='".date('Y-01-01')."' AND DATE_FORMAT(add_time,'%Y-%m-%d') <='".date('Y-m-d')."' GROUP BY DATE_FORMAT(add_time,'%Y-%m')");
		foreach ($xzData as $key=>$val) {
			if($val['adv_count']){
				$a[] = (int)$val['adv_count'];
			}else{
				$a[] = 0;
			}
		}

		//暂停
		$re_mon = array();
		$DataList = array();
		$now_m = date('m');//当前月份
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

			if($pl_id){
				$where = " and lineid=".$pl_id;
			}else{
				$where = " and 1=1";
			}
			$dayList = M()->query("SELECT COUNT(*) AS zt_count from(SELECT superid FROM boss_daydata_out WHERE adddate >='".$BeginDate."' AND adddate <='".$EndDate."' AND superid NOT IN (SELECT superid FROM boss_daydata_out WHERE adddate >='".$s_BeginDate."' AND adddate <='".$s_EndDate."' GROUP BY superid) ".$where." GROUP BY superid) b");//统计当年1月至当前月 暂停广告主个数
			//echo M()->getLastSql();exit;
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
		//return array('xinzeng'=>$xzData,'zanting'=>$DataList);
	}

	//供应商区域分布
	public function supRegion(){
		$qyData = $this->field('COUNT(a.id) AS fb_count,b.`name`')->join('a join boss_region b ON a.region=b.id')->group('a.region')->select();
		return $qyData;
	}
	/*供应商可视化报表结束
	 * */
}