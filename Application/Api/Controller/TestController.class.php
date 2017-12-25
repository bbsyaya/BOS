<?php
namespace Api\Controller;
use Think\Controller\RestController;
//测试
class TestController extends RestController {

    public function index() {

	    $param = array(
	    	'appid' => '101',
		    'ts' => '1476670082',
		    'sign' => '702c8490576cae6e6d14e5515d4b42c2',
//----------------------
		    'pro_id'=>2,
	    );

	    //$httpHeader = array('Host: boss.cm');
	    $urlhost= 'http://devboss3.yandui.com/Api/';
	    $path = 'Oa/sel_senddata';
	    $res = postRequest($urlhost.$path, $param);
	    P($res, true);

    }

}


