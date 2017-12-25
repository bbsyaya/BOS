<?php
namespace Home\Model;
use Think\Model;
use Common\Service;
class ChargingLogoModel extends Model {

	protected $tableName = 'charging_logo';

	public $totalPage = 0;

	protected $_validate = array(
		array('ad_id','require','广告主id不能为空', self::MUST_VALIDATE , 'regex'),
		array('prot_id','require','产品id不能为空', self::MUST_VALIDATE , 'regex'),
		array('name','require','计费标识名称不能为空', self::MUST_VALIDATE , 'regex'),

		array('url','require','计费标识链接地址不能为空', self::EXISTS_VALIDATE , 'regex'),
		array('price_type','require','计费标识价格类型不能为空', self::EXISTS_VALIDATE , 'regex'),
		array('price','require','计费标识价格不能为空', self::EXISTS_VALIDATE , 'regex'),
		array('charging_mode','require','计费标识不能为空', self::EXISTS_VALIDATE , 'regex'),
		/*array('back_url','checkByPackageReturnType','计费标识后台地址不能为空', self::EXISTS_VALIDATE , 'callback'),*/ //2017.02.08
		array('account','checkByPackageReturnType','计费标识账号不能为空', self::EXISTS_VALIDATE , 'callback'),
		array('password','checkByPackageReturnType','计费标识密码不能为空', self::EXISTS_VALIDATE , 'callback'),

		//array('package_return_account','check_package_return_backurl','返量后台账号不能为空', self::MUST_VALIDATE , 'callback'),
	);

	protected $_auto = array(
		array('add_time', 'date', self::MODEL_INSERT, 'function', array('Y-m-d H:i:s')), //添加时间
	);

	public function checkByPackageReturnType($data) {
		if(I('param.package_return_type') == 1 && empty($data)) { //返量方式为官方后台, 相应字段数据不能为空
			return false;
		} else {
			return true;
		}

	}


	public function getlistdata($where2){
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$alljfdata=M('ChargingLogo')->field('b.*,c.name as comname,d.name as advname,e.real_name,f.promotion_stime,f.promotion_etime,f.status as fstatus')->join('b join boss_product c on b.prot_id=c.id join boss_advertiser d on c.ad_id=d.id join boss_user e on c.saler_id=e.id join boss_charging_logo_assign f on f.cl_id=b.id')->where($where2)->group('b.id')->limit($str.',10')->select();
		//echo M('ChargingLogo')->getLastSql();exit;
		return $alljfdata;
		//return $Daydata->field('a.*,b.name as jfname,b.unit_price as price,b.charging_mode as jftype')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.ad_id=c.id join boss_advertiser d on c.ad_id=d.id')->where($where)->select();
	}

	public function getOutlistdata($where2){
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$alljfdata=M('ChargingLogo')->field('b.id,b.code,b.name,c.name as comname,d.name as advname,e.real_name,f.promotion_stime,f.promotion_etime,f.status as fstatus,f.promotion_price_type as price_type,f.promotion_price as price,f.charging_mode')->join('b join boss_product c on b.prot_id=c.id join boss_charging_logo_assign f on f.cl_id=b.id join boss_supplier d on f.sup_id=d.id join boss_user e on f.business_uid=e.id')->where($where2)->group('b.id')->limit($str.',10')->select();
		return $alljfdata;
		//return $Daydata->field('a.*,b.name as jfname,b.unit_price as price,b.charging_mode as jftype')->join('a join boss_charging_logo b on a.jfid=b.id join boss_product c on b.ad_id=c.id join boss_advertiser d on c.ad_id=d.id')->where($where)->select();
	}

	public function getdata($where){
		$M=M('charging_logo');
		$res=$M->where($where)->select();
		return $res;
	}
	public function getonedata($where){
		$M=M('charging_logo');
		$res=$M->where($where)->find();
		return $res;
	}
	public function getdatainfoforjfid($id){//根据计费标识ID查相关产品、广告主、销售数据
		return $this->field('a.prot_id,b.saler_id,b.bl_id,b.sb_id,b.ad_id')->join('a join boss_product b on a.prot_id=b.id')->where('a.id='.$id)->find();
	}
	public function getdataoutjfid($id){//根据计费标识ID查相关产品、供应商、商务数据
		//return $this->field('c.*')->join('a join boss_product b on a.prot_id=b.id join boss_charging_logo_assign c on c.cl_id=a.id')->where('c.status=1 && a.id='.$id)->find();
		return $this->field('c.*')->join('a join boss_product b on a.prot_id=b.id join boss_charging_logo_assign c on c.cl_id=a.id')->where('c.status=1 && a.id='.$id)->find();
	}


	/**
	 * 计费标识列表
	 * @param $where
	 * @return array|mixed
	 */
	public function getList($where) {

		$where[] = '1=1';
		$getProId = I('get.prot_id','');
		if ($getProId) {
			$where[] = 'prot_id ='.$getProId;
		}
		$getAdName = I('get.ad_name','');
		if ($getAdName) {
			$adid = M('advertiser')->where("name Like '%{$getAdName}%'")->getField('id',true);
			$adid = empty($adid) ? array(0) : $adid;
			$where[] = "ad_id in (".implode(',',$adid).")";
		}
		$getOutBlId = I('get.out_bl_id',''); //分配业务线
		$getProName = I('get.pro_name','','urldecode');
		if ($getProName || $getOutBlId) {
			if($getOutBlId)$w=" && bl_id=$getOutBlId";
			else $w='';
			$proId = M('product')->where("name Like '%{$getProName}%'$w")->getField('id',true);
			$proId = empty($proId) ? array(0) : $proId;
			$where[] = "prot_id in (".implode(',',$proId).")";
		}
		$getJfName = I('get.jf_name','','urldecode');
		if ($getJfName) {
			$where[] = "name like '%$getJfName%'";
		}
		/*$getSaler = I('get.saler','');
		if ($getSaler) {
			$where['salerid'] = $getSaler;
		}*/

		$clList = $this->where($where)
			->alias('a')
			->field('
			a.id,
			a.code,
			a.price,
			a.name,
			a.ad_id,
			a.prot_id,
			a.is_check,
			a.status AS ban_status'
			)
			->order('a.id desc')
			->group('a.id')
			->page($_GET['p'],C('LIST_ROWS'))
			->select();
		/*,
			IFNULL (b.status,2) AS `status`*/
		/*->join(" LEFT JOIN boss_charging_logo_assign b ON a.id=b.cl_id")*/
		foreach($clList as $key=>$val){

			if($val['id']){
				$wheres = 'cl_id='.$val['id'].' ';
				$getSupName = I('get.sup_name','');
				if ($getSupName) {
					$supId = M('Supplier')->where("name Like '%{$getSupName}%'")->getField('id',true);
					$supId = empty($supId) ? array(0) : $supId;
					$wheres .= " and sup_id in (".implode(',',$supId).")";
				}
				$getstatus = I('get.status','');
				if ($getstatus!='') {
					$wheres .= " and status = ".$getstatus;
				}
				$againList = M('charging_logo_assign')->field('id,sup_id,status')->where($wheres)->order('id desc')->limit(1)->find();
				if($againList){
					$clList[$key]['sup_id'] = $againList['sup_id'];
					$clList[$key]['status'] = $againList['status'];
					$clList[$key]['bid'] = $againList['id'];
				}

			}
		}

		//start
		$getstatus = I('get.status','');
		if ($getstatus!='') {
			$where['b.status'] = $getstatus;
		}
		$ca_ch = $this->where($where)
		->alias('a')
		->field('
		a.id,a.code,
		a.price,
		a.name,
		a.ad_id,
		a.prot_id,
		a.is_check,
		a.status AS ban_status'
		)
		->join(" LEFT JOIN boss_charging_logo_assign b ON a.id=b.cl_id")
		->order('a.id desc')
		->group('a.id')
		->buildSql();
		$this->totalPage = $this->table($ca_ch.' bc')->where()->count();
		//end 2017.02.08

			/*$p=I('get.p',1);
			if(count($where)>0)$where = implode(' && ',$where);
			else $where='1=1';
		$clList=$this->query("select * from (select a.id,a.code,a.price,a.name,a.ad_id,a.prot_id,a.is_check,b.sup_id,b.id AS bid,			a.status AS ban_status,IFNULL (b.status,2) AS `status` from boss_charging_logo a LEFT JOIN boss_charging_logo_assign b ON a.id=b.cl_id group by a.id)z where  $where order by id desc limit ".(($p-1)*10).",10");
		$count=$this->query("select COUNT(*) AS tp_count from (select a.id,a.code,a.price,a.name,a.ad_id,a.prot_id,a.is_check,b.sup_id,			a.status AS ban_status,IFNULL (b.status,2) AS `status` from boss_charging_logo a LEFT JOIN boss_charging_logo_assign b ON a.id=b.cl_id group by a.id)z where  $where");
		$this->totalPage = $count[0]['tp_count'];*/


		//没有数据
		if ($this->totalPage == 0) {
			return array();
		}

		return $clList;

	}

	public function generalCode($id) {
		return 'JF' . str_pad(intval($id), 7, 0, STR_PAD_LEFT);
	}

	/**
	 * 处理添加
	 * @param $clData 计费标识数据
	 * @param $adId 广告主id
	 * @param $protId 产品id
	 * @return bool 是否添加成功
	 */
	public function doAdd($clData, $adId, $protId) {
		if (empty($clData)) {
			$this->error = '无添加数据';
			return false;
		}

		foreach ($clData as $key=>$val) {
			//阶梯价格
			$priceType = $val['price_type'];
			if ($priceType == 2) {
				if (!check_tpstr($val['price'])) {
					$this->error = '计费标识阶梯价格格式错误';
				}
			}

			//上传包
			if(!empty($val['url'])) {
				$val['promotion_url'] = $val['url'];
				$val['promotion_url_type'] = 2; //普通链接类型
				if($val["up_type"]==1){
					$val['promotion_url_type'] = 1; //上传文件地址
				}
			//这段代码其实没有用
			} else if(!empty($val['clpk_url'])) { //是否上传操作
				$filepath = $val['clpk_url'];
				$val['promotion_url'] = $filepath;
				$val['promotion_url_type'] = 1; //安装包类型
			}

			$val['ad_id'] = $adId;
			$val['prot_id'] = $protId;
			$val['back_url'] = preg_replace('/(http:\/\/)|(https:\/\/)/i', '', $val['back_url']);//后台地址 去掉http

			$dataset = $val;
			if (I('post.bl_id',0) != 2) { //非ssp执行此检查
				$dataset = array();
				if ($this->create($val) === false) {
					return false;
				}
			}

			if (intval($val['id']) > 0) {
				$r = $this->save($dataset);
			} else { //添加计费标识
				$jfid = $r = $this->add($dataset);
				if ($r !== false) {
					//TODO: 增加默认分配记录
					$_map['cl_id'] = $jfid;
					$_map['sup_id']= 0;
					$_map['bl_id'] = 0;
					$_map['sup_id'] = 0;
					$_map['promotion_price'] = 0;
					$_map['return_cycle'] = 0;
					$_map['settlement_cycle'] = 0;
					$_map['promotion_stime'] = null;
					$_map['status']= 2;

					if(D('Home/ChargingLogoDetail')->doAdd($_map) === false) {
						$this->error = '增加默认分配记录失败：'.D('Home/ChargingLogoDetail')->getError();
						$this->delete($jfid);
						return false;
					}
					//添加后更新计费标识编码
					$_upmap['id'] = $jfid;
					$_upmap['code'] = $this->generalCode($jfid);
					if ($this->save($_upmap) === false) { //更新失败删除刚添加的计费标识
						$this->delete($jfid);
						return false;
					}
				}
			}
			if ($r === false) {
				return false;
			}
		}
		return true;

	}
	public function clossing($time){
		$data=$this->query('select d.out_lineid as outline,d.in_lineid as inline,c.id as comid,a.id as jfid,a.charging_mode,c.source_type,sum(ifnull(d.in_newmoney,0)) AS inmoney,sum(ifnull(d.out_newmoney,0)) AS outmoney,sum(ifnull(d.in_newmoney,0))-sum(ifnull(d.out_newmoney,0)) AS profit,"暂未统计" as inquerenlv,"暂未统计" as outquerenlv,"暂未统计" as kaipiaolv,"暂未统计" as huikuanlv,"暂未统计" as fukuanlv,d.in_adverid,d.out_superid,(1-b.in_settlement_prate)*sum(d.in_newmoney) as neibujiesuan,d.adddate as month from boss_charging_logo a  join boss_daydata_inandout d on d.jfid=a.id && d.adddate like"'.$time.'%" && ((d.in_status != 0 && d.in_status!=9) || (d.out_status != 0 && d.out_status!=9)) join boss_product c on ifnull(d.in_comid,a.prot_id)=c.id left join boss_charging_logo_assign b on b.cl_id=a.id && b.promotion_stime<=d.adddate && if(b.promotion_etime is null,1,b.promotion_etime>=d.adddate) where b.id>0 group by a.id,d.in_lineid,d.out_lineid,d.in_adverid,d.out_superid,d.adddate');
		return $data;
	}


