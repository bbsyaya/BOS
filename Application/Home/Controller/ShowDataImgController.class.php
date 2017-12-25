<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Org\Util\PHPEXCEL;
use Common\Service;
class ShowDataImgController extends BaseController {
    public function index(){
        $this->redirect('/ShowDataImg/profit');
    }
    public function profit(){//收益管理
        $t1=date('m.1');//本月1号
        $t2=date("m.d");//当前日期
        $t3=date("m.1",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月1号
        $t4=date("m.d",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月今天
        $t5=date("m.d",strtotime(date('Y').'-'.date("m").'-0'));//上月末
        $t6=date('Y-m-d',time()-3600*24*7);//7天前
        $t7=date('Y-m-d',time()-3600*24*30);//30天前
        $t8=date("Y-01-01");
        $t9=date("Y-m-d");
       

        $this->date_arr=array($t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9);

        //检查当前用户有查看总裁办数据权限 
        $url         = "/Home/ShowDataImg/auth_showZCdata";
        $isHas_check = $_SESSION["sec_".$url];
        if(!$isHas_check){
            $isHas_check           = isHasAuthToQuery($url,UID);
            $_SESSION["sec_".$url] = $isHas_check;
        }
        $this->assign('isHas_check',$isHas_check);

        //直接查看top
        $querytop10 = trim(I("querytop10"));
        if($querytop10==200){
            setcookie("profit_co",1201);
            $this->assign('querytop10',$querytop10);

             //上月
            $pre_start = date("Y-m",strtotime("-1 month"));
            $days      = getMonthDays_com($pre_start);
            $pre_end   = $pre_start."-".$days;
            $pre_start .= "-01";
            
            $this->assign('pre_start',$pre_start);
            $this->assign('pre_end',$pre_end);
        }
        $this->display();
    }
    public function showzhongcaibandata(){
        $t1=date('m.1');//本月1号
        $t2=date("m.d");//当前日期
        $t3=date("m.1",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月1号
        $t4=date("m.d",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月今天
        $t5=date("m.d",strtotime(date('Y').'-'.date("m").'-0'));//上月末
        $t6=date('Y-m-d',time()-3600*24*7);//7天前
        $t7=date('Y-m-d',time()-3600*24*30);//30天前
        $t8=date("Y-01-01");
        $t9=date("Y-m-d");
        $this->date_arr=array($t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9);
        $this->display();
    }
    public function getcon(){
        $nowmonth=date("Y-m");
        $nowday=date("Y-m-d");
        $endday=date("Y-m-d",time()+3600*24*31);
        $where='';
        if(!empty(I('get.lineid'))){
            $r=M('business_line')->where("id=".I('get.lineid'))->find();
            $where=" && data_612 = '{$r['name']}'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('data_612');
        $arr_name['user']=array('data_107');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name,0);
        $where.= " && $myrule_data";

        $res=M('flow_data_434')->field("data_139,count(*) as allnum,sum(if(status=1 && data_50!='流程作废' && data_111>'$nowday',1,0)) as youxiaonum,sum(if(left(data_3,7)='$nowmonth',1,0)) as thismonthaddnum,sum(if((data_6 != '' or data_7 != '') and data_111 <= '$endday' && data_111 > '$nowday' && remark!=1 && remark!=3,1,0)) as tixingnum,sum((data_6 != '' or data_7 != '') and if(data_111 <= '$nowday' && remark!=1 && remark!=3,1,0)) as dqtixingnum")->where("(data_139='推广合同' || data_139='销售合同')".$where)->group('data_139')->select();
        $time=date('Y');
        $res2=M('flow_data_434')->field("count(*) as num,data_139 as type,left(data_3,7) as date")->where("(data_139='推广合同' || data_139='销售合同') && left(data_3,4)='$time'".$where)->group('data_139,left(data_3,7)')->order('left(data_3,7)')->select();
        $arr_1=array();
        $arr_2=array();
        foreach ($res2 as $key => $value) {
            if($value['type']=='销售合同')$value['type']='xs';
            if($value['type']=='推广合同')$value['type']='tg';
            $arr_1[$value['type']][]=array('value'=>$value['num'],'itemStyle'=>array('normal'=>array('label'=>array('show'=>true,'textStyle'=>array('fontWeight'=>'bold','fontSize'=>14)))));
            $arr_2[$value['date']]=$value['date'];
        }
        foreach ($arr_2 as $k => $v) {
            $arr_3[]=$v;
        }
        echo json_encode(array('top'=>$res,'m'=>array('data'=>$arr_1,'date'=>$arr_3)));
    }
    public function contract(){//合同分析
        
        $this->display();
    }
}