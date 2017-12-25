var E={
    init:function(){
        // setTimeout(this.lazyLine(),1000);
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
    delExpand:function(id){
        // layer.confirm("您确定要删除该记录，删除之后系统无法找回，请慎重考虑",function(){
        //     $.post("/Home/InteSystem/delExpand.html",{id:id},function(data){
                
        //     });
        // });
    },
    showAllInfo:function(id,ids_tag){
        var obj     = $(ids_tag);
        var msg     = obj.attr("data-msg");
        var title   = obj.attr("data-title");
        var posturl = obj.attr("data-url");
        var status = obj.attr("data-status");
        if(status==200){
            //当前用户-可编辑
            layer.prompt({title: title, formType: 2,value:msg,area: ['500px', '300px']}, function(text, index){
                var param={};
                param.val = text;
                param.id = id;
                $.post(posturl,param,function(data){
                    layer.close(index);
                    layer.msg('修改成功');
                    window.location.reload();
                });
            });
        }else{
            //仅查看
            layer.msg(msg, {time: 5000});
        }
    }
}
$(function(){E.init();});