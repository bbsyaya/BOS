<?php
/**²úÆ·¼à¿Ø(µ¼º½)
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-07-19
 * Time: 9:28
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
class ProductMonitoringController extends BaseController {
    public function index(){
        $where =array();
        $list = $this->lists($this, $where);
        $this->assign('list', $list);
        $this->display();
    }

    /*ÁÐ±í*/
    public function getList($where, $field){
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('b.bl_id');
        $arr_name['user']=array('b.saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        if($myrule_data!="")$where=$myrule_data;
        else $where="";
        //ºÏÍ¬µ½ÆÚ²úÆ·(ÇóºÍ)
        $subQuery = M('daydata')->field('b.name,c.`name` as adv_name,sum(if(da.status=1,da.newmoney,0)) as newmoney,sum(if(da.status in (1,2,3,4),da.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney')
            ->join("da
JOIN boss_product b ON da.comid=b.id and b.cooperate_state=1 and b.contract_e_duration<date_format(now(),'%Y-%m-%d')
LEFT JOIN boss_advertiser c on c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=da.jfid and d.adddate=da.adddate")
            ->where($where)->group('b.id')->buildSql();
        $contratct_count = M('daydata')->table($subQuery.' aa')->where()->count();

        
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('c.bl_id');
        $arr_name['user']=array('c.saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        if($myrule_data!="")$where= " && $myrule_data";
        else $where="";

        //ÓâÆÚ²âÊÔ²úÆ·(ÇóºÍ)
        $whereTestStatus = 'test_status>0';
        $subQuery =	M('daydata')
            ->field('
				  c.id AS pro_id,
				  a.id,
				  f.sdate AS start_time,
				  IFNULL(SUM(IFNULL(a.newdata, a.datanum)),0) AS datanum,
				  IFNULL(SUM(IFNULL(a.newmoney, a.money)),0) AS money,
				  sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,
				  c.order_test_type,
				  c.order_test_quota,
				  c.name AS pro_name,
				  c.code AS pro_code,
				  d.name AS ad_name,
				  e.real_name AS saler_name,
				  CASE order_test_type
				    WHEN 1 THEN DATEDIFF(NOW(),f.sdate) - order_test_quota
					WHEN 2 THEN SUM(IFNULL(a.newdata,a.datanum)) - order_test_quota
					WHEN 3 THEN SUM(IFNULL(a.newmoney,a.money)) - order_test_quota
				  END AS test_status')
            ->join(' a
				  JOIN boss_product c
				    ON a.comid = c.id
				  JOIN boss_advertiser d
				    ON c.ad_id = d.id
				  LEFT JOIN boss_user e
				    ON e.id = a.salerid
				  JOIN (SELECT comid,MIN(a.adddate) AS sdate FROM `boss_daydata` a join boss_product b on a.comid=b.id where a.adddate>=b.laststoptime && b.cooperate_state =2 GROUP BY a.comid) f ON f.comid=c.id')
            ->where("c.cooperate_state=2 and a.adddate>=c.laststoptime".$where)
            ->group('a.comid')
            ->having($whereTestStatus)
            ->buildSql();
        $yu_count = M('daydata')->table($subQuery.' aa')->where()->count();

        //²âÊÔÎ´Í¨¹ý²úÆ·(ÇóºÍ)
        $subQuery = M('product')
            ->field("c.id")
            ->join('c JOIN
(select a.id,b.add_date  from boss_product a
join (SELECT pr_id,max(add_date) as add_date  FROM boss_product_state WHERE  cooperate_state IN (2,3) group by pr_id having count(id)>1) b
on a.id=b.pr_id and a.cooperate_state=3) h on h.id=c.id
left join `boss_daydata` a on h.id=a.comid
JOIN boss_advertiser d ON c.ad_id = d.id
LEFT JOIN boss_user e ON e.id = a.salerid
left JOIN (SELECT comid,MIN(a.adddate) AS sdate FROM `boss_daydata` a join boss_product b on a.comid=b.id where  b.cooperate_state =3 GROUP BY a.comid ) g ON g.comid=c.id
LEFT JOIN boss_daydata_out f ON f.jfid=a.jfid AND f.adddate=a.adddate')
            ->where("h.id NOT IN (3423,3441,3215,3440) and DATEDIFF(DATE_FORMAT(h.add_date,'%Y-%m-%d'),g.sdate)>0".$where)
            ->group('a.comid')
            ->buildSql();

        $wtg_count = M('product')->table($subQuery.' aa')->where()->count();
        $count_data = array('a'=>$contratct_count,'b'=>$yu_count,'c'=>$wtg_count);
        $this->assign('count_data',$count_data);


        $where = '1=1';
        $pro_name = I('get.pro_name');
        if($pro_name){
            $where .= " and b.name like '%".$pro_name."%'";
        }
        $adv_name = I('get.adv_name');
        if($adv_name){
            $where .= " and c.name like '%".$adv_name."%'";
        }
        $sale_name = I('get.sale_name');
        if($sale_name){
            $where .= " and e.real_name like '%".$sale_name."%'";
        }


        //数据权限
        $arr_name=array();
        $arr_name['line']=array('b.bl_id');
        $arr_name['user']=array('b.saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where.= " && $myrule_data";


        $ml = M('daydata');
        $resData = $ml->field('b.`name`,c.`name` as adv_name,e.real_name,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney,sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) as newmoney')
        ->join("a
JOIN boss_product b ON a.comid=b.id AND b.cooperate_state=2
LEFT JOIN boss_user e ON e.id=b.saler_id
LEFT JOIN boss_advertiser c ON c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=a.jfid and d.adddate=a.adddate")
        ->where($where)->group('b.id')->order('sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) desc')->limit(10)->select();
        $newmoney = 0;$whk_money = 0;$out_newmoney = 0;
        foreach($resData as $val){
            $newmoney = $newmoney+$val['newmoney'];
            $whk_money = $whk_money+$val['whk_money'];
            $out_newmoney = $out_newmoney+$val['out_newmoney'];
        }
        $sum_count = array('newmoney'=>$newmoney,'whk_money'=>$whk_money,'out_newmoney'=>$out_newmoney);
        $this->assign('sum_count',$sum_count);

        $subQuery = $ml->field('b.`name`,c.`name` as adv_name,e.real_name,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney,sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) as newmoney')
            ->join("a
JOIN boss_product b ON a.comid=b.id AND b.cooperate_state=2
LEFT JOIN boss_user e ON e.id=b.saler_id
LEFT JOIN boss_advertiser c ON c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=a.jfid and d.adddate=a.adddate")
            ->where($where)->group('b.id')->buildSql();
        $this->totalPage = $top_count = $ml->table($subQuery.' aa')->where()->count();
        $this->assign('top_count',$top_count);
        return $resData;
    }
}