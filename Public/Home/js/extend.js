/**
 * [E description]
 * @type {Object}
 */
var E={
	init:function(){
		setTimeout(this.lazyLine(),1000);this.choseDepart();this.initInput();this.lazyUser();
	},
	lazyUser:function(){
		$.get("/Home/InteSystem/lazyUser",function(data){
			var h='',h_xx="";
			$.each(data,function(i,o){
				var sel = _defult.need_user==o.real_name?"selected='selected'":"";
				h+='<option value="'+o.real_name+'" '+sel+'>'+o.real_name+'</option>';
				//加载信息收集者
				var sel_ = _defult.create_uid==o.id?"selected='selected'":"";
				h_xx += '<option value="'+o.id+'" '+sel_+'>'+o.real_name+'</option>';
			});
			$('#need_user').html(h);
			$('#need_user').selectpicker();
			//加载信息收集者
			$('#create_uid').html(h_xx);
			$('#create_uid').selectpicker();

		});
	},
	lazyLine:function(){
		$.get("/Home/InteSystem/loadBusinessLine",function(data){
			var h='';
			$.each(data,function(i,o){
				var sel = _defult.line_id==o.id?"selected='selected'":"";
				h+='<option value="'+o.id+'" '+sel+'>'+o.name+'</option>';
			});
			$('#line_id').html(h);
			$('#line_id').selectpicker();
		});
	},
	initInput:function(){
		$(".items").each(function(){
			var t=$(this);
			t.focusout(function(){
				if($(this).val()!="") $(this).css({"border":"1px solid #d8d8d8"});
			});
		});
	},
	choseDepart:function(){
			jQuery.browser={};(function(){jQuery.browser.msie=false; jQuery.browser.version=0;if(navigator.userAgent.match(/MSIE ([0-9]+)./)){ jQuery.browser.msie=true;jQuery.browser.version=RegExp.$1;}})();
			var setting = {
				check: {
						enable: true
					},
					data: {
						simpleData: {
							enable: true,
							idKey: "id",
							pIdKey: "pid",
							rootPId: 0
						}
					}
				},treeObj;
			$("#choseDepart").on("click",function(){
				var extid = $("#extid").val();
				$("#choseDepart").val("加载中...");
				$.get("/Home/InteSystem/getJsonTrees",{eid:extid},function(zNodes){
					treeObj = $.fn.zTree.init($("#treeDepart"), setting, zNodes);
					treeObj.expandAll(true);
					$(".authorizationDialog").dialog("open");
					$("#choseDepart").val("选择部门");
				});
			});

			$(".authorizationDialog").dialog({
				autoOpen: false,
				resizable: false,
				width: "300",
				height: "300",
				modal: false,
				show: "scale",
				buttons: {
					"确定":function() {
						var thisDialog = this;
						var chkNodes = treeObj.getCheckedNodes(true);
						// console.log(chkNodes);
						var chkIds = [],chkNames=[];
						if (chkNodes) {
							for(var obj in chkNodes) {
								// if(chkNodes[obj].isParent!=true){
								if(chkNodes[obj].pid=="171"||chkNodes[obj].pid=="172"||chkNodes[obj].pid=="173"||chkNodes[obj].pid=="174"||chkNodes[obj].pid=="175"||chkNodes[obj].id=="171"||chkNodes[obj].id=="172"||chkNodes[obj].id=="173"||chkNodes[obj].id=="174"||chkNodes[obj].id=="175" ){
									chkIds.push(chkNodes[obj].id);
									chkNames.push(chkNodes[obj].name);
								}
							}
						}
						if(chkNames){
							var h="",departs_ids="";
							$.each(chkNames,function(o,v){
								h+=v+",";
							});
							$.each(chkIds,function(o,v){
								if(v=="") departs_ids += ","+v+",";
								else departs_ids += v+",";
							});
							if(h==""){
								layer.alert("你没选中任何部门");
							}else{
								h = h.substr(0,h.length-1);
								$("#depart_names").val(h);
								$(this).dialog("close");
								$("#depart_id").val(departs_ids);
							}
						}
					},
					"取消":function(){
						$(this).dialog("close");
					}
				},
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
					E.subForm();
				}
			}else{
				layer.msg("红色方框为必填项");
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
					if(t.hasClass("require")){
						t.css({"border":"1px solid red"});
						back_params.isPassVali=false;
					}else{
						t.css({"border":"1px solid #1269cc"});
					}
					
				}
			});
			return back_params;
	},
	subForm:function(){
		$("#btnsave").attr("onclick","");
		$("#btnsave").val("提交中,请不要关闭浏览器....");
		var jsdata = $("#dataForm").serialize();
		//添加检查用户名和pinyin，身份证id是否存在
		var extid = $("#extid").val();
		// layer.msg("ok");
		$.post("/Home/InteSystem/saveInfo.html",jsdata,function(data){
			if(data.code==200){
				layer.alert(data.msg);
				window.location.href="/Home/InteSystem/extendCustomer.html";
			}else{
				layer.alert(data.msg);
			}
		});
	},
};
$(function(){E.init();});