	/**
	 * [getmonthtable description]
	 * @param  [type]  $time_s [description]
	 * @param  [type]  $time_e [description]
	 * @param  boolean $isdown [description]
	 * @return [type]          [description]
	 */
	public function getmonthtable($time_s,$time_e,$isdown=false){//月报数据
		$showtablestr='10000011100111111111100100011';
		if(!empty(I('get.advid'))){
			$w=array();
			foreach (I('get.advid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="ifnull(b.in_adverid,c.ad_id)=".$value;
				else $w[]="d.adverid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[1]="ifnull(b.out_lineid,b.in_lineid) as lineid,";
			$f_arr[4]="ifnull(b.in_adverid,c.ad_id) as adverid,";
			$showtablestr[21]='1';
		}
		if(!empty(I('get.superid'))){
			$w=array();
			foreach (I('get.superid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_superid=".$value;
				else $w[]="e.superid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[5]="b.out_superid as superid,";
			$showtablestr[22]='1';
		}
		if(!empty(I('get.lineid'))){
			$w=array();
			foreach (I('get.lineid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_lineid=".$value." || b.out_lineid=".$value;
				else $w[]="e.lineid=".$value." || d.lineid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$field='';
			$f_arr[1]="ifnull(b.out_lineid,b.in_lineid) as lineid,";
			$showtablestr[1]='1';
		}
		if(!empty(I('get.comid'))){
			$w=array();
			foreach (I('get.comid') as $key => $value) {
				$w[]="ifnull(b.in_comid,a.prot_id)=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[1]="ifnull(b.out_lineid,b.in_lineid) as lineid,";
			$f_arr[2]="c.name as comname,";
			$showtablestr[2]='1';
		}
		if(!empty(I('get.jfid'))){
			$w=array();
			foreach (I('get.jfid') as $key => $value) {
				$w[]="a.id=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[3]="a.name as jfname,";
			$showtablestr[3]='1';
		}
		if(!empty(I('get.sourcetype'))){
			$w=array();
			foreach (I('get.sourcetype') as $key => $value) {
				$w[]="c.source_type=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[6]="c.source_type,";
			$showtablestr[5]='1';
		}
		if(!empty(I('get.module'))){
			$w=array();
			foreach (I('get.module') as $key => $value) {
				$w[]="a.charging_mode=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[7]="a.charging_mode,";
			$showtablestr[4]='1';
		}

		if(!empty(I('get.instatus'))){
			$w=array();
			foreach (I('get.instatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_status=".$value;
				else $w[]="b.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[8]="b.in_status as instatus,";
			$showtablestr[9]='1';
		}
		if(!empty(I('get.outstatus'))){
			$w=array();
			foreach (I('get.outstatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_status=".$value;
				else $w[]="d.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[9]="b.out_status as outstatus,";
			$showtablestr[10]='1';
		}
		if(!empty(I('get.salerid'))){
			$w=array();
			foreach (I('get.salerid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_salerid=".$value;
				else $w[]="b.salerid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[10]="b.in_salerid as salerid,";
			$showtablestr[23]='1';
		}
		if(!empty(I('get.supid'))){
			$w=array();
			foreach (I('get.supid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_businessid=".$value;
				else $w[]="d.businessid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[11]="b.out_businessid as businessid,";
			$showtablestr[24]='1';
		}
		if(!empty(I('get.group_arr'))){
			$group_arr=I('get.group_arr');
			if(in_array('ifnull(in_lineid,out_lineid)',$group_arr)){
				$f_arr[1]="ifnull(b.out_lineid,b.in_lineid) as lineid,";
				$showtablestr[1]='1';
			}
			if(in_array('b.in_comid',$group_arr)){
				$f_arr[1]="ifnull(b.out_lineid,b.in_lineid) as lineid,";
				$f_arr[2]="c.name as comname,c.id as cid,";
				$showtablestr[2]='1';
			}
			if(in_array('b.jfid',$group_arr)){
				$f_arr[3]="a.name as jfname,";
				$showtablestr[3]='1';
			}
			if(in_array('b.in_adverid',$group_arr)){
				$f_arr[1]="ifnull(b.out_lineid,b.in_lineid) as lineid,";
				$f_arr[4]="ifnull(b.in_adverid,c.ad_id) as adverid,";
				$showtablestr[21]='1';
			}
			if(in_array('b.out_superid',$group_arr)){
				$f_arr[5]="b.out_superid as superid,";
				$showtablestr[22]='1';
			}
			if(in_array('c.source_type',$group_arr)){
				$f_arr[6]="c.source_type,";
				$showtablestr[5]='1';
			}
			if(in_array('a.charging_mode',$group_arr)){
				$f_arr[7]="a.charging_mode,";
				$showtablestr[4]='1';
			}
			if(in_array('b.in_status',$group_arr)){
				$f_arr[8]="b.in_status as instatus,";
				$showtablestr[9]='1';
			}
			if(in_array('b.out_status',$group_arr)){
				$f_arr[9]="b.out_status as outstatus,";
				$showtablestr[10]='1';
			}
			if(in_array('b.in_salerid',$group_arr)){
				$f_arr[10]="b.in_salerid as salerid,";
				$showtablestr[23]='1';
			}
			if(in_array('b.out_businessid',$group_arr)){
				$f_arr[11]="b.out_businessid as businessid,";
				$showtablestr[24]='1';
			}
			$group=implode(',', I('get.group_arr'));
		}else{
			$group='a.id,ifnull(in_lineid,out_lineid),b.in_adverid,b.out_superid,b.in_salerid,b.out_businessid,b.in_status,b.out_status,left(b.adddate,7)';
		}
		
		if(count($f_arr)>0)$f_str=implode('', $f_arr);
		else $f_str='';
		if(!empty(I('get.inandout')))$wheres[]="(((b.in_status!=0 && b.in_status!=9) || b.in_status is null) || ((b.out_status!=0 && b.out_status!=9) || b.out_status is null))";



		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr = $_SESSION["userinfo"]["uid"];
            if(!empty(I('get.inandout'))){
            	$wheres[] = "b.in_salerid=".$spidStr;
            	$wheres[] = "b.out_businessid=".$spidStr;

            }else{
            	$wheres[] = "b.salerid=".$spidStr;
            	$wheres[] = "b.businessid=".$spidStr;
            }

        }

        //数据权限
        $arr_name=array();
        $arr_name['line']=array('b.in_lineid','b.out_lineid');
        $arr_name['user']=array('b.in_salerid','b.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;



		if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='1=1';
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$order='';
		if(!empty(I('get.order'))){
        	$order=' order by '.str_replace('_',' ',I('get.order'));
        }
        $sql = "";
        if($isdown){
        	$sql = 'SELECT '.$f_str.' SUM(
					    IF(
					      b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					      0,
					      b.in_newmoney
					    )
					  ) AS indata,
					  SUM(
					    IF(
					      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					      0,
					      b.out_newmoney
					    )
					  ) AS outdata,
					  SUM(
					    IF(
					      b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					      0,
					      b.in_newmoney
					    )
					  ) - SUM(
					    IF(
					      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					      0,
					      b.out_newmoney
					    )
					  ) AS lirun,
					  SUM(
					    IF(
					      b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					      0,
					      b.in_money
					    )
					  ) - SUM(
					    IF(
					      b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					      0,
					      b.in_newmoney
					    )
					  ) AS inhejianmoney,
					  SUM(
					    IF(
					      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					      0,
					      b.out_money
					    )
					  ) - SUM(
					    IF(
					      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					      0,
					      b.out_newmoney
					    )
					  ) AS outhejianmoney,
					  ROUND(
					    SUM(
					      IF(
					        b.in_status >= 2 && b.in_status != 9,
					        b.in_newmoney,
					        0
					      )
					    ) / SUM(
					      IF(
					        b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					        0,
					        b.in_newmoney
					      )
					    ) * 100,
					    2
					  ) AS inquerenlv,
					  SUM(
					    IF(
					      b.in_status >= 2 && b.in_status != 9,
					      b.in_newmoney,
					      0
					    )
					  ) AS inqurenmoney,
					  ROUND(
					    SUM(
					      IF(
					        b.out_status >= 2 && b.out_status != 9,
					        b.out_newmoney,
					        0
					      )
					    ) / SUM(
					      IF(
					        b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					        0,
					        b.out_newmoney
					      )
					    ) * 100,
					    2
					  ) AS outquerenlv,
					  SUM(
					    IF(
					      b.out_status >= 2 && b.out_status != 9,
					      b.out_newmoney,
					      0
					    )
					  ) AS outquerenmoney,
					  ROUND(
					    SUM(
					      IF(
					        b.in_status = 4 || b.in_status = 5,
					        b.in_newmoney,
					        0
					      )
					    ) / SUM(
					      IF(
					        b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					        0,
					        b.in_newmoney
					      )
					    ) * 100,
					    2
					  ) AS kaipiaolv,
					  SUM(
					    IF(
					      b.in_status = 4 || b.in_status = 5,
					      b.in_newmoney,
					      0
					    )
					  ) AS kaipiaomoney,
					  ROUND(
					    SUM(
					      IF(
					        b.in_status IN (8, 5),
					        b.in_newmoney,
					        0
					      )
					    ) / SUM(
					      IF(
					        b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					        0,
					        b.in_newmoney
					      )
					    ) * 100,
					    2
					  ) AS huikuanlv,
					  SUM(
					    IF(
					      b.in_status IN (8, 5),
					      b.in_newmoney,
					      0
					    )
					  ) AS huikuanmoney,
					  "暂未统计" AS fukuanlv,
					  LEFT(b.adddate, 7) AS DATE,
					  a.id AS jfid,
					  b.adddate AS date1, b.out_addid,
					  b.out_superid
					FROM
					  boss_charging_logo a 
					  JOIN boss_daydata_inandout b 
					    ON b.jfid = a.id && b.adddate >= "'.$time_s.'" && b.adddate <= "'.$time_e.'" 
					  JOIN boss_product c 
					    ON IFNULL(b.in_comid, a.prot_id) = c.id 
					WHERE '.$where.' 
					GROUP BY '.$group ;
        }else{
        	$sql = 'SELECT '.$f_str.' SUM(
					    IF(
					      b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					      0,
					      b.in_newmoney
					    )
					  ) AS indata,
					  SUM(
					    IF(
					      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					      0,
					      b.out_newmoney
					    )
					  ) AS outdata,
					  SUM(
					    IF(
					      b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					      0,
					      b.in_newmoney
					    )
					  ) - SUM(
					    IF(
					      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					      0,
					      b.out_newmoney
					    )
					  ) AS lirun,
					  SUM(
					    IF(
					      b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					      0,
					      b.in_money
					    )
					  ) - SUM(
					    IF(
					      b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					      0,
					      b.in_newmoney
					    )
					  ) AS inhejianmoney,
					  SUM(
					    IF(
					      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					      0,
					      b.out_money
					    )
					  ) - SUM(
					    IF(
					      b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					      0,
					      b.out_newmoney
					    )
					  ) AS outhejianmoney,
					  ROUND(
					    SUM(
					      IF(
					        b.in_status >= 2 && b.in_status != 9,
					        b.in_newmoney,
					        0
					      )
					    ) / SUM(
					      IF(
					        b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					        0,
					        b.in_newmoney
					      )
					    ) * 100,
					    2
					  ) AS inquerenlv,
					  SUM(
					    IF(
					      b.in_status >= 2 && b.in_status != 9,
					      b.in_newmoney,
					      0
					    )
					  ) AS inqurenmoney,
					  ROUND(
					    SUM(
					      IF(
					        b.out_status >= 2 && b.out_status != 9,
					        b.out_newmoney,
					        0
					      )
					    ) / SUM(
					      IF(
					        b.out_status IS NULL || b.out_status = 0 || b.out_status = 9,
					        0,
					        b.out_newmoney
					      )
					    ) * 100,
					    2
					  ) AS outquerenlv,
					  SUM(
					    IF(
					      b.out_status >= 2 && b.out_status != 9,
					      b.out_newmoney,
					      0
					    )
					  ) AS outquerenmoney,
					  ROUND(
					    SUM(
					      IF(
					        b.in_status = 4 || b.in_status = 5,
					        b.in_newmoney,
					        0
					      )
					    ) / SUM(
					      IF(
					        b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					        0,
					        b.in_newmoney
					      )
					    ) * 100,
					    2
					  ) AS kaipiaolv,
					  SUM(
					    IF(
					      b.in_status = 4 || b.in_status = 5,
					      b.in_newmoney,
					      0
					    )
					  ) AS kaipiaomoney,
					  ROUND(
					    SUM(
					      IF(
					        b.in_status IN (8, 5),
					        b.in_newmoney,
					        0
					      )
					    ) / SUM(
					      IF(
					        b.in_status IS NULL || b.in_status = 0 || b.in_status = 9,
					        0,
					        b.in_newmoney
					      )
					    ) * 100,
					    2
					  ) AS huikuanlv,
					  SUM(
					    IF(
					      b.in_status IN (8, 5),
					      b.in_newmoney,
					      0
					    )
					  ) AS huikuanmoney,
					  "暂未统计" AS fukuanlv,
					  LEFT(b.adddate, 7) AS DATE,
					  a.id AS jfid,
					  b.adddate AS date1,
				      b.out_addid,
					  b.out_superid
					FROM
					  boss_charging_logo a 
					  JOIN boss_daydata_inandout b 
					    ON b.jfid = a.id && b.adddate >= "'.$time_s.'" && b.adddate <= "'.$time_e.'" 
					  JOIN boss_product c 
					    ON IFNULL(b.in_comid, a.prot_id) = c.id 
					WHERE '.$where.' 
					GROUP BY '."$group $order limit $str,10 ";

        } 
        $data=$this->query($sql);
        if(I("showsql")=="showsql023"){
        	echo $this->getLastSql();exit;
        }
		
		
		$linelist=M('business_line')->field('id,name')->select();
		foreach ($linelist as $key => $value) {
			$linearr[$value['id']]=$value['name'];
		}
		$advlist=M('advertiser')->field('id,name')->select();
		foreach ($advlist as $key => $value) {
			$advarr[$value['id']]=$value['name'];
		}


		$suplist=M('supplier')->field('id,name,fukuanname,type')->select();
		$suparr  = array();
		foreach ($suplist as $key => $value) {
			$suparr[$value['id']] = $value;
		}
		// print_r($suparr);exit;
		$userarr = array();
		
		foreach ($data as $key => $value) {
			if(!empty($value['salerid']) && !in_array($value['salerid'],$userarr))$userarr[]=$value['salerid'];
			if(!empty($value['businessid']) && !in_array($value['businessid'],$userarr))$userarr[]=$value['businessid'];
			if(!empty($value['superid']) && !in_array($value['superid'],$suparr))$suparr[]=$value['superid'];

			//获取供应商付款方 update07 12 tgd 
			$super_fukuan_name = "--";
			if(empty($value["out_addid"])){
				$super_one = $suparr[$value["out_superid"]];
				if($super_one["type"]==2){
					$super_fukuan_name = $super_one["fukuanname"];
				}
				unset($super_one);
			}else{
				$super_one = $suparr[$value["out_addid"]];
				$super_fukuan_name = $super_one["fukuanname"];
				unset($super_one);
			}
			$data[$key]["fukuanname"] = $super_fukuan_name;
		}
		if(count($userarr)>0){
			$res_saler=M('user')->where("id in (".implode(',',$userarr).")")->select();
			foreach ($res_saler as $key => $value) {
				$user_arr[$value['id']]=$value['real_name'];
			}
		}

		// if(count($suparr)>0){
		// 	$res_super=M('supplier')->where("id in (".implode(',',$suparr).")")->select();
		// 	foreach ($res_super as $key => $value) {
		// 		$suparr[$value['id']]=$value;
		// 	}
		// }
		// print_r($suparr);exit;

		foreach ($data as $key => $value) {
			if(!empty($value['lineid']) && !empty($value['outline']) && $value['inline']!=$value['outline']){
				$res=M('charging_logo_assign')->where("promotion_stime<='".$value['date1']."' && if(promotion_etime is null,1,promotion_etime>='".$value['date1']."') && cl_id=".$value['jfid'])->find();
				$data[$key]['neibujiesuan']=$value['neibujiesuan']=$value['indata']*(1-$res['in_settlement_prate']);
				if(!empty(I('get.lineid'))){
					if(in_array($value['inline'],I('get.lineid')) && !in_array($value['outline'],I('get.lineid'))){
						$data[$key]['outdata']=twonum($value['neibujiesuan']);
					}elseif(!in_array($value['inline'],I('get.lineid')) && in_array($value['outline'],I('get.lineid'))){
						$data[$key]['indata']=twonum($value['neibujiesuan']);
					}
				}
			}
			if(!empty($value['superid']) && !empty($value['outline'])){
				if($suparr[$value['superid']]['type']==1){
					$setout=M('settlement_out')->where("superid={$value['superid']}")->order('id desc')->find();
					$setout['addresserid'] = intval($setout['addresserid']);
					if(empty($value['outline']))$value['outline']=$value['inline'];
					$sk=M('supplier_finance')->where("sp_id={$setout['addresserid']} && bl_id={$value['outline']}")->find();
				}else{
					if(empty($value['outline']))$value['outline']=$value['inline'];
					$sk=M('supplier_finance')->where("sp_id={$value['superid']} && bl_id={$value['outline']}")->find();
				}
			}
			
			if(!empty($value['superid']) && !empty($value['outline']))$data[$key]['skname']=$sk['payee_name'];
			if(!empty($value['lineid'])){
				$data[$key]['lineid2']=$value['lineid'];
				$data[$key]['lineid']=$linearr[$value['lineid']];
			}
			if(!empty($value['adverid'])){
				$data[$key]['adverid2']=$value['adverid'];
				$data[$key]['adverid']=$advarr[$value['adverid']];
			}
			if(!empty($value['superid']))$data[$key]['superid']=$suparr[$value['superid']]['name'];
			if(!empty($value['salerid']))$data[$key]['salerid']=$user_arr[$value['salerid']];
			if(!empty($value['businessid']))$data[$key]['businessid']=$user_arr[$value['businessid']];

			//2017.01.17
			if(!empty($value['instatus']))$data[$key]['instatus']=$value['instatus']=C('option.indata_status')[$value['instatus']];
			if(!empty($value['outstatus']))$data[$key]['outstatus']=$value['outstatus']=C('option.outdata_status')[$value['outstatus']];
		}
		return array('data'=>$data,'showtablestr'=>$showtablestr);
	}
	public function getmonthalldata($time_s,$time_e){
		if(!empty(I('get.advid'))){
			$w=array();
			foreach (I('get.advid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="ifnull(b.in_adverid,c.ad_id)=".$value;
				else $w[]="d.adverid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.superid'))){
			$w=array();
			foreach (I('get.superid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_superid=".$value;
				else $w[]="e.superid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.lineid'))){
			$linearr=implode(',',I('get.lineid'));
			if(!empty(I('get.inandout'))){
				$file="sum(if(b.in_lineid not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney),if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))) as indata,sum(if(b.out_lineid not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney),if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney))) as outdata,sum(if(b.in_lineid not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status = 9 || b.in_status = 1,0,b.in_newmoney),if(b.in_status is null || b.in_status = 0 || b.in_status = 9 || b.in_status = 1,0,b.in_newmoney))) as inquerendata,sum(if(b.out_lineid not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status = 9 || b.in_status =0 || b.out_status=9 || b.out_status = 1 || b.out_status is null,0,b.in_newmoney),if(b.out_status is null || b.out_status = 0 || b.out_status = 9 || b.out_status = 1,0,b.out_newmoney))) as outquerendata,sum(if(b.in_lineid not in ($linearr),if(b.in_status in (4,5),b.in_newmoney,0),if(b.in_status in (4,5),b.in_newmoney,0))) as kaipiaomoney,sum(if(b.in_lineid not in ($linearr),if(b.in_status in (8,5),b.in_newmoney,0),if(b.in_status in (8,5),b.in_newmoney,0))) as huikuanmoney";
				$join="";
				$wheres[]="(b.in_lineid in ($linearr) || b.out_lineid in ($linearr))";
			}else{
				$file="sum(if(d.lineid not in ($linearr),d.newmoney*(1-b.in_settlement_prate),d.newmoney)) as indata,sum(if(e.lineid not in ($linearr),d.newmoney*(1-b.in_settlement_prate),e.newmoney)) as outdata";
				$join=" left join boss_charging_logo_assign b on b.cl_id=a.id && b.promotion_stime<=if(d.adddate is null,e.adddate,d.adddate) && if(b.promotion_etime is null,1,b.promotion_etime>=if(d.adddate is null,e.adddate,d.adddate))";
				$wheres[]="(e.lineid in ($linearr) || d.lineid in ($linearr))";
			}
			
			
		}else{
			if(!empty(I('get.inandout')))$file="sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as indata,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as outdata,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9 || b.in_status = 1,0,b.in_newmoney)) as inquerendata,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9 || b.out_status = 1,0,b.out_newmoney)) as outquerendata,sum(if(b.in_status in (4,5),b.in_newmoney,0)) as kaipiaomoney,sum(if(b.in_status in (8,5),b.in_newmoney,0)) as huikuanmoney";
			else $file="sum(d.newmoney) as indata,sum(e.newmoney) as outdata";
			$join='';
		}
		if(!empty(I('get.comid'))){
			$w=array();
			foreach (I('get.comid') as $key => $value) {
				$w[]="ifnull(b.in_comid,a.prot_id)=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.jfid'))){
			$w=array();
			foreach (I('get.jfid') as $key => $value) {
				$w[]="a.id=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.sourcetype'))){
			$w=array();
			foreach (I('get.sourcetype') as $key => $value) {
				$w[]="c.source_type=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.module'))){
			$w=array();
			foreach (I('get.module') as $key => $value) {
				$w[]="a.charging_mode=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.instatus'))){
			$w=array();
			foreach (I('get.instatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_status=".$value;
				else $w[]="b.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outstatus'))){
			$w=array();
			foreach (I('get.outstatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_status=".$value;
				else $w[]="d.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.salerid'))){
			$w=array();
			foreach (I('get.salerid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_salerid=".$value;
				else $w[]="b.salerid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.supid'))){
			$w=array();
			foreach (I('get.supid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_businessid=".$value;
				else $w[]="d.businessid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.inandout')))$wheres[]="(((b.in_status!=0 && b.in_status!=9) || b.in_status is null) || ((b.out_status!=0 && b.out_status!=9) || b.out_status is null))";
		else $wheres['t']="d.id > 0";






		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr = $_SESSION["userinfo"]["uid"];
            if(!empty(I('get.inandout'))){
            	$wheres[] = "b.in_salerid=".$spidStr;
            	$wheres[] = "b.out_businessid=".$spidStr;

            }else{
            	$wheres[] = "b.salerid=".$spidStr;
            	$wheres[] = "b.businessid=".$spidStr;
            }

        }

        //数据权限
        $arr_name=array();
        $arr_name['line']=array('b.in_lineid','b.out_lineid');
        $arr_name['user']=array('b.in_salerid','b.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;




		if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='';
        if(!empty(I('get.inandout'))){
        	$data=$this->field($file)->join('a join boss_daydata_inandout b on b.jfid=a.id && b.adddate >= "'.$time_s.'" && b.adddate <= "'.$time_e.'" join boss_product c on ifnull(b.in_comid,a.prot_id)=c.id'.$join)->where($where)->find();
	        return array('indata'=>$data['indata'],'outdata'=>$data['outdata'],'lirun'=>$data['indata']-$data['outdata'],'inquerendata'=>$data['inquerendata'],'outquerendata'=>$data['outquerendata'],'kaipiaomoney'=>$data['kaipiaomoney'],'huikuanmoney'=>$data['huikuanmoney']);
        }else{
        	$data=$this->field($file)->join('a left join boss_daydata d on d.jfid=a.id && d.adddate >= "'.$time_s.'" && d.adddate <= "'.$time_e.'" && d.status != 0 && d.status!=9 join boss_product c on ifnull(b.in_comid,a.prot_id)=c.id left join boss_daydata_out e on e.jfid=a.id && if(d.adddate is null,e.adddate >= "'.$time_s.'" && e.adddate <= "'.$time_e.'",d.adddate=e.adddate) && e.status != 0 && e.status!=9 '.$join)->where($where)->find();
	        $wheres['t']='d.id is null';
	        if(count($wheres)>0)$where=implode(' && ', $wheres);
	        else $where='';
	        $data2=$this->field('sum(e.newmoney) as outdata')->join('a left join boss_daydata_out e on e.jfid=a.id && e.adddate >= "'.$time_s.'" && e.adddate <= "'.$time_e.'" && e.status != 0 && e.status!=9 left join boss_daydata d on d.jfid=a.id && if(e.adddate is null,d.adddate >= "'.$time_s.'" && d.adddate <= "'.$time_e.'",d.adddate=e.adddate) && d.status != 0 && d.status!=9 join boss_product c on a.prot_id=c.id')->where($where)->find();
	        return array('indata'=>$data['indata'],'outdata'=>$data['outdata']+$data2['outdata'],'lirun'=>$data['indata']-$data['outdata']-$data2['outdata']);
        }
        
	}
	public function getmonthdatacount($time_s,$time_e){
		if(!empty(I('get.advid'))){
			$w=array();
			foreach (I('get.advid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="ifnull(b.in_adverid,c.ad_id)=".$value;
				else $w[]="d.adverid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.superid'))){
			$w=array();
			foreach (I('get.superid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_superid=".$value;
				else $w[]="e.superid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.lineid'))){
			$w=array();
			foreach (I('get.lineid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_lineid=".$value." || b.out_lineid=".$value;
				else $w[]="e.lineid=".$value." || d.lineid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.comid'))){
			$w=array();
			foreach (I('get.comid') as $key => $value) {
				$w[]="ifnull(b.in_comid,a.prot_id)=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.jfid'))){
			$w=array();
			foreach (I('get.jfid') as $key => $value) {
				$w[]="a.id=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.sourcetype'))){
			$w=array();
			foreach (I('get.sourcetype') as $key => $value) {
				$w[]="c.source_type=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.module'))){
			$w=array();
			foreach (I('get.module') as $key => $value) {
				$w[]="a.charging_mode=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.instatus'))){
			$w=array();
			foreach (I('get.instatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_status=".$value;
				else $w[]="b.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outstatus'))){
			$w=array();
			foreach (I('get.outstatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_status=".$value;
				else $w[]="d.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.salerid'))){
			$w=array();
			foreach (I('get.salerid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_salerid=".$value;
				else $w[]="b.salerid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.supid'))){
			$w=array();
			foreach (I('get.supid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_businessid=".$value;
				else $w[]="d.businessid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.group_arr'))){
			$group=implode(',', I('get.group_arr'));
		}else{
			$group='a.id,b.in_lineid,b.out_lineid,b.in_adverid,b.out_superid,b.in_salerid,b.out_businessid,b.in_status,b.out_status,left(b.adddate,7)';
		}
		if(!empty(I('get.inandout')))$wheres[]="(((b.in_status!=0 && b.in_status!=9) || b.in_status is null) || ((b.out_status!=0 && b.out_status!=9) || b.out_status is null))";




		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr = $_SESSION["userinfo"]["uid"];
            if(!empty(I('get.inandout'))){
            	$wheres[] = "b.in_salerid=".$spidStr;
            	$wheres[] = "b.out_businessid=".$spidStr;

            }else{
            	$wheres[] = "b.salerid=".$spidStr;
            	$wheres[] = "b.businessid=".$spidStr;
            }

        }

        //数据权限
        $arr_name=array();
        $arr_name['line']=array('b.in_lineid','b.out_lineid');
        $arr_name['user']=array('b.in_salerid','b.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;

		if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='1=1';
        if(!empty(I('get.inandout'))){
        	$data=$this->query('select count(*) as num from(select a.id from boss_charging_logo a join boss_daydata_inandout b on b.jfid=a.id && b.adddate >= "'.$time_s.'" && b.adddate <= "'.$time_e.'"  join boss_product c on ifnull(b.in_comid,a.prot_id)=c.id where '.$where.' group by '.$group.')z');
	        return (int)$data[0]['num'];
		}else {
			$data=$this->query('select count(*) as num from(select a.id from boss_charging_logo a left join boss_daydata d on d.jfid=a.id && d.adddate >= "'.$time_s.'" && d.adddate <= "'.$time_e.'" && d.status != 0 && d.status!=9 join boss_product c on a.prot_id=c.id left join boss_daydata_out e on e.jfid=a.id && if(d.adddate is null,e.adddate >= "'.$time_s.'" && e.adddate <= "'.$time_e.'",d.adddate=e.adddate) && e.status != 0 && e.status!=9 where d.id>0 && '.$where.' group by '.$group.')z');
	        $data1=$this->query('select count(*) as num from(select a.id from boss_charging_logo a left join boss_daydata_out e on e.jfid=a.id && e.adddate >= "'.$time_s.'" && e.adddate <= "'.$time_e.'" && e.status != 0 && e.status!=9 join boss_product c on a.prot_id=c.id left join boss_daydata d on d.jfid=a.id && if(d.adddate is null,d.adddate >= "'.$time_s.'" && d.adddate <= "'.$time_e.'",d.adddate=e.adddate) && d.status != 0 && d.status!=9 where e.id>0 && d.id is null && '.$where.' group by '.$group.')z');
	        return (int)$data[0]['num']+(int)$data1[0]['num'];
		}
        
	}
	
	public function getdaytable($isdown=false){//日报数据

		if(!empty(I('get.strtime')))$strtime=I('get.strtime');
		else $strtime=date('Y-m-d',time()-24*3600);
		if(!empty(I('get.endtime')))$endtime=I('get.endtime');
		else $endtime=date('Y-m-d',time()-24*3600);
		if(!empty(I('get.inzt'))){
			$w=array();
			foreach (I('get.inzt') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_ztid=".$value;
				else $w[]="b.ztid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outzt'))){
			$w=array();
			foreach (I('get.outzt') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_sbid=".$value;
				else $w[]="d.sbid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.adverid'))){
			$w=array();
			foreach (I('get.adverid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_adverid=".$value."";
				else $w[]="b.adverid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.instatus'))){
			$w=array();
			foreach (I('get.instatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_status=".$value;
				else $w[]="b.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outstatus'))){
			$w=array();
			foreach (I('get.outstatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_status=".$value;
				else $w[]="d.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.superid'))){
			$w=array();
			foreach (I('get.superid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_superid=".$value;
				else $w[]="d.superid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		
		if(!empty(I('get.lineid'))){
			$w=array();
			foreach (I('get.lineid') as $key => $value) {
				if(!empty($value)){
					if(!empty(I('get.inandout')))$w[]="b.in_lineid=".$value." || b.out_lineid=".$value;
					else $w[]="b.lineid=".$value." || d.lineid=".$value;
				}
			}
			if(count($w)>0)$wheres[]="(".implode(' || ',$w).")";
		}

		/*2016.12.29 部门id查业务线id */
		$dept_id = M('user')->field('dept_id')->where("id='".UID."'")->find();
		//echo $dept_id['dept_id'];exit;
		if($dept_id['dept_id'] == 150 or $dept_id['dept_id']==151){
			$busData = M('business_line')->field('id')->where("dept_id='".$dept_id['dept_id']."'")->select();
			//print_r($busData);exit;
			$w=array();
			foreach ($busData as $key => $value) {
				if(!empty($value)){
					if(!empty(I('get.inandout')))$w[]="b.in_lineid=".$value['id']." || b.out_lineid=".$value['id'];
					else $w[]="b.lineid=".$value['id']." || d.lineid=".$value['id'];
				}
			}
			if(count($w)>0)$wheres[]="(".implode(' || ',$w).")";
			//print_r($wheres);exit;

		}

		if(!empty(I('get.comid'))){
			$w=array();
			foreach (I('get.comid') as $key => $value) {
				$w[]="ifnull(b.in_comid,a.prot_id)=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.jfid'))){
			$w=array();
			foreach (I('get.jfid') as $key => $value) {
				$w[]="a.id=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.salerid'))){
			$w=array();
			foreach (I('get.salerid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_salerid=".$value;
				else $w[]="b.salerid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.supid'))){
			$w=array();
			foreach (I('get.supid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_businessid=".$value;
				else $w[]="d.businessid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}



		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
		if($isRead){
		    $spidStr = $_SESSION["userinfo"]["uid"];
		    if(!empty(I('get.inandout'))){
		    	$wheres[] = "b.in_salerid=".$spidStr;
		    	$wheres[] = "b.out_businessid=".$spidStr;

		    }else{
		    	$wheres[] = "b.salerid=".$spidStr;
		    	$wheres[] = "b.businessid=".$spidStr;
		    }

		}
		//数据权限
        $arr_name=array();
        $arr_name['line']=array('b.in_lineid','b.out_lineid');
        $arr_name['user']=array('b.in_salerid','b.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;






		if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='1=1';
        $order='adddate';
        if(!empty(I('get.order'))){
        	$order=str_replace('_',' ',I('get.order'));
        }

        //表格数据
        $p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		if(!empty(I('get.inandout'))){
			if($isdown){
				$data=$this->query('select a.id,b.adddate,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newdata)) as datanum,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as indata,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as outdata,b.in_status as instatus,b.in_auditdate,b.out_status as outstatus,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))-sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as profit,b.in_auditdate as inauditdate,b.out_auditdate as outauditdate,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))-sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_money)) as inhejianmoney,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney))-sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_money)) as outhejianmoney,a.status as logostatus,b.out_lineid as outline,b.in_lineid as inline,e.name as comname,a.name as jfname,a.charging_mode,a.price,c.promotion_price as outprice,b.out_sbid as outjszt,b.in_ztid as injszt,b.in_salerid as salerid,b.out_businessid as businessid,b.out_superid as superid,b.in_adverid as adverid,e.settle_cycle,c.settlement_cycle,a.back_url,a.account,a.password,e.id as comid,a.id as jfasid,(1-c.in_settlement_prate)*sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as neibujiesuan from boss_charging_logo a join boss_daydata_inandout b on a.id=b.jfid && b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'" join boss_product e on ifnull(b.in_comid,a.prot_id)=e.id left join boss_charging_logo_assign c on c.cl_id=a.id && c.promotion_stime<=b.adddate && if(c.promotion_etime is null,1,c.promotion_etime>=b.adddate) where '.$where.' && ((b.in_status!=0 && b.in_status!=9) || (b.out_status!=0 && b.out_status!=9)) group by a.id,b.adddate order by b.adddate');

			}else $data=$this->query('select a.id,b.adddate,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newdata)) as datanum,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as indata,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as outdata,b.in_status as instatus,b.in_auditdate,b.out_status as outstatus,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))-sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as profit,b.in_auditdate as inauditdate,b.out_auditdate as outauditdate,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))-sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_money)) as inhejianmoney,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney))-sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_money)) as outhejianmoney,a.status as logostatus,b.out_lineid as outline,b.in_lineid as inline,e.name as comname,a.name as jfname,a.charging_mode,a.price,c.promotion_price as outprice,b.out_sbid as outjszt,b.in_ztid as injszt,b.in_salerid as salerid,b.out_businessid as businessid,b.out_superid as superid,b.in_adverid as adverid,e.settle_cycle,c.settlement_cycle,a.back_url,a.account,a.password,e.id as comid,a.id as jfasid,(1-c.in_settlement_prate)*sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as neibujiesuan from boss_charging_logo a join boss_daydata_inandout b on a.id=b.jfid && b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'" join boss_product e on ifnull(b.in_comid,a.prot_id)=e.id left join boss_charging_logo_assign c on c.cl_id=a.id && c.promotion_stime<=b.adddate && if(c.promotion_etime is null,1,c.promotion_etime>=b.adddate) where '.$where.' && ((b.in_status!=0 && b.in_status!=9) || (b.out_status!=0 && b.out_status!=9)) group by a.id,b.adddate order by '.$order.' limit '.$str.',10');
		}else{
			if($isdown){
			$data=$this->query('select * from(select a.id,if(b.adddate is null,d.adddate,b.adddate) as adddate,sum(b.newdata) as datanum,sum(b.newmoney) as indata,sum(d.newmoney) as outdata,b.status as instatus,b.auditdate,d.status as outstatus,sum(b.newmoney)-sum(if(d.newmoney is null,0,d.newmoney)) as profit,b.auditdate as inauditdate,d.auditdate as outauditdate,sum(b.newmoney)-sum(b.money) as inhejianmoney,sum(d.newmoney)-sum(d.money) as outhejianmoney,a.status as logostatus,d.lineid as outline,b.lineid as inline,e.name as comname,a.name as jfname,a.charging_mode,a.price,c.promotion_price as outprice,d.sbid as outjszt,e.sb_id as injszt,b.salerid,d.businessid,d.superid,b.adverid,e.settle_cycle,c.settlement_cycle,a.back_url,a.account,a.password,e.id as comid,a.id as jfasid,(1-c.in_settlement_prate)*sum(b.newmoney) as neibujiesuan from boss_charging_logo a left join boss_daydata b on a.id=b.jfid && b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'" && b.status!=0 && b.status!=9 left join boss_daydata_out d on a.id=d.jfid && if(b.adddate is null,d.adddate >="'.$strtime.'" && d.adddate <="'.$endtime.'",d.adddate=b.adddate) && d.status!=0 && d.status!=9 join boss_product e on a.prot_id=e.id left join boss_charging_logo_assign c on c.cl_id=a.id && c.promotion_stime<=if(b.adddate is null,d.adddate,b.adddate) && if(c.promotion_etime is null,1,c.promotion_etime>=if(b.adddate is null,d.adddate,b.adddate)) where '.$where.' && b.id>0 group by a.id,b.adddate  UNION all select a.id,if(b.adddate is null,d.adddate,b.adddate) as adddate,sum(b.newdata) as datanum,sum(b.newmoney) as indata,sum(d.newmoney) as outdata,b.status as instatus,b.auditdate,d.status as outstatus,sum(b.newmoney)-sum(if(d.newmoney is null,0,d.newmoney)) as profit,b.auditdate as inauditdate,d.auditdate as outauditdate,sum(b.newmoney)-sum(b.money) as inhejianmoney,sum(d.newmoney)-sum(d.money) as outhejianmoney,a.status as logostatus,d.lineid as outline,b.lineid as inline,e.name as comname,a.name as jfname,a.charging_mode,a.price,c.promotion_price as outprice,d.sbid as outjszt,e.sb_id as injszt,b.salerid,d.businessid,d.superid,b.adverid,e.settle_cycle,c.settlement_cycle,a.back_url,a.account,a.password,e.id as comid,a.id as jfasid,(1-c.in_settlement_prate)*sum(b.newmoney) as neibujiesuan from boss_charging_logo a left join boss_daydata_out d on a.id=d.jfid && d.adddate >="'.$strtime.'" && d.adddate <="'.$endtime.'" && d.status!=0 && d.status!=9 left join boss_daydata b on a.id=b.jfid && if(d.adddate is null,b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'",d.adddate=b.adddate) && b.status!=0 && b.status!=9 join boss_product e on a.prot_id=e.id left join boss_charging_logo_assign c on c.cl_id=a.id && c.promotion_stime<=if(b.adddate is null,d.adddate,b.adddate) && if(c.promotion_etime is null,1,c.promotion_etime>=if(b.adddate is null,d.adddate,b.adddate)) where '.$where.' && d.id>0 && b.id is null group by a.id,d.adddate)z order by adddate');

			}else $data=$this->query('select * from(select a.id,if(b.adddate is null,d.adddate,b.adddate) as adddate,sum(b.newdata) as datanum,sum(b.newmoney) as indata,sum(d.newmoney) as outdata,b.status as instatus,b.auditdate,d.status as outstatus,sum(b.newmoney)-sum(if(d.newmoney is null,0,d.newmoney)) as profit,b.auditdate as inauditdate,d.auditdate as outauditdate,sum(b.newmoney)-sum(b.money) as inhejianmoney,sum(d.newmoney)-sum(d.money) as outhejianmoney,a.status as logostatus,d.lineid as outline,b.lineid as inline,e.name as comname,a.name as jfname,a.charging_mode,a.price,c.promotion_price as outprice,d.sbid as outjszt,e.sb_id as injszt,b.salerid,d.businessid,d.superid,b.adverid,e.settle_cycle,c.settlement_cycle,a.back_url,a.account,a.password,e.id as comid,a.id as jfasid,(1-c.in_settlement_prate)*sum(b.newmoney) as neibujiesuan from boss_charging_logo a left join boss_daydata b on a.id=b.jfid && b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'" && b.status!=0 && b.status!=9 left join boss_daydata_out d on a.id=d.jfid && if(b.adddate is null,d.adddate >="'.$strtime.'" && d.adddate <="'.$endtime.'",d.adddate=b.adddate) && d.status!=0 && d.status!=9 join boss_product e on a.prot_id=e.id left join boss_charging_logo_assign c on c.cl_id=a.id && c.promotion_stime<=if(b.adddate is null,d.adddate,b.adddate) && if(c.promotion_etime is null,1,c.promotion_etime>=if(b.adddate is null,d.adddate,b.adddate)) where '.$where.' && b.id>0 group by a.id,b.adddate  UNION all select a.id,if(b.adddate is null,d.adddate,b.adddate) as adddate,sum(b.newdata) as datanum,sum(b.newmoney) as indata,sum(d.newmoney) as outdata,b.status as instatus,b.auditdate,d.status as outstatus,sum(b.newmoney)-sum(if(d.newmoney is null,0,d.newmoney)) as profit,b.auditdate as inauditdate,d.auditdate as outauditdate,sum(b.newmoney)-sum(b.money) as inhejianmoney,sum(d.newmoney)-sum(d.money) as outhejianmoney,a.status as logostatus,d.lineid as outline,b.lineid as inline,e.name as comname,a.name as jfname,a.charging_mode,a.price,c.promotion_price as outprice,d.sbid as outjszt,e.sb_id as injszt,b.salerid,d.businessid,d.superid,b.adverid,e.settle_cycle,c.settlement_cycle,a.back_url,a.account,a.password,e.id as comid,a.id as jfasid,(1-c.in_settlement_prate)*sum(b.newmoney) as neibujiesuan from boss_charging_logo a left join boss_daydata_out d on a.id=d.jfid && d.adddate >="'.$strtime.'" && d.adddate <="'.$endtime.'" && d.status!=0 && d.status!=9 left join boss_daydata b on a.id=b.jfid && if(d.adddate is null,b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'",d.adddate=b.adddate) && b.status!=0 && b.status!=9 join boss_product e on a.prot_id=e.id left join boss_charging_logo_assign c on c.cl_id=a.id && c.promotion_stime<=if(b.adddate is null,d.adddate,b.adddate) && if(c.promotion_etime is null,1,c.promotion_etime>=if(b.adddate is null,d.adddate,b.adddate)) where '.$where.' && d.id>0 && b.id is null group by a.id,d.adddate)z order by '.$order.' limit '.$str.',10');
		}

		if(I("showsql")=="showsql023"){
			// print_r(1);
			echo $this->getLastSql();
			exit;
		}
		
		if(count($data)==0)return $data;
		$linearrid=array();
		$comlistid=array();
		$ztid=array();
		$adverlistid=array();
		$superlistid=array();
		$userlistid=array();
		$jflistid=array();
		foreach ($data as $key => $value) {//取出所有产品ID和记费标识ID
			$comarr[$value['comid'].'_'.$value['adddate']]=1;
			$jfasarr[$value['jfasid'].'_'.$value['adddate']]=1;
			if(!in_array($value['inline'],$linearrid) && $value['inline']!='')$linearrid[]=$value['inline'];
			if(!in_array($value['outline'],$linearrid) && $value['outline']!='')$linearrid[]=$value['outline'];
			if(!in_array($value['comid'],$comlistid) && $value['comid']!='')$comlistid[]=$value['comid'];
			if(!in_array($value['outjszt'],$ztid) && $value['outjszt']!='')$ztid[]=$value['outjszt'];
			if(!in_array($value['injszt'],$ztid) && $value['injszt']!='')$ztid[]=$value['injszt'];
			if(!in_array($value['adverid'],$adverlistid) && $value['adverid']!='')$adverlistid[]=$value['adverid'];
			if(!in_array($value['superid'],$superlistid) && $value['superid']!='')$superlistid[]=$value['superid'];
			if(!in_array($value['salerid'],$userlistid) && $value['salerid']!='')$userlistid[]=$value['salerid'];
			if(!in_array($value['businessid'],$userlistid) && $value['businessid']!='')$userlistid[]=$value['businessid'];
			if(!in_array($value['id'],$jflistid) && $value['id']!='')$jflistid[]=$value['id'];
		}
		if(count($linearrid)>0)$linearrid=M('business_line')->field('name,id')->where("id in (".implode(',',$linearrid).")")->select();
		if(count($comlistid)>0)$comlistid=M('product')->field('name,id')->where("id in (".implode(',',$comlistid).")")->select();
		if(count($ztid)>0)$ztid=M('data_dic')->field('name,id')->where("id in (".implode(',',$ztid).")")->select();
		if(count($adverlistid)>0)$adverlistid=M('advertiser')->field('name,id')->where("id in (".implode(',',$adverlistid).")")->select();
		if(count($superlistid)>0)$superlistid=M('supplier')->field('name,id')->where("id in (".implode(',',$superlistid).")")->select();
		if(count($userlistid)>0)$userlistid=M('user')->field('real_name,id')->where("id in (".implode(',',$userlistid).")")->select();
		if(count($jflistid)>0)$jflistid=M('charging_logo')->field('name,id')->where("id in (".implode(',',$jflistid).")")->select();

		//取出所有相关收入成本结算单
		foreach ($comarr as $key => $value) {
			$arr=explode('_',$key);
			$wheres_settlement[]="strdate <= '".$arr[1]."' && enddate >= '".$arr[1]."' && (comid = ".$arr[0]." || allcomid like '".$arr[0].",%' || allcomid like '%,".$arr[0]."' || allcomid like '%,".$arr[0].",%')";
		}
		$where_settlement=implode(' || ',$wheres_settlement);
		$data_settlement=M('settlement_in')->where($where_settlement)->select();
		foreach ($data_settlement as $key => $value) {
			$data_settlement[$key]['comarr']=explode(',',$value['allcomid']);
		}
		foreach ($jfasarr as $key => $value) {
			$arr=explode('_',$key);
			$wheres_settlement2[]="strdate <= '".$arr[1]."' && enddate >= '".$arr[1]."' && (jfid = ".$arr[0]." || alljfid like '".$arr[0].",%' || alljfid like '%,".$arr[0]."' || alljfid like '%,".$arr[0].",%')";
		}
		$where_settlement2=implode(' || ',$wheres_settlement2);
		$data_settlement2=M('settlement_out')->where($where_settlement2)->select();
		foreach ($data_settlement2 as $key => $value) {
			$data_settlement2[$key]['jfarr']=explode(',',$value['allcomid']);
		}

		foreach ($linearrid as $key => $value) {
			$linearrid_t[$value['id']]=$value['name'];
		}
		foreach ($comlistid as $key => $value) {
			$comlistid_t[$value['id']]=$value['name'];
		}
		foreach ($ztid as $key => $value) {
			$ztid_t[$value['id']]=$value['name'];
		}
		foreach ($adverlistid as $key => $value) {
			$adverlistid_t[$value['id']]=$value['name'];
		}
		foreach ($superlistid as $key => $value) {
			$superlistid_t[$value['id']]=$value['name'];
		}
		foreach ($userlistid as $key => $value) {
			$userlistid_t[$value['id']]=$value['real_name'];
		}
		foreach ($jflistid as $key => $value) {
			$jflistid_t[$value['id']]=$value['name'];
		}

		//填充数据
		$showdata=array();
		foreach ($data as $k => $v) {
			$data[$k]['inline']=$v['inline']=$linearrid_t[$v['inline']];
			$data[$k]['outline']=$v['outline']=$linearrid_t[$v['outline']];
			$data[$k]['comid']=$v['comid']=$comlistid_t[$v['inline']];
			$data[$k]['outjszt']=$v['outjszt']=$ztid_t[$v['outjszt']];
			$data[$k]['injszt']=$v['injszt']=$ztid_t[$v['injszt']];
			$data[$k]['adverid']=$v['adverid']=$adverlistid_t[$v['adverid']];
			$data[$k]['superid']=$v['superid']=$superlistid_t[$v['superid']];
			$data[$k]['salerid']=$v['salerid']=$userlistid_t[$v['salerid']];
			$data[$k]['businessid']=$v['businessid']=$userlistid_t[$v['businessid']];
			$data[$k]['id']=$v['id']=$jflistid_t[$v['id']];
			$data[$k]['charging_mode']=$v['charging_mode']=C('option.charging_mode')[$v['charging_mode']];
			$data[$k]['instatus']=$v['instatus']=C('option.indata_status')[$v['instatus']];
			$data[$k]['outstatus']=$v['outstatus']=C('option.outdata_status')[$v['outstatus']];
			$data[$k]['logostatus']=$v['logostatus']=C('option.chargingLogo_status')[$v['logostatus']];
			$data[$k]['settle_cycle']=$v['settle_cycle']=C('option.settlement_cycle')[$v['settle_cycle']];
			$data[$k]['settlement_cycle']=$v['settlement_cycle']=C('option.settlement_cycle')[$v['settlement_cycle']];
			foreach ($data_settlement as $key => $value) {
				if(in_array($v['comid'],$value['comarr']) && $v['adddate']>=$value['strdate'] && $v['adddate']<=$value['enddate']){
					$data[$k]['invoicetime']=$value['invoicetime'];
					$data[$k]['nowskmoneytime']=$value['nowskmoneytime'];
				}
			}
			foreach ($data_settlement2 as $key => $value) {
				if(in_array($v['jfasid'],$value['jfarr']) && $v['adddate']>=$value['strdate'] && $v['adddate']<=$value['enddate']){
					$data[$k]['nowfkmoneytime']=$value['nowfkmoneytime'];
				}
			}
			if($v['inline']==$v['outline'] || $v['outline']==''){
				$data[$k]['bl_id']=$v['inline'];
				unset($data[$k]['neibujiesuan']);
				$showdata[]=$data[$k];
			}elseif($v['inline']==''){
				$data[$k]['bl_id']=$v['outline'];
				unset($data[$k]['neibujiesuan']);
				$showdata[]=$data[$k];
			}else{
				$adddata=$data[$k];
				$data[$k]['bl_id']=$v['outline'];
				$data[$k]['indata']=$v['neibujiesuan'];
				$data[$k]['profit']=$v['indata']-$v['outdata'];
				$showdata[]=$data[$k];
				$adddata['bl_id']=$v['inline'];
				$adddata['outdata']=$v['neibujiesuan'];
				$adddata['profit']=$adddata['indata']-$adddata['outdata'];
				$showdata[]=$adddata;
			}
		}
		return $showdata;
		
	}
	public function getalldata($isimg=0){//统计总量

		if(!empty(I('get.strtime')))$strtime=I('get.strtime');
		else $strtime=date('Y-m-d',time()-24*3600);
		if(!empty(I('get.endtime')))$endtime=I('get.endtime');
		else $endtime=date('Y-m-d',time()-24*3600);
		if(!empty(I('get.inzt'))){
			$w=array();
			foreach (I('get.inzt') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_ztid=".$value;
				else $w[]="b.ztid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outzt'))){
			$w=array();
			foreach (I('get.outzt') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_sbid=".$value;
				else $w[]="d.sbid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.adverid'))){
			$w=array();
			foreach (I('get.adverid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_adverid=".$value;
				else $w[]="b.adverid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.superid'))){
			$w=array();
			foreach (I('get.superid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_superid=".$value;
				else $w[]="d.superid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.instatus'))){
			$w=array();
			foreach (I('get.instatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_status=".$value;
				else $w[]="b.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outstatus'))){
			$w=array();
			foreach (I('get.outstatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_status=".$value;
				else $w[]="d.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		$lineid_    = I('lineid');
		$isshowzc   = I("isshowzc");
		$zcb_lineid = I("zcb_lineid");





		if(!empty(I('get.lineid')) && I('get.lineid')[0]!=0){
			

			$linearr=implode(',',I('get.lineid'));
			$inandout = I('get.inandout');

			//面前写死了
			if(!empty($inandout)){
				//一直进入
				

				$file="sum(if(b.in_lineid  not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status =9,0,b.in_newmoney)*(1-c.in_settlement_prate),if(b.in_status is null || b.in_status = 0 || b.in_status =9,0,b.in_newmoney))) as indata,sum(if(b.out_lineid not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status =9,0,b.in_newmoney)*(1-c.in_settlement_prate),if(b.out_status is null || b.out_status = 0 || b.out_status =9,0,b.out_newmoney))) as outdata";


				$join=' left join boss_charging_logo_assign c on c.cl_id=a.id && c.promotion_stime<=b.adddate && if(c.promotion_etime is null,1,c.promotion_etime>=b.adddate)';

				//总裁办数据判断
				/*if($isshowzc == 500){
					//不包含总裁办
					$wheres[] = "(b.in_lineid in ($linearr) || b.out_lineid in ($linearr))";
				}else{*/
					//只显示选择的业务线
					$wheres[] = "(b.in_lineid in ($linearr) || b.out_lineid in ($linearr))";
				//}



			}else{

				//不会进入
				$file="sum(if(b.lineid not in ($linearr),b.newmoney*(1-c.in_settlement_prate),b.newmoney)) as indata,sum(if(d.lineid not in ($linearr),b.newmoney*(1-c.in_settlement_prate),d.newmoney)) as outdata";
				$join=' left join boss_charging_logo_assign c on c.cl_id=a.id && c.promotion_stime<=if(b.adddate is null,d.adddate,b.adddate) && if(c.promotion_etime is null,1,c.promotion_etime>=if(b.adddate is null,d.adddate,b.adddate))';

				//总裁办数据判断
				/*if($isshowzc == 500){
					$wheres[] = "(b.in_lineid not in ({$zcb_lineid}) || b.out_lineid not in ({$zcb_lineid}))";
				}else{*/
					$wheres[]="(b.lineid in ($linearr) || d.lineid in ($linearr))";
				//}



				
			}
			if($isimg==1){
				$st=date('Y');
				$sm=date('m',strtotime(I('get.strtime')));
				$em=date('m',strtotime(I('get.endtime')));

				//总裁办数据判断
				$where_str = "";
				if($isshowzc == 500){
					$where_str = "bl_id not in ($linearr) && years=$st && months>=$sm && months<=$em";
				}else{
					$where_str = "bl_id in ($linearr) && years=$st && months>=$sm && months<=$em";
				}

				$allpmoney=M('project_target')->field('sum(money) as money')->where($where_str)->find();
			}
		}else{
			// print_r(1);

			//排除总裁办数据
			if($isshowzc==500){
				$wheres[]="(b.in_lineid not in ({$zcb_lineid}) || b.out_lineid not in ({$zcb_lineid}))";
			}

			$join='';
			if(!empty(I('get.inandout')))$file="sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as indata,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as outdata";
			else $file="sum(b.newmoney) as indata,sum(d.newmoney) as outdata";
			if($isimg==1){
				$st=date('Y');
				$sm=date('m',strtotime(I('get.strtime')));
				$em=date('m',strtotime(I('get.endtime')));
				$allpmoney=M('project_target')->field('sum(money) as money')->where("years=$st && months>=$sm && months<=$em")->find();
			}
		}
		if(!empty(I('get.comid'))){
			$w=array();
			foreach (I('get.comid') as $key => $value) {
				$w[]="ifnull(b.in_comid,a.prot_id)=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.jfid'))){
			$w=array();
			foreach (I('get.jfid') as $key => $value) {
				$w[]="a.id=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.salerid'))){
			$w=array();
			foreach (I('get.salerid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_salerid=".$value;
				else $w[]="b.salerid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}



		if(!empty(I('get.supid'))){
			$w=array();
			foreach (I('get.supid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_businessid=".$value;
				else $w[]="d.businessid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}



		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr = $_SESSION["userinfo"]["uid"];
            if(!empty(I('get.inandout'))){
            	$wheres[] = "b.in_salerid=".$spidStr;
            	$wheres[] = "b.out_businessid=".$spidStr;

            }else{
            	$wheres[] = "b.salerid=".$spidStr;
            	$wheres[] = "b.businessid=".$spidStr;
            }

        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('b.in_lineid','b.out_lineid');
        $arr_name['user']=array('b.in_salerid','b.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;
        



        

		if(!empty(I('get.inandout'))){
			// print_r(expression)

			//始终进入这里,前面写死了--可视化报表数据操作
			$wheres[]="((b.in_status!=0 && b.in_status!=9) || (b.out_status!=0 && b.out_status!=9))";
			if(count($wheres)>0)$where=implode(' && ', $wheres);
	        else $where='';
	        if($isimg==0)$file2=',sum(if(b.in_newdata is null || b.in_status = 0 || b.in_status = 9,0,b.in_newdata)) as datanum,sum(if(b.in_newmoney is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))-sum(if(b.in_money is null || b.in_status = 0 || b.in_status = 9,0,b.in_money)) as inhejianmoney,sum(if(b.out_newmoney is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney))-sum(if(b.out_money is null || b.out_status = 0 || b.out_status = 9,0,b.out_money)) as outhejianmoney';
	        else $file2=",sum(if(b.in_status!=9 && b.in_status>1,in_newmoney,0))/sum(if(b.in_status!=9 && b.in_status>0,in_newmoney,0)) as inqueren,sum(if(b.out_status!=9 && b.out_status>1,out_newmoney,0))/sum(if(b.out_status!=9 && b.out_status>0,out_newmoney,0)) as outqueren";

	        // print_r($where);exit;
	        $data=M('charging_logo')->field($file.$file2)->join('a join boss_daydata_inandout b on a.id=b.jfid && b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'" join boss_product e on ifnull(b.in_comid,a.prot_id)=e.id'.$join)->where($where)->find();
	        // $sql = M('charging_logo')->getLastSql();
	        // print_r($sql);
		}else{
			// print_r(1);
			$wheres['t']='b.id>0';
			if(count($wheres)>0)$where=implode(' && ', $wheres);
	        else $where='';
	        //表格数据
	        $p=I('get.p');
			if($p<1)$p=1;
			$str=($p-1)*10;
			$data=M('charging_logo')->field($file.',sum(b.newdata) as datanum,sum(b.newmoney)-sum(b.money) as inhejianmoney,sum(if(d.newmoney is null,0,d.newmoney))-sum(if(d.money is null,0,d.money)) as outhejianmoney')->join('a left join boss_daydata b on a.id=b.jfid && b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'" && b.status!=0 && b.status!=9 left join boss_daydata_out d on a.id=d.jfid && if(b.adddate is null,d.adddate >="'.$strtime.'" && d.adddate <="'.$endtime.'",d.adddate=b.adddate) && d.status!=0 && d.status!=9 join boss_product e on a.prot_id=e.id'.$join)->where($where)->find();
			$wheres['t']='b.id is null';
			if(count($wheres)>0)$where=implode(' && ', $wheres);
	        else $where='';
	        $data2=M('charging_logo')->field('sum(d.newmoney) as outdata')->join('a left join boss_daydata_out d on a.id=d.jfid && d.adddate >="'.$strtime.'" && d.adddate <="'.$endtime.'" && d.status!=0 && d.status!=9 left join boss_daydata b on a.id=b.jfid && if(d.adddate is null,b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'",d.adddate=b.adddate) && b.status!=0 && b.status!=9 join boss_product e on a.prot_id=e.id'.$join)->where($where)->find();
		} 
		
		
		//$isimg==1：表示可视化报表，0：表示从月报来的
		if($isimg==0){
			$list = array(
				'indata'  =>$data['indata'],
				'outdata' =>$data['outdata']+$data2['outdata'],
				'profit'  =>$data['indata']-$data['outdata']-$data2['outdata']);
			return $list;
		}else{
			$list = array(
				'indata'    =>twonum($data['indata']/10000,','),
				'outdata'   =>twonum($data['outdata']/10000,','),
				'profit'    =>twonum(($data['indata']-$data['outdata'])/10000,','),
				'inqueren'  =>twonum($data['inqueren'],',')*100,
				'outqueren' =>twonum($data['outqueren'],',')*100,
				'allpmoney' =>twonum($allpmoney['money'])
				);
			return $list;
		} 
	}
	public function getdaydatacount(){
		if(!empty(I('get.strtime')))$strtime=I('get.strtime');
		else $strtime=date('Y-m-d',time()-24*3600);
		if(!empty(I('get.endtime')))$endtime=I('get.endtime');
		else $endtime=date('Y-m-d',time()-24*3600);
		if(!empty(I('get.inzt'))){
			$w=array();
			foreach (I('get.inzt') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_ztid=".$value;
				else $w[]="b.ztid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outzt'))){
			$w=array();
			foreach (I('get.outzt') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_sbid=".$value;
				else $w[]="d.sbid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.instatus'))){
			$w=array();
			foreach (I('get.instatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_status=".$value;
				else $w[]="b.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outstatus'))){
			$w=array();
			foreach (I('get.outstatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_status=".$value;
				else $w[]="d.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.adverid'))){
			$w=array();
			foreach (I('get.adverid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_adverid=".$value;
				else $w[]="b.adverid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.superid'))){
			$w=array();
			foreach (I('get.superid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_superid=".$value;
				else $w[]="d.superid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.lineid'))){
			$w=array();
			foreach (I('get.lineid') as $key => $value) {
				if(!empty($value)){
					if(!empty(I('get.inandout')))$w[]="b.in_lineid=".$value." || b.out_lineid=".$value;
					else $w[]="b.lineid=".$value." || d.lineid=".$value;
				}
			}
			if(count($w)>0)$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.comid'))){
			$w=array();
			foreach (I('get.comid') as $key => $value) {
				$w[]="ifnull(b.in_comid,a.prot_id)=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.jfid'))){
			$w=array();
			foreach (I('get.jfid') as $key => $value) {
				$w[]="a.id=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.salerid'))){
			$w=array();
			foreach (I('get.salerid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_salerid=".$value;
				else $w[]="b.salerid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.supid'))){
			$w=array();
			foreach (I('get.supid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_businessid=".$value;
				else $w[]="d.businessid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}

		//判断当前用户是否只读取自己的数据-临时处理：商务专员只看自己的数据，模块：合作管理、数据管理、财务管理 update 2017-10-12
		$isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr = $_SESSION["userinfo"]["uid"];
            if(!empty(I('get.inandout'))){
            	$wheres[] = "b.in_salerid=".$spidStr;
            	$wheres[] = "b.out_businessid=".$spidStr;

            }else{
            	$wheres[] = "b.salerid=".$spidStr;
            	$wheres[] = "b.businessid=".$spidStr;
            }
        }


        //数据权限
        $arr_name=array();
        $arr_name['line']=array('b.in_lineid','b.out_lineid');
        $arr_name['user']=array('b.in_salerid','b.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;



		if(!empty(I('get.inandout')))$wheres[]="((b.in_status!=0 && b.in_status!=9) || (b.out_status!=0 && b.out_status!=9))";
		else $wheres[]="(d.id>0 || b.id>0)";
		if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='';
        //表格数据
        if(!empty(I('get.inandout'))) $data=$this->query('select count(*) as num from (SELECT distinct a.id,b.adddate FROM `boss_charging_logo` a join boss_daydata_inandout b on a.id=b.jfid && b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'" WHERE '.$where.')z');
		else $data=$this->query('select count(*) as num from (SELECT distinct a.id,b.adddate,d.adddate as t FROM `boss_charging_logo` a left join boss_daydata b on a.id=b.jfid && b.adddate >="'.$strtime.'" && b.adddate <="'.$endtime.'" && b.status!=0 && b.status!=9 left join boss_daydata_out d on a.id=d.jfid && if(b.adddate is null,d.adddate >="'.$strtime.'" && d.adddate <="'.$endtime.'",d.adddate=b.adddate) && d.status!=0 && d.status!=9 WHERE '.$where.')z');
		return $data[0];

	}

	/*关账数据查询*/
	public function getmonthtable_close($time_s,$time_e,$isdown=false){//月报数据
		$showtablestr='1000001110011111111110000011';
		if(!empty(I('get.advid'))){
			$w=array();
			foreach (I('get.advid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_adverid=".$value;
				else $w[]="d.adverid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[4]="ifnull(b.in_adverid,c.ad_id) as adverid,";
			$showtablestr[21]='1';
		}
		if(!empty(I('get.superid'))){
			$w=array();
			foreach (I('get.superid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_superid=".$value;
				else $w[]="e.superid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[5]="b.out_superid as superid,";
			$showtablestr[22]='1';
		}
		if(!empty(I('get.lineid'))){
			$w=array();
			foreach (I('get.lineid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_lineid=".$value." || b.out_lineid=".$value;
				else $w[]="e.lineid=".$value." || d.lineid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$field='';
			$f_arr[1]="b.out_lineid as outline,b.in_lineid as inline,";
			$showtablestr[1]='1';
		}
		if(!empty(I('get.comid'))){
			$w=array();
			foreach (I('get.comid') as $key => $value) {
				$w[]="ifnull(b.in_comid,a.prot_id)=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[2]="c.name as comname,";
			$showtablestr[2]='1';
		}
		if(!empty(I('get.jfid'))){
			$w=array();
			foreach (I('get.jfid') as $key => $value) {
				$w[]="a.id=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[3]="a.name as jfname,";
			$showtablestr[3]='1';
		}
		if(!empty(I('get.sourcetype'))){
			$w=array();
			foreach (I('get.sourcetype') as $key => $value) {
				$w[]="c.source_type=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[6]="c.source_type,";
			$showtablestr[5]='1';
		}
		if(!empty(I('get.module'))){
			$w=array();
			foreach (I('get.module') as $key => $value) {
				$w[]="a.charging_mode=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[7]="a.charging_mode,";
			$showtablestr[4]='1';
		}

		if(!empty(I('get.instatus'))){
			$w=array();
			foreach (I('get.instatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_status=".$value;
				else $w[]="b.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[8]="b.in_status as instatus,";
			$showtablestr[9]='1';
		}
		if(!empty(I('get.outstatus'))){
			$w=array();
			foreach (I('get.outstatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_status=".$value;
				else $w[]="d.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[9]="b.out_status as outstatus,";
			$showtablestr[10]='1';
		}
		if(!empty(I('get.salerid'))){
			$w=array();
			foreach (I('get.salerid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_salerid=".$value;
				else $w[]="b.salerid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[10]="b.in_salerid as salerid,";
			$showtablestr[23]='1';
		}
		if(!empty(I('get.supid'))){
			$w=array();
			foreach (I('get.supid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_businessid=".$value;
				else $w[]="d.businessid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
			$f_arr[11]="b.out_businessid as businessid,";
			$showtablestr[24]='1';
		}
		if(!empty(I('get.group_arr'))){
			$group_arr=I('get.group_arr');
			if(in_array('b.in_lineid,b.out_lineid',$group_arr)){
				$f_arr[1]="ifnull(b.out_lineid,b.in_lineid) as lineid,";
				$showtablestr[1]='1';
			}
			if(in_array('b.in_comid',$group_arr)){
				$f_arr[2]="c.name as comname,";
				$showtablestr[2]='1';
			}
			if(in_array('b.jfid',$group_arr)){
				$f_arr[3]="a.name as jfname,";
				$showtablestr[3]='1';
			}
			if(in_array('b.in_adverid',$group_arr)){
				$f_arr[4]="ifnull(b.in_adverid,c.ad_id) as adverid,";
				$showtablestr[21]='1';
			}
			if(in_array('b.out_superid',$group_arr)){
				$f_arr[5]="b.out_superid as superid,";
				$showtablestr[22]='1';
			}
			if(in_array('c.source_type',$group_arr)){
				$f_arr[6]="c.source_type,";
				$showtablestr[5]='1';
			}
			if(in_array('a.charging_mode',$group_arr)){
				$f_arr[7]="a.charging_mode,";
				$showtablestr[4]='1';
			}
			if(in_array('b.in_status',$group_arr)){
				$f_arr[8]="b.in_status as instatus,";
				$showtablestr[9]='1';
			}
			if(in_array('b.out_status',$group_arr)){
				$f_arr[9]="b.out_status as outstatus,";
				$showtablestr[10]='1';
			}
			if(in_array('b.in_salerid',$group_arr)){
				$f_arr[10]="b.in_salerid as salerid,";
				$showtablestr[23]='1';
			}
			if(in_array('b.out_businessid',$group_arr)){
				$f_arr[11]="b.out_businessid as businessid,";
				$showtablestr[24]='1';
			}
			$group=implode(',', I('get.group_arr'));
		}else{
			$group='a.id,b.in_lineid,b.out_lineid,b.in_adverid,b.out_superid,b.in_salerid,b.out_businessid,b.in_status,b.out_status,left(b.adddate,7)';
		}
		
		if(count($f_arr)>0)$f_str=implode('', $f_arr);
		else $f_str='';
		if(!empty(I('get.inandout')))$wheres[]="((b.in_status!=0 && b.in_status!=9) || (b.out_status!=0 && b.out_status!=9))";

		//数据权限
        $arr_name=array();
        $arr_name['line']=array('b.in_lineid','b.out_lineid');
        $arr_name['user']=array('b.in_salerid','b.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;

		if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='1=1';
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$order='';
		if(!empty(I('get.order'))){
        	$order=' order by '.str_replace('_',' ',I('get.order'));
        }
        if(!empty(I('get.inandout'))){
        	if($isdown) $data=$this->query('select '.$f_str.'sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as indata,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as outdata,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_money))-sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as inhejianmoney,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_money))-sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as outhejianmoney,ROUND(sum(if(b.in_status>=2 && b.in_status!=9,b.in_newmoney,0))/sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))*100,2) as inquerenlv,sum(if(b.in_status>=2 && b.in_status!=9,b.in_newmoney,0)) as inqurenmoney,ROUND(sum(if(b.out_status>=2 && b.out_status!=9,b.out_newmoney,0))/sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_money))*100,2) as outquerenlv,sum(if(b.out_status>=2 && b.out_status!=9,b.out_newmoney,0)) as outquerenmoney,ROUND(sum(if(b.in_status=4 || b.in_status=5,b.in_newmoney,0))/sum(if(b.in_status=4 || b.in_status=5 || b.in_status=3,b.in_newmoney,0))*100,2) as kaipiaolv,sum(if(b.in_status=4 || b.in_status=5,b.in_newmoney,0)) as kaipiaomoney,ROUND(sum(if(b.in_status=5,b.in_newmoney,0))/sum(if(b.in_status=4 || b.in_status=5 || b.in_status=3,b.in_newmoney,0))*100,2) as huikuanlv,sum(if(b.in_status=5,b.in_newmoney,0)) as huikuanmoney,"暂未统计" as fukuanlv,left(b.adddate,7) as date,a.id as jfid,b.adddate as date1 from boss_charging_logo a join boss_daydata_inandout b on b.jfid=a.id && b.adddate >= "'.$time_s.'" && b.adddate <= "'.$time_e.'" join boss_product c on ifnull(b.in_comid,a.prot_id)=c.id where '.$where.' group by '.$group);
			else $data=$this->query('select '.$f_str.'sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as indata,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as outdata,sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_money))-sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as inhejianmoney,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_money))-sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as outhejianmoney,ROUND(sum(if(b.in_status>=2 && b.in_status!=9,b.in_newmoney,0))/sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))*100,2) as inquerenlv,sum(if(b.in_status>=2 && b.in_status!=9,b.in_newmoney,0)) as inqurenmoney,ROUND(sum(if(b.out_status>=2 && b.out_status!=9,b.out_newmoney,0))/sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_money))*100,2) as outquerenlv,sum(if(b.out_status>=2 && b.out_status!=9,b.out_newmoney,0)) as outquerenmoney,ROUND(sum(if(b.in_status=4 || b.in_status=5,b.in_newmoney,0))/sum(if(b.in_status=4 || b.in_status=5 || b.in_status=3,b.in_newmoney,0))*100,2) as kaipiaolv,sum(if(b.in_status=4 || b.in_status=5,b.in_newmoney,0)) as kaipiaomoney,ROUND(sum(if(b.in_status=5,b.in_newmoney,0))/sum(if(b.in_status=4 || b.in_status=5 || b.in_status=3,b.in_newmoney,0))*100,2) as huikuanlv,sum(if(b.in_status=5,b.in_newmoney,0)) as huikuanmoney,"暂未统计" as fukuanlv,left(b.adddate,7) as date,a.id as jfid,b.adddate as date1 from boss_charging_logo a join boss_daydata_inandout b on b.jfid=a.id && b.adddate >= "'.$time_s.'" && b.adddate <= "'.$time_e.'" join boss_product c on ifnull(b.in_comid,a.prot_id)=c.id where '.$where.' group by '."$group $order limit $str,10");
			//echo $this->getLastSql();exit;
        }
		
		$linelist=M('business_line')->field('id,name')->select();
		foreach ($linelist as $key => $value) {
			$linearr[$value['id']]=$value['name'];
		}
		$advlist=M('advertiser')->field('id,name')->select();
		foreach ($advlist as $key => $value) {
			$advarr[$value['id']]=$value['name'];
		}
		$suplist=M('supplier')->field('id,name')->select();
		foreach ($suplist as $key => $value) {
			$suparr[$value['id']]=$value['name'];
		}
		$userarr=array();
		$suparr=array();
		foreach ($data as $key => $value) {
			if(!empty($value['salerid']) && !in_array($value['salerid'],$userarr))$userarr[]=$value['salerid'];
			if(!empty($value['businessid']) && !in_array($value['businessid'],$userarr))$userarr[]=$value['businessid'];
			if(!empty($value['superid']) && !in_array($value['superid'],$suparr))$suparr[]=$value['superid'];
		}
		if(count($userarr)>0){
			$res_saler=M('user')->where("id in (".implode(',',$userarr).")")->select();
			foreach ($res_saler as $key => $value) {
				$user_arr[$value['id']]=$value['real_name'];
			}
		}
		if(count($suparr)>0){
			$res_super=M('supplier')->where("id in (".implode(',',$suparr).")")->select();
			foreach ($res_super as $key => $value) {
				$suparr[$value['id']]=$value;
			}
		}
		foreach ($data as $key => $value) {
			if(!empty($value['inline']) && !empty($value['outline']) && $value['inline']!=$value['outline']){
				$res=M('charging_logo_assign')->where("promotion_stime<='".$value['date1']."' && if(promotion_etime is null,1,promotion_etime>='".$value['date1']."') && cl_id=".$value['jfid'])->find();
				$data[$key]['neibujiesuan']=$value['neibujiesuan']=$value['indata']*(1-$res['in_settlement_prate']);
				if(!empty(I('get.lineid'))){
					if(in_array($value['inline'],I('get.lineid')) && !in_array($value['outline'],I('get.lineid'))){
						$data[$key]['outdata']=twonum($value['neibujiesuan']);
					}elseif(!in_array($value['inline'],I('get.lineid')) && in_array($value['outline'],I('get.lineid'))){
						$data[$key]['indata']=twonum($value['neibujiesuan']);
					}
				}
			}
			
			if(!empty($value['superid'])){
				if($suparr[$value['superid']]['type']==1){
					$setout=M('settlement_out')->where("superid={$value['superid']}")->order('id desc')->find();
					$setout['addresserid'] = intval($setout['addresserid']);
					if(empty($value['outline']))$value['outline']=$value['inline'];
					$value['outline'] = empty($value['outline'])?0:$value['outline'];
					$sk=M('supplier_finance')->where("sp_id={$setout['addresserid']} and bl_id={$value['outline']}")->find();
				}else{
					if(empty($value['outline']))$value['outline']=$value['inline'];
					$value['outline'] = empty($value['outline'])?0:$value['outline'];
					$sk=M('supplier_finance')->where("sp_id={$value['superid']} and bl_id={$value['outline']}")->find();
				}
			}
			
			if(!empty($value['superid']))$data[$key]['skname']=$sk['payee_name'];
			if(!empty($value['lineid']))$data[$key]['lineid']=$linearr[$value['lineid']];
			if(!empty($value['adverid']))$data[$key]['adverid']=$advarr[$value['adverid']];
			if(!empty($value['superid']))$data[$key]['superid']=$suparr[$value['superid']]['name'];
			$data[$key]['lirun']=twonum($data[$key]['indata']-$data[$key]['outdata']);
			if(!empty($value['salerid']))$data[$key]['salerid']=$user_arr[$value['salerid']];
			if(!empty($value['businessid']))$data[$key]['businessid']=$user_arr[$value['businessid']];
			//2017.01.17
			if(!empty($value['instatus']))$data[$key]['instatus']=$value['instatus']=C('option.indata_status')[$value['instatus']];
			if(!empty($value['outstatus']))$data[$key]['outstatus']=$value['outstatus']=C('option.outdata_status')[$value['outstatus']];
		}
		return array('data'=>$data,'showtablestr'=>$showtablestr);
	}
	public function getmonthalldata_close($time_s,$time_e){
		if(!empty(I('get.advid'))){
			$w=array();
			foreach (I('get.advid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_adverid=".$value;
				else $w[]="d.adverid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.superid'))){
			$w=array();
			foreach (I('get.superid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_superid=".$value;
				else $w[]="e.superid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.lineid'))){
			$linearr=implode(',',I('get.lineid'));
			if(!empty(I('get.inandout'))){
				$file="sum(if(b.in_lineid not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)*(1-d.in_settlement_prate),if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney))) as indata,sum(if(b.out_lineid not in ($linearr),if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)*(1-d.in_settlement_prate),if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney))) as outdata";
				$join=" left join boss_charging_logo_assign d on d.cl_id=a.id && d.promotion_stime<=b.adddate && if(d.promotion_etime is null,1,d.promotion_etime>=b.adddate)";

				$wheres[]="(b.in_lineid in  ($linearr) || b.out_lineid in ($linearr))";
			}else{
				$file="sum(if(d.lineid not in ($linearr),d.newmoney*(1-b.in_settlement_prate),d.newmoney)) as indata,sum(if(e.lineid not in ($linearr),d.newmoney*(1-b.in_settlement_prate),e.newmoney)) as outdata";
				$join=" left join boss_charging_logo_assign b on b.cl_id=a.id && b.promotion_stime<=if(d.adddate is null,e.adddate,d.adddate) && if(b.promotion_etime is null,1,b.promotion_etime>=if(d.adddate is null,e.adddate,d.adddate))";
				$wheres[]="(e.lineid in ($linearr) || d.lineid in ($linearr))";
			}
			
			
		}else{
			if(!empty(I('get.inandout')))$file="sum(if(b.in_status is null || b.in_status = 0 || b.in_status = 9,0,b.in_newmoney)) as indata,sum(if(b.out_status is null || b.out_status = 0 || b.out_status = 9,0,b.out_newmoney)) as outdata";
			else $file="sum(d.newmoney) as indata,sum(e.newmoney) as outdata";
			$join='';
		}
		if(!empty(I('get.comid'))){
			$w=array();
			foreach (I('get.comid') as $key => $value) {
				$w[]="ifnull(b.in_comid,a.prot_id)=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.jfid'))){
			$w=array();
			foreach (I('get.jfid') as $key => $value) {
				$w[]="a.id=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.sourcetype'))){
			$w=array();
			foreach (I('get.sourcetype') as $key => $value) {
				$w[]="c.source_type=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.module'))){
			$w=array();
			foreach (I('get.module') as $key => $value) {
				$w[]="a.charging_mode=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.instatus'))){
			$w=array();
			foreach (I('get.instatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_status=".$value;
				else $w[]="b.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outstatus'))){
			$w=array();
			foreach (I('get.outstatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_status=".$value;
				else $w[]="d.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.salerid'))){
			$w=array();
			foreach (I('get.salerid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_salerid=".$value;
				else $w[]="b.salerid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.supid'))){
			$w=array();
			foreach (I('get.supid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_businessid=".$value;
				else $w[]="d.businessid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.inandout')))$wheres[]="((b.in_status!=0 && b.in_status!=9) || (b.out_status!=0 && b.out_status!=9))";
		else $wheres['t']="d.id > 0";

		//数据权限
        $arr_name=array();
        $arr_name['line']=array('b.in_lineid','b.out_lineid');
        $arr_name['user']=array('b.in_salerid','b.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;

		if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='';
        if(!empty(I('get.inandout'))){
        	$data=$this->field($file)->join('a left join boss_closing b on b.jfid=a.id && b.adddate >= "'.$time_s.'" && b.adddate <= "'.$time_e.'" join boss_product c on ifnull(b.in_comid,a.prot_id)=c.id'.$join)->where($where)->find();
	        return array('indata'=>$data['indata'],'outdata'=>$data['outdata'],'lirun'=>$data['indata']-$data['outdata']);
        }
        
	}
	public function getmonthdatacount_close($time_s,$time_e){
		if(!empty(I('get.advid'))){
			$w=array();
			foreach (I('get.advid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_adverid=".$value;
				else $w[]="d.adverid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.superid'))){
			$w=array();
			foreach (I('get.superid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_superid=".$value;
				else $w[]="e.superid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.lineid'))){
			$w=array();
			foreach (I('get.lineid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_lineid=".$value." || b.out_lineid=".$value;
				else $w[]="e.lineid=".$value." || d.lineid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.comid'))){
			$w=array();
			foreach (I('get.comid') as $key => $value) {
				$w[]="ifnull(b.in_comid,a.prot_id)=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.jfid'))){
			$w=array();
			foreach (I('get.jfid') as $key => $value) {
				$w[]="a.id=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.sourcetype'))){
			$w=array();
			foreach (I('get.sourcetype') as $key => $value) {
				$w[]="c.source_type=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.module'))){
			$w=array();
			foreach (I('get.module') as $key => $value) {
				$w[]="a.charging_mode=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.instatus'))){
			$w=array();
			foreach (I('get.instatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_status=".$value;
				else $w[]="b.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.outstatus'))){
			$w=array();
			foreach (I('get.outstatus') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_status=".$value;
				else $w[]="d.status=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.salerid'))){
			$w=array();
			foreach (I('get.salerid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.in_salerid=".$value;
				else $w[]="b.salerid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.supid'))){
			$w=array();
			foreach (I('get.supid') as $key => $value) {
				if(!empty(I('get.inandout')))$w[]="b.out_businessid=".$value;
				else $w[]="d.businessid=".$value;
			}
			$wheres[]="(".implode(' || ',$w).")";
		}
		if(!empty(I('get.group_arr'))){
			$group=implode(',', I('get.group_arr'));
		}else{
			$group='a.id,b.in_lineid,b.out_lineid,b.in_adverid,b.out_superid,b.in_salerid,b.out_businessid,b.in_status,b.out_status,left(b.adddate,7)';
		}
		if(!empty(I('get.inandout')))$wheres[]="((b.in_status!=0 && b.in_status!=9) || (b.out_status!=0 && b.out_status!=9))";


		//数据权限
        $arr_name=array();
        $arr_name['line']=array('b.in_lineid','b.out_lineid');
        $arr_name['user']=array('b.in_salerid','b.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;



		if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='1=1';
        if(!empty(I('get.inandout'))){
        	$data=$this->query('select count(*) as num from(select a.id from boss_charging_logo a left join boss_closing b on b.jfid=a.id && b.adddate >= "'.$time_s.'" && b.adddate <= "'.$time_e.'"  join boss_product c on ifnull(b.in_comid,a.prot_id)=c.id where '.$where.' group by '.$group.')z');
	        return (int)$data[0]['num'];
		}
        
	}
}