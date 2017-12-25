<?php
/**
 * 行政办公管理
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-11-03
 * Time: 10:18
 */

namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class OfficeManagementController extends BaseController
{
    public function index(){

        $ser = new Service\AuthAccessService();
        $officeTree = $ser->getAuthOffice(UID,233);
        if($officeTree){
            $name  =  $officeTree[0]['name'];
            $this->assign("mainurl",$name);
        }

        $this->display();
    }
}