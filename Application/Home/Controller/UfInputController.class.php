<?php
/**
 * 财务系统对接 接口
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/14
 * Time: 9:30
 */
namespace Home\Controller;
use Common\Controller\BaseController;
class UfInputController extends BaseController
{

    public function index()
    {

        /*token start*/
        mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        $gcm = "/finanInter";
        $key = "1qaz#EDC5tgb&UJM";
        $middle = base64_encode($gcm . $key);
        $date_time = date('YmdHi', time());
        $date_time = base64_encode($date_time . 'L');
        $token = $uuid . $middle . $date_time;
        /*token end*/

        $http_r = 'http://bos3api.yandui.com:16088';

        /*基础信息 start*/
        //广告主
        $adv_f = '/bCustomer/insertBCustomer';
        $advModel = M('advertiser');
        $advData = $advModel->field('ad_code,name')->where("status=1")->order("id desc")->select();
        $adv_url = $http_r . $gcm . $adv_f . '?token=' . $token;
        //curl传送数据
        $advData_a = json_encode($advData);
        $adv_res = bossPostData_json($adv_url, $advData_a);
        $adv_res = json_decode($adv_res, true);
        if($adv_res['message'] == "success"){
            echo "同步广告主信息成功"."\n";
        }else{
            echo "同步失败,状态码：".$adv_res['code']."错误信息：".$adv_res['message'];exit;
        }

        //供应商
        $sup_f = '/bVendor/insertBVendor';
        $supModel = M('supplier');
        $supData = $supModel->field('code,name')->where("status=1")->order("id desc")->select();
        $sup_url = $http_r . $gcm . $sup_f . '?token=' . $token;
        //curl传送数据
        $supData = json_encode($supData);
        $sup_res = bossPostData_json($sup_url, $supData);
        $sup_res = json_decode($sup_res, true);
        if($sup_res['message'] == "success"){
            echo "同步供应商信息成功"."\n";
        }else{
            echo "同步失败,状态码：".$sup_res['code']."错误信息：".$sup_res['message'];exit;
        }

        //业务线
        $bus_f = '/bItem/insertBItem';
        $busModel = M('business_line');
        $busData = $busModel->field('id,name')->where("status=1")->order("id desc")->select();
        $bus_url = $http_r . $gcm . $bus_f . '?token=' . $token;
        //curl传送数据
        $busData = json_encode($busData);
        $bus_res = bossPostData_json($bus_url, $busData);
        $bus_res = json_decode($bus_res, true);
        if($bus_res['message'] == "success"){
            echo "同步产品线信息成功";
        }else{
            echo "同步失败,状态码：".$bus_res['code']."错误信息：".$bus_res['message'];exit;
        }
        /*基础信息 end*/
    }

}