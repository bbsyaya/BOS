var E={
    init:function(){
        // setTimeout(this.lazyLine(),1000);
        // if(pType!=undefined && pType!="supply"){
            var v=$("#need_follow_products").val();
            if(v!=200){
                setTimeout(this.checkYestodayCompareToday(),1000);
            }
        // }else{

        // }
    },
    lazyLine:function(){
        $.get("/Home/InteSystem/loadBusinessLine",function(data){
            var h='<option value="" >全部</option>';
            $.each(data,function(i,o){
                var se=d_.line_id==o.id?"selected='selected'":"";
                h+='<option value="'+o.id+'" '+se+'>'+o.name+'</option>';
            });
            $('#line_id').html(h);
            $('.selectpicker1').selectpicker();
        });
    },
    expand:function(id,type_id){
        layer.open({
          type: 2,
          title: '我来跟进',
          shadeClose: true,
          shade: 0.8,
          area: ['800px', '70%'],
          content: '/Home/InteSystem/expand.html?extid='+id+"&type_id="+type_id //iframe的url
        }); 
    },
    checkYestodayCompareToday:function(){
        layer.open({
            title: '检查中',
            type: 1
            ,offset: 'rb' //具体配置参考：offset参数项
            ,area: ['400px', '200px']
            ,content: '<div id="fmsg" style="text-align:center;margin-top:10%;width:100%;display:inline-block;font-size:14px;color:red;">加载中....</div>'
            ,shade: 0 //不显示遮罩
            ,yes: function(){
                layer.closeAll();
            }
        });
        var url='/Home/InteSystem/checkYestodayCompareToday.html';
        var link='/Home/InteSystem/cooperAdver.html';
        if(pType=="supply"){
            url="/Home/InteSystem/checkYestodayCompareTodaySupply.html";
            link='/Home/InteSystem/cooperSupply.html';
        }
        var link_url='<a href="'+link+'?need_follow_products=200" style="text-align:center;width:100%;display:inline-block;font-size:14px;color:red;">有流水比前一天数据存在正负30%的波动的产品，点击查看</a>'
        $.get(url,function(data){
            if(data.code==200){
                $("#fmsg").html(link_url);
            }else{
                layer.closeAll();
            }
        });
        // $.get("/Home/InteSystem/checkYestodayCompareToday.html",function(data){
        //     if(data.code==200){
        //         var h='<a href="javascript:void(0);" onclick="E.postQuery(\''+data.product_ids+'\')" style="text-align:center;width:100%;display:inline-block;font-size:14px;color:red;">有流水比前一天数据存在正负30%的波动的产品，点击查看</a>';
        //         $("#fmsg").html(h);
        //     }else{
        //         layer.closeAll();
        //     }
        // });
    },
    postQuery:function(product_ids){
        var u='<form method="post" action="/Home/InteSystem/cooperAdver.html" style="display:none;" id="post_"><input type="hidden" value="'+product_ids+'" name="product_ids"/><input type="hidden" name="need_follow_products" value="200"/></form>';
        $("body").append(u);
        $("#post_").submit();
    }
    
}
$(function(){E.init();});