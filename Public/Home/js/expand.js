var E={
    init:function(){
        this.initInput();
    },
    initInput:function(){
        $(".items").each(function(){
            var t=$(this);
            t.focusout(function(){
                if($(this).val()!="") $(this).css({"border":"1px solid #d8d8d8"});
            });
        });
    },
    saveData:function(){
        var backParams=E.beforeCheck();
        if(backParams.isPassVali){
            if(backParams.has_null>0){
                layer.confirm("您还有"+backParams.has_null+"个信息没有填写,确定提交？", {
                    btn: ['确定', '取消'],
                    yes: function(index){
                        E.subForm();
                    }
                });
            }else{
                layer.confirm("您确定要提交？", {
                    btn: ['确定', '取消'],
                    yes: function(index){
                       E.subForm();
                    }
                });
            }
        }
    },
    beforeCheck:function(){
        var back_params={
            has_null:0,
            isPassVali:true
        };
        $(".items").each(function(i,o){
            var t=$(this);
            if(t.val()==""){
                back_params.has_null++;
                t.css({"border":"1px solid #1269cc"});
            }
        });
        return back_params;
    },
    subForm:function(){
        $("#btnsave").attr("onclick","");
        $("#btnsave").val("提交中,请不要关闭浏览器....");
        var jsdata = $("#dataForm").serialize();
        $.post("/Home/InteSystem/saveFollow.html",jsdata,function(data){
            if(data.code==200){
                layer.msg(data.msg);
                window.location.reload();
            }else{
                layer.alert(data.msg);
            }
        });
    },
}
$(function(){E.init();});