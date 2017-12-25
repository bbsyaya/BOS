/**
 * [F description]
 * @type {Object}
 */
"use strict";
var C={
	pageType: "",
	init:function(){
		this.pageSwitch();
	},
	 pageSwitch: function() {
        if ("undefined" != typeof pType) {
            C.pageType = pType
        }
        C._publiFun();
        C.pageComFun();
        switch (C.pageType) {
	        case "applyList":
                C.addPro();C.sendPro();C.checkAll();C.delAll();C.initImprot();
	        break;
	        case "entry":
                C.importEntry();
	        break;
        }
    },
    importEntry:function(){
    	var button = $('#importPro');
		new AjaxUpload(button,{
			action: "/OA/HrManage/importEntrydo.html",
			name: 'files',
			onSubmit:function(){
				button.html("导入中...");
			},
			onComplete: function(file, response){
				var list=eval("("+response.replace(/<\/?[^>]*>/g,'')+")");
				if(list.logdata){
					// C.createForm(list.logdata);
					layer.open({
		              type: 2,
		              title: '导入结果',
		              shadeClose: true,
		              shade: 0.8,
		              area: ['800px', '80%'],
		              content: '/Home/Public/showLog.html'//iframe的url
		            }); 
				}
				// layer.alert(list.msg);
				button.html("导入当月特殊工资");
				// window.location.reload();
			}
		});
    },
    initImprot:function(){
    	var button = $('#importPro');
		new AjaxUpload(button,{
			action: "/OA/Office/importDatado.html",
			name: 'files',
			onSubmit:function(){
				button.html("导入中...");
			},
			onComplete: function(file, response){
				var list=eval("("+response.replace(/<\/?[^>]*>/g,'')+")");
				if(list.logdata){
					C.createForm(list.logdata);
				}
				layer.alert(list.msg);
				button.html("导入办公用品表");
			}
		});
	},
	createForm:function(logdata){
		var html_="<form id=\"logForm\" method='post' target=\"_blank\" action=\"/Public/showLog.html\"><input type=\"hidden\" name=\"datalog\" value=\""+logdata+"\"/></form>";
		$("body").append(html_);
		$("#logForm").submit();
	},
    changeStatus:function(id){
    	//发放
    	C.checkChange("你确定发放？",id);
    	$(".ui-widget-overlay").remove();
    },
    checkOk:function(id){
		var params={"id":id};
		$.post("/OA/Office/updateStatus.html",params,function(data){
			layer.alert(data.msg);
			window.location.reload();
		});
    },
    checkChange:function(msg,pid){
		var thisDialog = $('.opennewpage');
		$('.opennewpage').html(msg);
		$(".opennewpage").dialog({
			autoOpen: false,
			resizable: false,
			modal: true,
			buttons: {
				"确定":function() {
					C.checkOk(pid);
					$(this).dialog("close");
				},
				"取消":function(){
					$(this).dialog("close");
				}
			},
		});		
		$(".opennewpage").dialog("open");
		return thisDialog;
	},
    delAll:function(){

    	$(".delAll").on("click",function(){

    		var params={};
			params.ids=C.getAllChecked();

			if(!params.ids){

    			layer.alert("请勾选要删除的申领记录");
    			return false;
    		}
    		C.delAllConfirm("你确定要删除选中的？",params);
    		$(".ui-widget-overlay").remove();
    	});
    },
    delAllOk:function(params){
    	$.post("/OA/Office/deleteItem.html",params,function(data){
    		layer.alert(data.msg);
			window.location.reload();
		});
    },
    delAllConfirm:function(msg,params){
    	var thisDialog = $('.opennewpage');
		$('.opennewpage').html(msg);
		$(".opennewpage").dialog({
			autoOpen: false,
			resizable: false,
			modal: true,
			buttons: {
				"确定":function() {
					C.delAllOk(params);
					$(this).dialog("close");
				},
				"取消":function(){
					$(this).dialog("close");
				}
			},
		});		
		$(".opennewpage").dialog("open");
		return thisDialog;
    },
    checkAll:function(){
    	$("#checkall").on("click",function(){
    		var f=this.checked;
    		$(".ckitem").prop("checked",f);
    	});
    },
    applyAll:function(){
    	$(".applyAll").on("click",function(){
    		var ids,params={};
    		params.ids = C.getAllChecked(true);
    		if(!params.ids){
    			layer.alert("请选择项目或者没有需要采购的");return false;
    		}
    		C.applyAllConfirm("你确定已采购完成？",params);
    		$(".ui-widget-overlay").remove();
    	});
    },
    applyOk:function(params){
		$.post("/OA/Office/updateStock.html",params,function(data){
			layer.alert(data.msg);
			window.location.reload();
		});
    },
    applyAllConfirm:function(msg,params){
    	var thisDialog = $('.opennewpage');
		$('.opennewpage').html(msg);
		$(".opennewpage").dialog({
			autoOpen: false,
			resizable: false,
			modal: true,
			buttons: {
				"确定":function() {
					C.applyOk(params);
					$(this).dialog("close");
				},
				"取消":function(){
					$(this).dialog("close");
				}
			},
		});		
		$(".opennewpage").dialog("open");
		return thisDialog;
    },
    getAllChecked:function(is_check_apply){
    	var ids = "";
    	$(".ckitem").each(function(){
    		if(this.checked){
    			if(is_check_apply==true){
    				var s = $(this).attr("data-status");
    				if(s=="2")ids+=$(this).val()+",";
    			}else{
    				ids+=$(this).val()+",";
    			}
    		}
    	});
    	if(ids){
    		ids = ids.substr(0,ids.length-1);
    	}
    	return ids;
    },
    _publiFun:function(){

    },
    pageComFun:function(){

    },
	addPro:function(){
		$(".addPro").on("click",function(){
			$(".xz-sec").show();
		});
	},
	sendPro:function(){
		$(".sendPro").on("click",function(){
			var par={};
			par.name   = $("#name").val();
			par.format = $("#format").val();
			par.price  = $("#price").val();
			par.stock  = $("#stock").val();
			par.remark = $("#remark").val();
			if(C.checkPro(par)){
				$.post("/Office/addProduct.html",par,function(data){
					layer.alert(data.msg);
				});
			}
		});
	},
	checkPro:function(par){
		if(!par.name){
			$("#name").focus();return false;
		}
		if(!par.format){
			$("#format").focus();return false;
		}
		if(!par.price){
			$("#price").focus();return false;
		}
		if(!par.stock){
			$("#stock").focus();return false;
		}
		if(!par.remark){
			$("#remark").focus();return false;
		}
		return true;
	},
	finishTake:function(id){
		var params={
			ids:id
		};
		C.applyAllConfirm("你确定已采购完成？",params);
		$(".ui-widget-overlay").remove();
	},
	addOffice:function(){
		var params = {};
		params.officename = $("#officename").val();
		params.format     = $("#format").val();
		params.price      = $("#price").val();
		params.stock      = $("#stock").val();
		params.remark     = $("#remark").val();
		if(C.checkAddOffice(params)){
			var o = $("#addproduct");
			o.val("提交中...");
			o.attr("onclick","");
			$.post("/OA/Office/addProduct.html",params,function(data){
				o.val("提交");
				o.attr("onclick","C.addOffice()");layer.alert(data.msg);
			});
		}

	},
	checkAddOffice:function(params){
		if(params.officename==""){
			layer.alert("请填写办公用品名称");
			$("#officename").focus();
			return false;
		}
		if(params.format==""){
			layer.alert("请填写办公用品规格");$("#format").focus();
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
	}

};
$(function(){C.init();});