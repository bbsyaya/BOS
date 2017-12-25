<?php
namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;
/**
 * 流程通知提醒功能(每步提醒) 定时任务，每5分钟跑一次
 * Class OaNotice
 * @package OA\Controller
 */
class OaNoticeController extends BaseController
{

    public function index()
    {

        $tiXing = M('oa_tixing');
        $Data = $tiXing->field("a.id,a.liuchenid,a.jiedianid,a.userid,a.addtime,b.adduser,b.addtime,b.name")->join("a join boss_oa_liuchen b on a.liuchenid=b.liuchenid")->where("a.is_check=0")->select();//->limit(10)

        foreach($Data as $key=>$val){

            $minute=floor((time()-strtotime(strtotime($val['addtime'])))%86400/60);
            $hour=floor((time()-strtotime(strtotime($val['addtime'])))%86400/3600);
            $prompt_information = M('prompt_information');

            /*if( $minute <= 6 ){//先判断6分钟之内的有没有提醒

                //通知审核人
                $addData = array();
                $addData['date_time'] = date('Y-m-d H:i:s');
                $addData['send_user'] = $val['userid'];
                $addData['content'] = "请审核流程 (OA号:".$val['liuchenid'].")".$val['name'];
                $addData['a_link'] = "/OA/Index/useing?lcid=".$val['liuchenid']."&jdid=".$val['jiedianid']."&txid=".$val['id']."";
                $addData['oa_number'] = $val['liuchenid'];
                $prompt_information->add($addData);
            }else*/if($hour >4){//判断是否超过4个小时

                //判断超过4小时的有没有提醒
                $txData = $prompt_information->field("id")->where("oa_number='".$val['liuchenid']."' and find_in_set(".$val['adduser'].",send_user)")->find();
                if(empty($txData)){

                    //通知审核人及申请人
                    $addData = array();
                    $addData['date_time'] = date('Y-m-d H:i:s');
                    $addData['send_user'] = $val['userid'].','.$val['adduser'];
                    $addData['content'] = "(OA号:".$val['liuchenid'].")流程 ".$val['name']."已超过4小时,请处理";
                    $addData['a_link'] = "/OA/Index/useing?lcid=".$val['liuchenid']."&jdid=".$val['jiedianid']."&txid=".$val['id']."" ;
                    $addData['oa_number'] = $val['liuchenid'];
                    $addData['status'] = 3;
                    $prompt_information->add($addData);

                }
            }
        }
    }
}