var problemsIndex=1,problems_itemIndex=1;
var List = {
		init:function(){
			this.initPopupe();this.initTime();this.initDel();this.sendVote();
		},
		sendVote:function(){
			$(".sendvote").on("click",function(){
				var t=$(this),vid=t.attr("data-id");
				List.voteSure("您确定要发布？",vid);
				$(".ui-widget-overlay").remove();
			});
		},
		postVote:function(vid){
			$.post("/OA/Vote/releaseVote.html",{vid:vid},function(data){
				$(".ui-widget-overlay").remove();
				$(".ui-widget-overlay").remove();
				layer.alert(data.msg);
				$(".ui-widget-overlay").remove();
				window.location.reload();
			});
		},
		voteSure:function(msg,vid){

			var thisDialog = $('.opennewpage');
			$('.opennewpage').html(msg);
			$(".opennewpage").dialog({
				autoOpen: false,
				resizable: false,
				modal: true,
				buttons: {
					"确定":function() {
						List.postVote(vid);$(this).dialog("close");
					},
					"取消":function(){
						$(this).dialog("close");
					}
				},
			});		
			$(".opennewpage").dialog("open");
			return thisDialog;
		},
		initPopupe:function(){
			var Popupe = new PopupBase();
	        Popupe.show('.vote_btn', '.mask,.Js_vote');
	        Popupe.hide('.colse','.mask,.Js_vote');
	        List.initHt();
		},
		initHt:function(){
			var w=$(window),wht=w.height(),voteBox=$(".voteBox"),vht=voteBox.height();
			if(parseFloat(vht)>=parseFloat(wht) && parseFloat(wht)>0){
				var ht=parseFloat(wht)-100;
				voteBox.height(ht).css({"marginTop":"0px","top":"5%"});
			}
		},
		initTime:function(){
			 //时间查询
	        $('#start_one').daterangepicker({
		        locale: {
		            direction: "ltr",
		            format: "YYYY.MM.DD", //控件中from和to 显示的日期格式
		            separator: " ~ ",
		             applyLabel: "确定",
		             cancelLabel: "取消",
		             fromLabel: "起始时间",
		             toLabel: "结束时间",
		            customRangeLabel: "自定义",
		            daysOfWeek: ['日', '一', '二', '三', '四', '五', '六'],
		            monthNames: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
		            firstDay: 1
		        },
		        startDate: time.startDate,
		        endDate: time.endDate,
		        opens: "left"
		    });
		},
		initDel:function(){
			$(".delitem").on("click",function(){
				var t=$(this),vid=t.attr("data-id");
				layer.confirm('您确定要删除？', {
		            btn: ['确定','取消'], //按钮
		            yes: function(){
		               $.post("/OA/Vote/delVote.html",{vid:vid},function(data){
							layer.msg(data.msg);
							window.location.reload();
						});
		            }
		        });
				// List.delSure("",vid);
				// $(".ui-widget-overlay").remove();
			});
		}
	};
	var Box ={
			init:function(){
				this.vtypeClick();this.addProblems();this.choseDepart();
				this.initRequired();
			},
			initRequired:function(){
				$(".required").each(function(){
					var t=$(this);
					t.focusout(function(){
						if($(this).val()!="") $(this).css({"border":"1px solid #d8d8d8"});
					});
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
			},
			reloadHtml:function(){
				$(".problems_item").each(function(i,o){
					var t    =$(this),n=0,n=i+1;
					var reg  = /问题(\d*)】/;
					var ht_t = t.find(".p_title").html();
					ht_t     =ht_t.replace(reg,'问题'+n+"】");
					t.find(".p_title").html(ht_t);
					Box.vtypeClick();
				});
			},
			_getValue:function(pid){
				// var ops_ = {};
				// var pro = $("#problems_item_"+pid);
				// ops_.problems_title = pro.find("#problems_title_"+pid).val();
				// ops_.problems_type = pro.find("input[name='problems_type_"+pid+"[]']:checked").val();
				// ops_.item_type = pro.find("input[name='item_type_"+pid+"']:checked").val();
				// $.each(pro.find("#wz_items_"+pid).find(".items"),function(i,o){

				// });
				// console.log(ops_);
			},
			addProblems:function(){
				$("#addProblems").on("click",function(){
				var html_,p_count_ = $(".problems_item").length;
				problemsIndex++;
				var p_count = problemsIndex;
				p_count_++;
				html_ = '<div class="voteQuestion problems_item" data-index="'+p_count+'" id="problems_item_'+p_count+'">'
				+'<p class="voteQuesCon"><font class="p_title">【问题'+p_count_+'】</font><span class="sc-went" onclick="Box.delProblems('+p_count+')">【删除问题】</span></p>'
				+'<p class="voteQuesCon"><label for="">投票问题</label><input id="problems_title_'+p_count+'"  placeholder="请输入投票问题"  name="problems_title['+p_count+'][titlename]" type="text"></p>'
				+'<p class="voteQuesCon voteRadio">'
				+'<label class="voteLabel" style="width:90px;"  for="">问题类型</label>'
				+'<span><input id="problems_type_'+p_count+'" name="problems_type_'+p_count+'[]"  onclick="Box.pTypeChose(0,'+p_count+')"  value="0" checked="checked"  type="radio" >&nbsp;&nbsp;单选</span>'
				+'<span><input id="0problems_type_'+p_count+'" name="problems_type_'+p_count+'[]"  onclick="Box.pTypeChose(1,'+p_count+')"  value="1" type="radio">&nbsp;&nbsp;多选</span>'
				+'<span><input id="1problems_type_'+p_count+'" name="problems_type_'+p_count+'[]" value="2"  onclick="Box.pTypeChose(2,'+p_count+')" type="radio">&nbsp;&nbsp;文本输入框</span></p>'
				+'<p class="voteQuesCon voteRadio" id="a_item_type_'+p_count+'">'
				+'<label for="">选项类型</label>'
				+'<span>'
				+'<input id="item_type_'+p_count+'" name="item_type_'+p_count+'" data-pid="'+p_count+'" value="0" class="item_type" checked="checked" type="radio" >&nbsp;&nbsp;文字</span><span><input data-pid="'+p_count+'" type="radio" id="0item_type_'+p_count+'" name="item_type_'+p_count+'" class="item_type" value="1" >&nbsp;&nbsp;图片</span></p>'
				+'<div class="voteQuesCon"  id="b_item_type_'+p_count+'">'
				+'<div class="probles_items" id="wz_items_'+p_count+'">'
				+'<div class="xx-wz"  data-i="1"><label for="" class="wd90">选项标题</label><input type="text" id="item_title_'+p_count+'_1" name="item_title_'+p_count+'[]"  placeholder="请输入投票选项标题"  class="xx-pt items " data-pid="'+p_count+'"/></div>'
				+'</div>'
				+'<div id="img_items_'+p_count+'" class="disnone probles_items">'
				+'<div class="xx-wz" data-i="1">'
				+'<label for="" class="wd90">选择图片</label><input type="button" data-pid="'+p_count+'" id="item_img_url_'+p_count+'_1" name="item_img_url_'+p_count+'[]"  class="com-btn items" value="选择图片" /><img src="" class="disnone imgxz">'
				+'<input type="hidden" id="hidden_item_img_url_'+p_count+'_1" name="hidden_item_img_url_'+p_count+'[]" class="wd100" data-pid="'+p_count+'"/>'
				+'</div></div><input type="button" class="addItems com-btn "  onclick="Box.addItems(this)"  data-pid="'+p_count+'" value="增加选项" />'
				+'</div>'
				+'<p class="voteQuesCon voteRadio"  id="c_item_type_'+p_count+'">'
				+'<label class="voteLabel"   style="width:120px;"  for="">是否可自定义选项</label>'
				+'<span>'
				+'<input type="radio" id="problems_is_custom_'+p_count+'" name="problems_is_custom_'+p_count+'[]" value="0" checked="checked">&nbsp;&nbsp;可以'
				+'</span><span><input type="radio" id="0problems_is_custom_'+p_count+'" name="problems_is_custom_'+p_count+'[]" value="1">&nbsp;&nbsp;不可以'
				+'</span></p>'
				+'</div>';

				$(".problems_list").append(html_);Box.vtypeClick();
				});
			},
			addItems:function(obj){
				var t = 0,pid = 0,th=$(obj);
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
								+'<input type="text" id="item_title_'+pid+'_'+lth+'"  placeholder="请输入投票选项标题"  name="item_title_'+pid+'[]" class="xx-pt items " data-pid="'+pid+'" style="width:325px !important;"/><input type="button" class="com-btn" onclick="Box.delWzItems(this)" data-pid="'+pid+'" value="删除选项" style="margin-left:8px;"/></div>';
					break;
				}
				if(t=="0") {
					$("#wz_items_"+pid).append(html_);
				}else {
					$("#img_items_"+pid).append(html_);
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
					}else{
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
					$.get("/OA/Vote/getJsonTrees",function(zNodes){
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
									alertnewpage2("你没选中任何部门");
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
					$.post("/OA/Vote/saveVotes",jsdata,function(data){
						layer.alert(data.msg);
						// $(".ui-widget-overlay").remove();
						$("#sendVote").val("发起投票");
						$("#sendVote").attr("onclick","Box.sendVote()");
						window.location.reload();
					});
				}
				$(".ui-widget-overlay").remove();
			},
			checkSend:function(params){
				if(params.vname==""){
					layer.alert("请输入投票名称");
					$("#vname").focus().css({"border":"1px solid red"});
					return false;
				}
				if(params.start_time==""){
					layer.alert("请输入投票开始时间");
					$("#start_time").focus().css({"border":"1px solid red"});
					return false;
				}
				if(params.end_time==""){
					layer.alert("请输入投票结束时间");
					$("#end_time").focus().css({"border":"1px solid red"});
					return false;
				}

				if(params.instruct==""){
					layer.alert("请输入投票须知");
					$("#instruct").focus().css({"border":"1px solid red"});
					return false;
				}
				if(params.attention==""){
					layer.alert("请输入注意事项");
					$("#attention").focus().css({"border":"1px solid red"});
					return false;
				}
				if(params.departs_ids==""){
					layer.alert("请选择投票部门,手写无效");
					$("#departs").focus();
					return false;
				}
				return true;
			}
		};
	$(function(){List.init();Box.init();});