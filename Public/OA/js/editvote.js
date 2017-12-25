/**
 * [Box description]
 * @type {Object}
 */
var problemsIndex=100,problems_itemIndex=100;
var Box ={
		init:function(){
			this.vtypeClick();this.addProblems();this.choseDepart();this.initUpBtn();
		},
		initUpBtn:function(){
			$(".upbtn").each(function(){
				var t=$(this),index=t.attr("data-i"),pid=t.attr("data-pid");
				Box.initUp("item_img_url_"+pid+"_"+index);
			});
		},
		delProblems:function(p_count){
			Box.delWt("您确定要删除？",p_count);
			$(".ui-widget-overlay").remove();
		},
		delWt:function(msg,pid){
			var thisDialog = $('.opennewpage');
			$('.opennewpage').html(msg);
			$(".opennewpage").dialog({
				autoOpen: false,
				resizable: false,
				modal: true,
				buttons: {
					"确定":function() {
						Box.delwenti(pid);$(this).dialog("close");
					},
					"取消":function(){
						$(this).dialog("close");
					}
				},
			});		
			$(".opennewpage").dialog("open");
			return thisDialog;
		},
		delwenti:function(pid){
			$("#problems_item_"+pid).remove();
			Box.reloadHtml();
			Box.initUpBtn();
			$(".ui-widget-overlay").remove();
		},
		reloadHtml:function(){
			$(".problems_item").each(function(i,o){
				var t    =$(this),n=0,n=i+1;
				var reg  = /问题(\d*)】/;
				var ht_t = t.find(".p_title").html();
				ht_t     = ht_t.replace(reg,'问题'+n+"】");
				t.find(".p_title").html(ht_t);
				Box.vtypeClick();
			});
		},
		addProblems:function(){
			$("#addProblems").on("click",function(){
			var html_,p_count_ = $(".problems_item").length;
			problemsIndex++;
			var p_count = problemsIndex;
			p_count_++;
			html_ = '<div class="voteQuestion problems_item" data-index="'+p_count+'" id="problems_item_'+p_count+'">'
			+'<p class="voteQuesCon"><font class="p_title">【问题'+p_count_+'】</font><span class="sc-went" onclick="Box.delProblems('+p_count+')">【删除问题】</span></p><p class="voteQuesCon"><label for="">投票问题</label><input  placeholder="请输入投票问题"   id="problems_title_'+p_count+'" name="problems_title['+p_count+'][titlename]" type="text"></p>'
			+'<p class="voteQuesCon voteRadio">'
			+'<label class="voteLabel" style="width:90px;"  for="">问题类型</label>'
			+'<span><input id="problems_type_'+p_count+'" name="problems_type_'+p_count+'[]" value="0" onclick="Box.pTypeChose(0,'+p_count+')" checked="checked"  type="radio" >&nbsp;&nbsp;单选</span>'
			+'<span><input id="0problems_type_'+p_count+'" name="problems_type_'+p_count+'[]" value="1" onclick="Box.pTypeChose(1,'+p_count+')" type="radio">&nbsp;&nbsp;多选</span>'
			+'<span><input id="1problems_type_'+p_count+'" name="problems_type_'+p_count+'[]" value="2"  onclick="Box.pTypeChose(2,'+p_count+')" type="radio">&nbsp;&nbsp;文本输入框</span>'
			+'</p>'
			+'<p class="voteQuesCon voteRadio" id="a_item_type_'+p_count+'">'
			+'<label for="">选项类型</label>'
			+'<span>'
			+'<input id="item_type_'+p_count+'" name="item_type_'+p_count+'" data-pid="'+p_count+'" value="0" class="item_type" checked="checked" type="radio" >&nbsp;&nbsp;文字</span><span><input data-pid="'+p_count+'" type="radio" id="0item_type_'+p_count+'" name="item_type_'+p_count+'" class="item_type" value="1" >&nbsp;&nbsp;图片</span></p>'
			+'<div class="voteQuesCon" id="b_item_type_'+p_count+'">'
			+'<div class="probles_items" id="wz_items_'+p_count+'">'
			+'<div class="xx-wz"  data-i="1"><label for="" class="wd90">选项标题</label><input type="text"  placeholder="请输入投票选项标题"   id="item_title_'+p_count+'_1" name="item_title_'+p_count+'[]" class="xx-pt items " data-pid="'+p_count+'"/></div>'
			+'</div>'
			+'<div id="img_items_'+p_count+'" class="disnone probles_items">'
			+'<div class="xx-wz" data-i="1">'
			+'<label for="" class="wd90">选择图片</label><input type="button" data-pid="'+p_count+'" id="item_img_url_'+p_count+'_1" name="item_img_url_'+p_count+'[]"  class="com-btn items" value="选择图片" /><img src="" class="disnone imgxz">'
			+'<input type="hidden" id="hidden_item_img_url_'+p_count+'_1" name="hidden_item_img_url_'+p_count+'[]" class="wd100" data-pid="'+p_count+'"/>'
			+'</div></div><input type="button" class="addItems com-btn " data-pid="'+p_count+'"  onclick="Box.addItems(this)" value="增加选项" />'
			+'</div><p class="voteQuesCon voteRadio" id="c_item_type_'+p_count+'">'
			+'<label class="voteLabel"   style="width:120px;"  for="">是否可自定义选项</label>'
			+'<span>'
			+'<input type="radio" id="problems_is_custom_'+p_count+'" name="problems_is_custom_'+p_count+'[]" value="0" checked="checked">&nbsp;&nbsp;可以'
			+'</span><span><input type="radio" id="0problems_is_custom_'+p_count+'" name="problems_is_custom_'+p_count+'[]" value="1">&nbsp;&nbsp;不可以'
			+'</span></p></div>';

			$(".problems_list").append(html_);Box.vtypeClick();
			});
		},
		addItems:function(this_){
			var t = 0,pid = 0,th=$(this_);
			pid=th.attr("data-pid");
			t = $("input[name='item_type_"+pid+"']:checked").val();
			var html_="",lth=problems_itemIndex;
			lth++;
			problems_itemIndex = lth;
			switch(t){
				case "1"://图片
					html_ = '<div class="xx-wz"  data-i="'+lth+'">'
							+'<label for="" class="wd90">选择图片</label>'
							+'<input type="button" data-pid="'+pid+'" id="item_img_url_'+pid+'_'+lth+'" name="item_img_url_'+pid+'[]"  class="com-btn items" value="选择图片" /><img src="" class="disnone imgxz"><input type="button" class="com-btn" onclick="Box.delImgItems(this)" data-pid="'+pid+'" value="删除图片选项" style="margin-left:8px;"/>'
							+'<input type="hidden" id="hidden_item_img_url_'+pid+'_'+lth+'" name="hidden_item_img_url_'+pid+'[]" class="wd100" data-pid="'+pid+'"/>'
							+'</div>';
				break;
				case "0"://文字
					html_ = '<div class="xx-wz" data-i="'+lth+'">'
							+'<label for="" class="wd90">选项标题</label>'
							+'<input placeholder="请输入投票选项标题"  type="text" id="item_title_'+pid+'_'+lth+'" name="item_title_'+pid+'[]" class="xx-pt items " data-pid="'+pid+'" style="width:325px !important;"/><input type="button" class="com-btn" onclick="Box.delWzItems(this)" data-pid="'+pid+'" value="删除选项" style="margin-left:8px;"/></div>';
				break;
			}
			if(t=="0") {
				$("#wz_items_"+pid).append(html_);
			}else {
				$("#img_items_"+pid).append(html_);
				// console.log("item_img_url_"+pid+"_"+lth);
				Box.initUp("item_img_url_"+pid+"_"+lth);
			}
		},
		delImgItems:function(obj){
			var pid=$(obj).attr("data-pid");
			$(obj).parent().remove();
			Box.vtypeClick();
		},
		delWzItems:function(obj){
			var pid=$(obj).attr("data-pid");
			$(obj).parent().remove();
			Box.vtypeClick();
		},
		vtypeClick:function(){
			$(".item_type").on("click",function(){
				var t = $(this),pid;
				var v=t.val(),pid=t.attr("data-pid");
				if(v=="0") {
					$("#img_items_"+pid).hide();$("#wz_items_"+pid).show();
				}else {
					$("#img_items_"+pid).show();$("#wz_items_"+pid).hide();
					Box.initUp("item_img_url_"+pid+"_1");
				}
				
				
			});
		},
		pTypeChose:function(type,p_id){
			if(type==2){
				$("#a_item_type_"+p_id).hide();
				$("#b_item_type_"+p_id).hide();$("#c_item_type_"+p_id).hide();
			}else{
				$("#a_item_type_"+p_id).show();
				$("#b_item_type_"+p_id).show();$("#c_item_type_"+p_id).show();
			}
		},
		initUp:function(id){
			var button = $('#'+id);
			new AjaxUpload(button,{
				action: "/Home/Public/uploadss.html",
				name: 'files',
				onSubmit: function (file, ext) {
					if (!(ext && /^(jpg|jpeg|JPG|JPEG|png)$/.test(ext))) {
						layer.alert("图片格式不正确,请选择jpg|jpeg|JPG|JPEG|png格式的文件!");
						return false;
					}
				},
				onComplete: function(file, response){
					var list=eval("("+response.replace(/<\/?[^>]*>/g,'')+")");
					if(list.data){
						$('#hidden_'+id).val(list.data);
						button.next().show().attr("src",list.data);
					}
					layer.msg(list.msg);
				}
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
				var vid = $("#vid").val();
				$.get("/OA/Vote/getJsonTrees",{eid:vid},function(zNodes){
					treeObj = $.fn.zTree.init($("#treeDepart"), setting, zNodes);
					treeObj.expandAll(true);
					$(".authorizationDialog").dialog("open");
				});
			});

			$(".authorizationDialog").dialog({
				autoOpen: false,
				resizable: false,
				width: "500",
				height: "600",
				modal: false,
				show: "scale",
				buttons: {
					"确定":function() {
						var thisDialog = this;
						var chkNodes = treeObj.getCheckedNodes(true);
						var chkIds = [],chkNames=[];
						if (chkNodes) {
							for(var obj in chkNodes) {
								chkIds.push(chkNodes[obj].id);
								chkNames.push(chkNodes[obj].name);
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
								$("#departs").val(h);
								$(this).dialog("close");
								$("#departs_ids").val(departs_ids);
							}
						}
					},
					"取消":function(){
					$(this).dialog("close");
					}
				},
			});
		},
		sendVote:function(){
			var vname       =$("#vname").val();
			var start_time  =$("#start_time").val();
			var end_time    =$("#end_time").val();
			var instruct    =$("#instruct").val();
			var departs_ids =$("#departs_ids").val();
			var attention   =$("#attention").val();
			var params = {
				vname:vname,
				start_time:start_time,
				end_time:end_time,
				instruct:instruct,
				departs_ids:departs_ids,
				attention:attention
			};
			if(Box.checkSend(params)){
				var jsdata = $("#voteForm").serialize();
				$("#sendVote").val("保存中...");
				$("#sendVote").attr("onclick","");
				$.post("/OA/Vote/saveEditeData.html",jsdata,function(data){
					layer.alert(data.msg);
					$("#sendVote").val("保存投票信息");
					$("#sendVote").attr("onclick","Box.sendVote()");
					window.location.href="/OA/Vote/voteList.html";
				});
			}
		},
		checkSend:function(params){
			if(params.vname==""){
				layer.alert("请输入投票名称");
				$("#vname").focus().css({"border":"1px solid red"});
				return false;
			}
			if(params.start_time==""){
				layer.alert("请输入投票开始时间");
				$("#start_time").focus();
				return false;
			}
			if(params.end_time==""){
				layer.alert("请输入投票结束时间");
				$("#end_time").focus();
				return false;
			}

			if(params.instruct==""){
				layer.alert("请输入投票须知");
				$("#instruct").focus();
				return false;
			}
			if(params.attention==""){
				layer.alert("请输入注意事项");
				$("#attention").focus();
				return false;
			}
			if(params.departs_ids==""){
				layer.alert("请选择投票部门");
				$("#departs").focus();
				return false;
			}
			return true;
		}
	};
$(function(){Box.init();});