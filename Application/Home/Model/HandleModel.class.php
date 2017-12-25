<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/29
 * Time: 10:53
 */
namespace Home\Model;
use Think\Model;
use Common\Service;
class HandleModel extends Model {

    protected $trueTableName =   'boss_oa_66';
    public $totalPage = 0;
    public function getList(){

        $wheres[]="x739c8a_3 <> '测试' and x739c8a_9>0 and yy_status=0";
        if(I('get.ggzname')){
            $wheres[]="a.x739c8a_3 like '%".I('get.ggzname')."%'";
        }
        if(!empty(I('get.fid')))$wheres[]="a.x739c8a_13 = '".I('get.fid')."'";
        if(!empty(I('get.comname')))$wheres[]="a.x739c8a_14 like '%".I('get.comname')."%'";

        if(!empty(I('get.sb_name')))$wheres[]="a.x739c8a_0 like '%".I('get.sb_name')."%'";

        if(!empty(I('get.strtime')))$wheres[]="b.overtime like '%".I('get.strtime')."%'";
        else$wheres[]="b.overtime like '%".date('Y-m')."%'";

        if(!empty(I('get.status')) && I('get.status') ==1)$wheres[]="(a.x739c8a_9 - a.pay_money)=0";
        elseif(I('get.status') ==1) $wheres[]="(a.x739c8a_9 - a.pay_money)<0";

        if(!empty(I('get.lc_status')) && I('get.lc_status') ==2)$wheres[]="c.status=2";
        elseif (I('get.lc_status') ==1) $wheres[]="c.status<>2";
        //数据权限
        $arr_name=array();
        $arr_name['user']=array('c.adduser');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;

        if(count($wheres)>0)$where=implode(' and ', $wheres);

        //结算款
        $data = M('oa_66')->field("a.*,DATE_FORMAT(b.overtime,'%Y-%m-%d') as pay_date,d.real_name as adduser,DATE_FORMAT(c.addtime,'%Y-%m-%d') as addtime,c.beginuser,c.name,c.status")
            ->join('a
                LEFT JOIN boss_oa_tixing b ON a.x739c8a_1=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.x739c8a_1 AND c.`status`=2
                LEFT join boss_user d on d.id=c.adduser')
            ->where($where)->order('a.id desc')->page($_GET['p'],C('LIST_ROWS'))->select();

        $Row = $this->join('a
                LEFT JOIN boss_oa_tixing b ON a.x739c8a_1=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.x739c8a_1 AND c.`status`=2')->where($where)->count();

        $this->totalPage =$Row;
        return $data;
    }

    public function getSum(){
        $wheres[]="x739c8a_3 <> '测试' and x739c8a_9>0 and yy_status=0";
        if(I('get.ggzname')){
            $wheres[]="a.x739c8a_3 like '%".I('get.ggzname')."%'";
        }
        if(!empty(I('get.fid')))$wheres[]="a.x739c8a_13 = '".I('get.fid')."'";
        if(!empty(I('get.comname')))$wheres[]="a.x739c8a_14 like '%".I('get.comname')."%'";


        if(!empty(I('get.strtime')))$wheres[]="b.overtime like '%".I('get.strtime')."%'";
        else$wheres[]="b.overtime like '%".date('Y-m')."%'";


        if(!empty(I('get.status')) && I('get.status') ==1)$wheres[]="(a.x739c8a_9 - a.pay_money)=0";
        elseif(I('get.status') ==1) $wheres[]="(a.x739c8a_9 - a.pay_money)<0";

        if(!empty(I('get.lc_status')) && I('get.lc_status') ==2)$wheres[]="c.status=2";
        elseif (I('get.lc_status') ==1) $wheres[]="c.status<>2";


        //数据权限
        $arr_name=array();
        $arr_name['user']=array('c.adduser');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;
        if(count($wheres)>0)$where=implode(' and ', $wheres);


        $sum_data = $this->field('round(sum(a.x739c8a_9),2) as data_22,round(sum(a.pay_money),2) as pay_money')->join('a
                LEFT JOIN boss_oa_tixing b ON a.x739c8a_1=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.x739c8a_1 AND c.`status`=2
                LEFT join boss_user d on d.id=c.adduser')->where($where)->find();


        return $sum_data;
    }

    function getListData(){

        $wheres[]="x739c8a_3 <> '测试' and x739c8a_9>0 and yy_status=0";
        if(I('get.ggzname')){
            $wheres[]="a.x739c8a_3 like '%".I('get.ggzname')."%'";
        }
        if(!empty(I('get.fid')))$wheres[]="a.x739c8a_13 = '".I('get.fid')."'";
        if(!empty(I('get.comname')))$wheres[]="a.x739c8a_14 like '%".I('get.comname')."%'";

        if(!empty(I('get.strtime')))$wheres[]="b.overtime like '%".I('get.strtime')."%'";
        else$wheres[]="b.overtime like '%".date('Y-m')."%'";

        if(!empty(I('get.status')) && I('get.status') ==1)$wheres[]="(a.x739c8a_9 - a.pay_money)=0";
        elseif(I('get.status') ==1) $wheres[]="(a.x739c8a_9 - a.pay_money)<0";

        if(!empty(I('get.lc_status')) && I('get.lc_status') ==2)$wheres[]="c.status=2";
        elseif (I('get.lc_status') ==1) $wheres[]="c.status<>2";
        if(count($wheres)>0)$where=implode(' and ', $wheres);

        //结算款
        $data = M('oa_66')->field("a.*,DATE_FORMAT(b.overtime,'%Y-%m-%d') as pay_date,d.real_name as adduser,DATE_FORMAT(c.addtime,'%Y-%m-%d') as addtime,c.beginuser,c.name,c.status")
            ->join('a
                LEFT JOIN boss_oa_tixing b ON a.x739c8a_1=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.x739c8a_1 AND c.`status`=2
                LEFT join boss_user d on d.id=c.adduser')
            ->where($where)->order('a.id desc')->select();

        return $data;
    }
}