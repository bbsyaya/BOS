<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
 <head> 
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
  <title></title>  
  <link rel="stylesheet" type="text/css" href="/Public/static/dialog/css/xcConfirm.css?v=788" /> 
 </head>
 <body>
  <div class="xcConfirm">
   <div class="xc_layer"></div>
   <div class="popBox">
    <div class="ttBox">
     <span class="tt">信息</span>
    </div>
    <div class="txtBox">
     <div class="bigIcon" style="background-position: 0px 0px;"></div>
     <p>
        <?php if(isset($message)) {?>
            <?php echo($message); ?>
        <?php }else{?>
            <?php echo($error); ?>
        <?php }?>
        <br/>
        页面自动 <a id="href" href="<?php echo($jumpUrl); ?>">跳转</a> 等待时间： <b id="wait"><?php echo($waitSecond); ?></b>
     </p>
    </div>
   </div>
  </div> 
<script type="text/javascript">
(function(){
var wait = document.getElementById('wait'),href = document.getElementById('href').href;
var interval = setInterval(function(){
var time = --wait.innerHTML;
if(time <= 0) {
    location.href = href;
    clearInterval(interval);
};
}, 1000);
})();
</script>
 </body>
</html>