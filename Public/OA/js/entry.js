/**
 * [E description]
 * @type {Object}
 */
var E={
	init:function(){
		this.lazyCompany();this.lazyDepart();this.initInput();this.lazyPosition();
	},
	saveData:function(obj){
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
				E.subForm();
			}
		}else{
			layer.alert('红色框信息为必填项', {icon: 6});
		}
	},
	initInput:function(){
		$(".items").each(function(){
			var t=$(this);
			t.focusout(function(){
				if($(this).val()!="") $(this).css({"border":"1px solid #d8d8d8"});
			});
		});
	},
	subForm:function(){
		$("#btnsave").attr("onclick","");
		$("#btnsave").val("提交中,请不要关闭浏览器....");
		var jsdata = $("#dataForm").serialize();
		//添加检查用户名和pinyin，身份证id是否存在
		var hrid = $("#hrid").val(),canSub=true;
		if(hrid==undefined){
			$.post("/OA/HrManage/checkIsRepeat.html",jsdata,function(data){
				if(data.status==501){
					layer.alert(data.msg);
					$("#user_name").css({"border":"1px solid red"}).focus();
					$("#btnsave").val("保存资料");$("#btnsave").attr("onclick","E.saveData(this)");
				}else if(data.status==502){
					
					$("#body_no").css({"border":"1px solid red"});
					$("#btnsave").val("保存资料");$("#btnsave").attr("onclick","E.saveData(this)");$("#body_no").focus();
				}else if(data.status==200){
					E.subData(jsdata);
				}else if(data.status==503){
					layer.alert(data.msg,function(){
						window.location.href="/Home/User/index.html?addbasic=200";
					});
					
				}
			});
		}else{
			E.subData(jsdata);
		}
	},
	subData:function(jsdata){
		$.post("/OA/HrManage/saveEntry.html",jsdata,function(data){
			if(data.code==200){
				layer.alert(data.msg);
				window.location.href="/OA/HrManage/hrList.html";
			}else{
				layer.alert(data.msg);
				$("#btnsave").val("保存资料");
				$("#btnsave").attr("onclick","E.saveData(this)");
			}
		});
		$(".ui-widget-overlay").remove();
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
				//重点检查几个字段，是否能提交
				if(t.attr("name")=="user_name"){
					t.css({"border":"1px solid red"});
					back_params.isPassVali=false;
					t.focus();
				}
				// if(t.attr("name")=="username_pinyin"){
				// 	t.css({"border":"1px solid red"});
				// 	back_params.isPassVali=false;t.focus();
				// }
				if(t.attr("name")=="body_no"){
					t.css({"border":"1px solid red"});
					back_params.isPassVali=false;t.focus();
				}
			}
		});
		return back_params;
	},
	lazyCompany:function(){
        setTimeout(function(){
            $.get("/OA/OrganizSetting/getCompnanys.html",function(data){
                var ht='',sec;
                $.each(data,function(i,o){
                	sec =_default.company_id==o.id?"selected":"";
                    ht+='<option value="'+o.id+'" '+sec+'>'+o.name+'</option>';
                });
                $("#company_id").html(ht);
            });
        },1000);
    },
    lazyDepart:function(){
        setTimeout(function(){
            $.get("/OA/OrganizSetting/getAllList.html",function(data){
                var ht='',sec;
                $.each(data,function(i,o){
                	sec=_default.depart_id==o.id?"selected":"";
                    ht+='<option value="'+o.id+'" '+sec+'>'+o.name+'</option>';
                });
                $("#depart_id").html(ht);
            });
        },1000);
    },
    lazyPosition:function(){
        setTimeout(function(){
            $.get("/OA/OrganizSetting/getPositonList.html",function(data){
                var ht='',sec;
                $.each(data,function(i,o){
                	sec=_default.duty==o.id?"selected":"";
                    ht+='<option value="'+o.id+'" '+sec+'>'+o.name+'</option>';
                });
                $("#duty").html(ht);
                $("#duty").selectpicker();
            });
        },1001);
    }
};
$(function(){E.init();});