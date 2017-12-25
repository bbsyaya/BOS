<?php
/**
 * Created by PhpStorm.
 * User: h
 * Date: 2017-07-20
 * Time: 16:20
 * 本地验签
 */
include 'demo.php';
$data = "account_date=20170720&fee=0.01&notify_time=2017-07-20 16:37:34&notify_type=ysepay.ds.single.notify&out_trade_no=20170720163707HMYS18930987427&sign_type=RSA&total_amount=100.00&trade_no=101170720196458358&trade_status=TRADE_SUCCESS&trade_status_description=交易成功";
$sign = "QMqcyLaM/7bjzL1cRZfQHTsmpsj8ujhTjty5GgZytkcM2fqh7XRryCnPRcawhexFCl2mi40XVjgAVeHYDg4Jx4OoPeP752mAxTWYqq4hPopq8HiKZrSVclUvWkLrJbvm4d3VXfkzRZF8Gn5TQPjsx5bStyq4RYR0MnSkiaRSZPs=";
if( $s->sign_check($sign,$data)==true){
    echo "验签成功";
}else{
    echo "验证失败";
}