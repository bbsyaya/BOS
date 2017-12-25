<?php
/**
 * 情报库
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-10-25
 * Time: 11:10
 */
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 情报库
 */
class IntelligenceLibraryController extends BaseController
{
    public function  index(){

        $this->display();
    }
}