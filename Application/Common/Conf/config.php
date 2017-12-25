<?php
	if(!empty(I('get.trace')))$trace_open=true;
	else $trace_open=false;

	define('UPLOAD_BASIS_IMG_PATH','./upload/creative/img');
	define('UPLOAD_QZ_IMG_PATH','./upload/qz/img');
	define('UPLOAD_INDATA_EXCEL_PATH','./upload/excel/indata');
	define('UPLOAD_INMONEY_EXCEL_PATH','./upload/excel/inmoney');
	define('UPLOAD_ORTHER_EXCEL_PATH','./upload/excel/orther');
	define('UPLOAD_INMONEY_ADVERTISER_PATH','./upload/advertiser_qualify/file');
	define('CL_PACKAGE_PATH','./upload/cl_package/file');
	define('UPLOAD_CONTRACT','./upload/creative/file');
	define('UPLOAD_CONTROL_OBJ','./upload/control_objectives/file');//
	define('UPLOAD_TALENT_POOL','./upload/talent_pool/file');
	define('UPLOAD_OA_FILE_PATH','./upload/oa/file');
	define('UPLOAD_ANNEX','./upload/annex/file');
	define('UC_AUTH_KEY', 'K(myt@z&UI=3C}hjF-Mx"9XEs`{!w73+fu,HLZ5G'); //用户md5加密key
	define('ACTION_LOG_PATH', realpath(LOG_PATH) . '/actionlog');
	if(!is_dir(UPLOAD_OA_FILE_PATH))mkdir(UPLOAD_OA_FILE_PATH,0777,true);
	if(!is_dir(UPLOAD_BASIS_IMG_PATH))mkdir(UPLOAD_BASIS_IMG_PATH,0777,true);
	if(!is_dir(UPLOAD_QZ_IMG_PATH))mkdir(UPLOAD_QZ_IMG_PATH,0777,true);
	if(!is_dir(UPLOAD_INDATA_EXCEL_PATH))mkdir(UPLOAD_INDATA_EXCEL_PATH,0777,true);
	if(!is_dir(UPLOAD_INMONEY_EXCEL_PATH))mkdir(UPLOAD_INMONEY_EXCEL_PATH,0777,true);
	if(!is_dir(UPLOAD_ORTHER_EXCEL_PATH))mkdir(UPLOAD_ORTHER_EXCEL_PATH,0777,true);
	if(!is_dir(UPLOAD_CONTRACT))mkdir(UPLOAD_CONTRACT,0777,true);
	$host='192.168.1.176';
	$mongohost="192.168.1.5";
	$weburl="http://it.yandui.com";
	if( $_SERVER['HTTP_HOST'] == 'devboss3.yandui.com'){
		$host='127.0.0.1';
		$mongohost="127.0.0.1";
		//测试机
		$weburl="http://devboss3.yandui.com";
	}elseif(file_exists('is_lo.jsp')){
		//本地
		$host='devboss3.yandui.com';
		$mongohost="devboss3.yandui.com";
		$weburl='';
	}
	//配置
	return array(
			'DB_TYPE'   => 'mysql', // 数据库类型
			'DB_HOST'   => $host,// 服务器地址
			'DB_NAME'   => 'boss3_www', // 数据库名
			'DB_USER'   => 'boss3_www',// 用户名
			'DB_PWD'    => '/AtgwN0V5WemmnpacX8A',// 密码
			'DB_PORT'   => 3306, // 端口
			'DB_PARAMS' =>  array(), // 数据库连接参数
			'DB_PREFIX' => 'boss_', // 数据库表前缀 
			'DB_CHARSET'=> 'utf8', // 字符集
			'DB_DEBUG'  =>  TRUE, // 数据库调试模式 开启后可以记录SQL日志
			'MONGOHOST' => $mongohost,
			
			'SHOW_PAGE_TRACE'   =>$trace_open,//开启trace调试
			'MODULE_ALLOW_LIST' =>    array('Home','Api','Cron','OA'),
			'DEFAULT_MODULE'    =>    'Home',  // 默认模块
			'URL_MODEL'         =>2,
			
			'AUTH_ON'           => true, //认证开关
			'AUTH_TYPE'         => 1, // 认证方式，1为时时认证；2为登录认证。
			'AUTH_GROUP'        => 'boss_auth_group', //用户组数据表名
			'AUTH_GROUP_ACCESS' => 'boss_auth_group_access', //用户组明细表
			'AUTH_RULE'         => 'boss_auth_rule', //权限规则表
			'AUTH_USER'         => 'boss_user',//用户信息表
			
			'USER_ADMINISTRATOR' => 1, //管理员用户ID
			'TAGLIB_BUILD_IN'=>'Cx,Home\TagLib\Boss',
			'INIT_PASSWORD' => '123456', //用户初始密码
			
			'SEASLOG_LEVEL' => array(
				SEASLOG_INFO      => "信息",
				SEASLOG_ERROR     => "错误",
				SEASLOG_DEBUG     => '调试',
				SEASLOG_NOTICE    => "通知",
				SEASLOG_WARNING   => "警告",
				SEASLOG_CRITICAL  => "致命",
				SEASLOG_ALERT     => "警惕",
				SEASLOG_EMERGENCY => "紧急",
			),
			
			'LOG_MODULE' => array(
							'test'    => '测试',
							'api'     => '接口',
							'cron'    => '计划任务',
							'partner' =>'合作伙伴',
			),
			//日常配置数据
			"AUDIT_API_HTTP"=>"http://bos3api.yandui.com:16088",//审计api调取地址--java程序
			//审计api调取地址
			"AUDIT_API_URL"=>array(
					"queryRbillData_Url"    =>"/finanInter/rBillData/queryRbillData?token=",////销售发票凭证数据查询
					"queryPamentData_Url"   =>"/finanInter/payMent/queryPamentData?token=",//付款凭证数据查询
					"queryAddMoneyData_Url" =>"/finanInter/rData/queryAddMoneyData?token=",//收入凭证数据查询
					"adjustAccount_Url"     =>"/finanInter/rData/adjustAccount?token=",///应收账款调整
			),
			//获取财务凭证数据-php程序
			"VOUCHER_IP"=>"http://bos3api.yandui.com:188",  //正式：http://bos3api.yandui.com:188；测试：http://192.168.7.51:88
			"VOUCHER_URL"=>array(
					"queryRbillData_Url"    =>"/api/Voucher/getRBillData",////销售发票凭证数据查询
					"queryAddMoneyData_Url" =>"/api/Voucher/getVRData",//收入凭证数据查询
					"queryPamentData_Url"   =>"/api/Voucher/getPaymentData",//付款凭证数据查询
			),
			'LOAD_EXT_CONFIG' => array('OPTION'=>'option'),
			'LIST_ROWS'=>10,
			//邮件
			"EMAIL_HOST"     =>"smtp.exmail.qq.com", //邮件发送服务器
			"EMAIL_USERNAME" =>"yyjk@yandui.com", //邮件登录名
			"EMAIL_PWD"      =>"Ywyy2017", //邮件登录名
			//web_url

			"WEB_URL"=>$weburl
);
