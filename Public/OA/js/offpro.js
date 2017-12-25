jQuery.browser={};(function(){jQuery.browser.msie=false; jQuery.browser.version=0;if(navigator.userAgent.match(/MSIE ([0-9]+)./)){ jQuery.browser.msie=true;jQuery.browser.version=RegExp.$1;}})();
var addDialogData = '';
var box={
    delalloff:function(obj){
        //删除选中的办公用品
        layer.confirm('您确定要删除选中的办公用品吗？', {
            btn: ['确定','取消'], //按钮
            yes: function(){
               var ids = box.getChoseId();
               if(ids){
                    box.delOfficDo(ids);
               }else{
                 layer.alert("需勾选要删除的办公用品");
               }
               
            }
        });
    },
    getChoseId:function(){
        var ids = "";
        $(".ckitem").each(function(){
            if(this.checked){
               ids+=$(this).val()+",";
            }
        });
        if(ids){
            ids = ids.substr(0,ids.length-1);
        }
        return ids;
    },
    sendApply:function(pid){
        $("#apply_no").val("");
        $("#product_id").val(pid);
        box.showBox();
    },
    showBox:function(){
        var w    =$(window),wd=w.width(),ht=w.height(),wd_c=350,ht_c=200,obj=$(".popupPublic1");
        var mg_w =((wd-wd_c)/2)/wd*100;
        var mg_h =((ht-ht_c)/2)/ht*100;
        obj.css({"left":mg_w+"%","top":mg_h+"%",});
        $(".mask").show();
        obj.fadeIn();
    },
    close:function(){
        $(".mask").hide();
        $(".popupPublic1").hide();
    },
    saveApply:function(){
        var params={};
        params.apply_no   = $("#apply_no").val();
        params.product_id = $("#product_id").val();
        params.price      = $("#tr_"+params.product_id).attr("data-price");
        if(params.apply_no){
            if(parseFloat(params.apply_no)>0){
                var o = $("#addproduct");
                o.val("提交中...");
                o.attr("onclick","");
                $.post("/OA/Office/addApply.html",params,function(data){
                    o.val("提交");
                    o.attr("onclick","C.addOffice()");
                    layer.alert(data.msg);
                    box.close();
                    window.location.href="/OA/office/applyList.html";
                });
            }else{
                $("#apply_no").val("1");
            }
        }else{
            $("#apply_no").focus();
        }
    },
    addPro:function(obj){
        $(obj).html("加载中...");
        if (!addDialogData) {
            $.get("/OA/Office/add",function(dom){
                addDialogData = dom;
                $(".addDataDialog").html(addDialogData);
                $(".addDataDialog").dialog({
                    width:400
                });
                $(obj).html("新增办公用品");
            });
        } else {
            $(".addDataDialog").html(addDialogData);
            $(".addDataDialog").dialog();$(obj).html("新增办公用品");
        }
    },
    saveData:function(obj){
        var params = {};
        params.officename = $("#officename").val();
        params.format     = $("#format").val();
        params.price      = $("#price").val();
        params.stock      = $("#stock").val();
        params.remark     = $("#remark").val();
        params.unit     = $("#unit").val();
        params.id     = $("#pid").val();
        if(box.checkAddOffice(params)){
            var o = $(obj);
            o.val("提交中...");
            o.attr("onclick","");
            $.post("/OA/Office/addProduct.html",params,function(data){
                o.val("提交");
                o.attr("onclick","C.addOffice()");
                layer.alert(data.msg);
                if(data.code==200){
                    $(".ui-widget-overlay").remove();
                     window.location.href="/OA/office/productList";
                }
            });
        }
        $(".ui-widget-overlay").remove();
    },
    checkAddOffice:function(params){
        if(params.officename==""){
            layer.alert("请填写办公用品名称");
            $("#officename").focus();
            return false;
        }
        if(params.format==""){
            layer.alert("请填写办公用品规格");
            $("#format").focus();
            return false;
        }
        if(params.unit==""){
            layer.alert("请填写单位");
            $("#unit").focus();
            return false;
        }
        if(params.price==""){
            layer.alert("请填写办公用品单价");$("#price").focus();
            return false;
        }
        if(params.stock==""){
            layer.alert("请填写办公用品库存");$("#stock").focus();
            return false;
        }
        return true;
    },
    editOffic:function(id){
        $("#editOffic_"+id).val("加载中...");
        $.get("/OA/Office/editProduct.html",{id:id},function(dom){
            $(".addDataDialog").html(dom);
            $(".addDataDialog").dialog({width:400});
            $("#editOffic_"+id).val("编辑");
        });
    },
    delOffic:function(id){
        layer.confirm('您确定要删除选中的办公用品吗？', {
            btn: ['确定','取消'], //按钮
            yes: function(){
               box.delOfficDo(id);
            }
        });
    },
    delOfficDo:function(id){
        layer.msg('删除中...')
        $.post("/OA/Office/delProduct.html",{id:id},function(data){
            layer.alert(data.msg,function(){
                window.location.reload();
            });
        });
    },
    initImprot:function(){
        var button = $('#importPro');
        new AjaxUpload(button,{
            action: "/OA/Office/importDatado.html",
            name: 'files',
            onSubmit:function(){button.html("导入中...");},
            onComplete: function(file, response){
                var list=eval("("+response.replace(/<\/?[^>]*>/g,'')+")");
                if(list.logdata){
                    box.createForm(list.logdata);
                }
                window.location.reload();
            }
        });
    },
    init:function(){
        this.initImprot();this.checkAll();
    },
    checkAll:function(){
        $("#checkall").on("click",function(){
            var f=this.checked;
            $(".ckitem").prop("checked",f);
        });
    },
    createForm:function(logdata){
        var html_="<form id=\"logForm\" method='post' target=\"_blank\" action=\"/Public/showLog.html\"><input type=\"hidden\" name=\"datalog\" value=\""+logdata+"\"/></form>";
        $("body").append(html_);
        $("#logForm").submit();
    }
};
$(function(){box.init();});