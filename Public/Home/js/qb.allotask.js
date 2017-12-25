/**
 * [all description]
 * @type {Object}
 */
var qb={
	init:function(){
		
		 this.init_qb_fzr();
		 this.initTaskQuQiuZiDuan(1);
		this.loadLog(0);
		 setTimeout(this.initGetZRWDdata(),1000);
		 setTimeout(this.initGetZRWGenJindata(1),2000);
	},
	//添加跟进记录
	initGetZRWGenJindata:function(page){
		var ht='<tr class="zw-dattd"><td colspan="5" class="nthsi">加载中...</td></tr>';
		$("#page_genjin").hide();
		$("#genjin_list").html(ht);
		var params            ={};
		params.parent_task_id = $("#hd_task_id").val();
		params.type_id        = 100;
		params.p=page;
		var is_query          = $("#is_query").val();
		$.get("/Home/IntelligenceSystem/GetZRWGenJindata.html",params,function(data){
			if(data.data.length>0){
				var mht="";
				$.each(data.data,function(i,o){
					mht+='<tr class="zw-dattd">';
					mht+='<td>'+o.visit_time+'</td>';
					mht+='<td>'+o.real_name+'</td>';
					mht+='<td>'+o.result+'</td>';
					mht+='<td>'+o.status_str+'</td>';
					mht+='<td><a href="javascript:void(0);" onclick="qb.addFlow()"  data-id="'+o.id+'">查看</a></td>';
					mht+='</tr>';
				});
				$("#genjin_list").html(mht);
				$("#page_genjin").html(data.page).show();
			}else{
				var ht='<tr class="zw-dattd"><td colspan="5" class="nthsi">暂时没有跟进记录！</td></tr>';
				$("#genjin_list").html(ht);
			}
		});
	},
	//获取子任务数据
	initGetZRWDdata(){
		var ht='<tr class="zw-dattd"><td colspan="4" class="nthsi">加载中...</td></tr>';
		$("#tzrw").html(ht);
		var params={};
		params.parent_task_id = $("#hd_task_id").val();
		var is_query             = $("#is_query").val();
		$.get("/Home/IntelligenceSystem/GetZRWDdata.html",params,function(data){
			if(data.data.length>0){
				var mht="";
				$.each(data.data,function(i,o){
					mht+='<tr class="zw-dattd">';
					mht+='<td>'+o.head_title+'</td>';
					mht+='<td>'+o.exp_end_time+'</td>';
					mht+='<td>'+o.real_name+'</td>';
					var bjhtml,type_s;
					if(is_query==200){
						bjhtml = "查看";
						type_s = 1;
					}else{
						bjhtml = o.is_same_pri==2?"编辑":"查看";
						type_s = o.is_same_pri==2?2:1;
					}
					
					mht+='<td><a href="javascript:void(0);" onclick="qb.editZrw('+o.id+','+type_s+')" class="blizw" data-id="'+o.id+'">'+bjhtml+'</a></td>';
					mht+='</tr>';
				});
				$("#tzrw").html(mht);
			}else{
				var ht='<tr class="zw-dattd"><td colspan="4" class="nthsi">暂时没有子任务！</td></tr>';
				$("#tzrw").html(ht);
			}
		});
	},
	//编辑子任务
	editZrw:function(zrw_id,type_){
		var task_id = $("#hd_task_id").val();
		var bjhtml = type_==2?"编辑":"查看";
		layer.open({
		  type: 2,
		  title: bjhtml+"子任务",
		  shadeClose: true,
		  shade: 0.4,
		  area: ['100%', '80%'],
		  content: '/Home/IntelligenceSystem/addZrwPage.html?parent_task_id='+task_id+"&zrw_id="+zrw_id+"&is_query="+type_//iframe的url
		}); 
	},	
	initTitle:function(){
		// $(".f-txt").on("click",function(){
		// 	$("#txt_qb_fzr").hide();
		// 	$("#qb_fzr option[value='"+$("#txt_qb_fzr").html()+"']").attr("select","selected");  
		// 	$("#qb_fzr").show();
		// });
	},
	//加载任务需求字段
	initTaskQuQiuZiDuan:function(type_){
		var params               ={};
		params.fields_data_count = $("#fields_data_count").val();
		params.task_id           = $("#hd_task_id").val();
		var is_query             = $("#hd_is_my_task").val();
		var dis = is_query==500?'disabled="true"':"";
		$.get("/Home/IntelligenceSystem/getTaskQuQiuZiDuan.html",params,function(data){
			var ht='<option value="">--请选择--</option>';
			var mht="";

			if(data.data!=undefined && data.data.length>0){
				$.each(data.data,function(i,o){
					ht+='<option value="'+o.id+'">'+o.name+'</option>';

					// if(type_>0 && parseFloat(params.fields_data_count)<=0){
					// 	if(i<3){
					// 		mht+='<div class="d-f qu-item" id="xqsec_'+o.id+'">';
					// 		mht+='<span class="mis-lea-xk">'+o.name+'：</span>';
					// 		mht+='<span class="mis-rb-xks t-tile">';
					// 		mht+='<input type="text" data-field-id="'+o.id+'" '+dis+'  class="tks-ps tpols-ck xq_zd  fd_'+o.id+'" name="qb_xqzd[]" value=""  />';
					// 		mht+='</span>';
					// 		if(is_query==200){
					// 			mht+='<span class="dexs" onclick="qb.deXuqiu('+o.id+')">删除</span>';
					// 		}
					// 		mht+='</div>';	
					// 	}
					// }
					
				});
			}
		


			//加载自己的
			if(parseFloat(params.fields_data_count)>0 && data.myfieldsdata.length>0){
				mht="";
				$.each(data.myfieldsdata,function(i1,o1){
					mht+='<div class="d-f qu-item" id="xqsec_'+o1.field_id+'">';
					mht+='<span class="mis-lea-xk">'+o1.name+'：</span>';
					mht+='<span class="mis-rb-xks t-tile">';
					mht+='<input type="text" data-field-id="'+o1.field_id+'"  '+dis+'  class="tks-ps tpols-ck xq_zd  fd_'+o1.field_id+'" name="qb_xqzd[]" value="'+o1.demand_val+'"  />';
					mht+='</span>';
					if(is_query==200){
						mht+='<span class="dexs" onclick="qb.deXuqiu('+o1.field_id+')">删除</span>';
					}
					mht+='</div>';
				});
			}


			
			$("#qb_xqzd").html(ht);
			$('#qb_xqzd').selectpicker();

			//子任务
			// $("#zrw_qb_xqzd").html(ht);
			// $('#zrw_qb_xqzd').selectpicker();

			

			if(type_>0){
				$("#qb_xq_zd").html(mht).show();
				if(parseFloat(params.fields_data_count)>0 && data.myfieldsdata.length>0){
					//显示前面3个字段
					$("#qb_xq_zd").html(mht).show();
				}
			}

		});
	},
	deXuqiu:function(id){
		layer.confirm("您确定要删除?",function(){
			$("#xqsec_"+id).remove();
			layer.close();
			layer.msg("记得点击下面的保存哟",{time:1000});
		});	
		
	},
	//加载负责人列表
	init_qb_fzr:function(){
		$.get("/Home/IntelligenceSystem/getBumenOrFuzeren.html",function(data){
			var ht='<option value="">--全部--</option>';
			$.each(data.data,function(i,o){
				var se = dvalue.qb_fzr==o.id?"selected='selected'":"";
				ht+='<option value="'+o.id+'" '+se+'>'+o.name+'</option>';
			});
			$("#qb_fzr").html(ht);
			$('#qb_fzr').selectpicker();
		});
	},
	backText:function(){
		$("#qb_title").focusout(function(){
			//post
			console.log("save title");
		});
	},
	saveFzr:function(){
		var v = $("#qb_fzr option:selected").val();
		console.log("saveFzr"+v);
	},
	mb_layer_inde:0,
	addZd:function(type_){
		// type_ 1:主任务，2：子任务
		var mb='<div class="xuq-se  d-f " style="display:block;" id="add_zd_mb">';
		mb+='<div class="d-f qu-item" style="width:92%;padding:0 4%;" >';
		mb+='<span class="mis-lea" style="width:15%;text-align:right;">需求字段名：</span>';
		mb+='<span class="mis-rb t-tile" style="width:50%;">';
		mb+='<input type="text"  id="zx_add_name" value="" class="tks-ps tpols-ck" placeholder="请输入需求字段名"   style="width:100% !important;" />';
		mb+='</span>';
		mb+='<input type="button" value="添加字段" class="tx-zi" id="tjzdbtn" onclick="qb.saveZd()" style="margin-left:20px;" />';
		mb+='</div>';
		mb+='</div>';
		qb.mb_layer_inde = layer.open({
			title:"添加字段",
		  type: 1,
		  skin: 'layui-layer-rim', //加上边框
		  area: ['90%', '110px'], //宽高
		  content: mb
		});
	},
	saveZd:function(){
		var params = {};
		params.xz_zd_name = $("#zx_add_name").val();
		params.hd_task_id = $("#hd_task_id").val();
		var hd_is_my_task = $("#hd_is_my_task").val();
		if(params.xz_zd_name!=""){
			$("#tjzdbtn").val("保存中...").attr("onclick","");
			$.post("/Home/IntelligenceSystem/saveZdDo.html",params,function(data){
				if(data.code==200){
					//添加字段，重新读取字段select
					qb.initTaskQuQiuZiDuan(0);
					var mht = "";
					mht+='<div class="d-f qu-item" id="xqsec_'+data.id+'">';
					mht+='<span class="mis-lea-xk">'+params.xz_zd_name+'：</span>';
					mht+='<span class="mis-rb-xks t-tile">';
					mht+='<input type="text"  data-field-id="'+data.id+'"   class="tks-ps tpols-ck xq_zd fd_'+data.id+'" name="qb_xqzd[]" value=""  />';
					mht+='</span>';
					mht+='<span class="dexs" onclick="qb.deXuqiu('+data.id+')">删除</span>';
					mht+='</div>';	
					$("#qb_xq_zd").append(mht);

					
				}

				if(data.code==600){
					layer.msg(data.msg);
				}

				layer.close(qb.mb_layer_inde);
			});
		}else{
			$("#xz_zd_name").focus();
		}
	},
	addZrw:function(){
		var task_id = $("#hd_task_id").val();
		layer.open({
		  type: 2,
		  title: "添加子任务",
		  shadeClose: true,
		  shade: 0.4,
		  area: ['100%', '80%'],
		  content: '/Home/IntelligenceSystem/addZrwPage.html?parent_task_id='+task_id+"&is_query=2"//iframe的url
		}); 
	},
	addFlow:function(){
		var task_id = $("#hd_task_id").val();
		layer.open({
		  type: 2,
		  title: "跟进记录",
		  shadeClose: true,
		  shade: 0.4,
		  area: ['100%', '80%'],
		  content: '/Home/InteSystem/expand.html?extid='+task_id+"&type_id="+100 //iframe的url
		}); 
	},
	//继续保存任务
	saveTaskGoOn:function(){
		var params       ={};
		params.qb_title  = $("#qb_title").val();
		params.qb_fzr    = $("#qb_fzr").val();
		params.qb_jhwcsj = $("#qb_jhwcsj").val();
		params.qb_ms     = $("#qb_ms").val();
		params.id        = $("#hd_task_id").val();

		var zd_list = new Array();
		$(".xq_zd").each(function(){
			var vv=$(this).val();
			if(vv!=""){
				var fid=$(this).attr("data-field-id");
				var vvv= fid+"__"+vv;
				zd_list.push(vvv);
			}
		});	
		params.zd_data = zd_list;

		var res = qb.checkTaskGoOn(params);
		if(res.no>0){
			layer.confirm("您还有信息没填写，确定要保存吗？",function(){
				layer.close();
				qb.saveTaskGoOn_(params);
				
			});
		}else{
			layer.confirm("您确定要保存吗？",function(){
				layer.close();
				qb.saveTaskGoOn_(params);
			});
		}

	},
	saveTaskGoOn_:function(params){
		
		$("#jx_bcbtn").val("保存中...").attr("onclick","");
		$.post("/Home/IntelligenceSystem/saveTaskGoOnDo.html",params,function(data){
			window.location.reload();
		});
	},
	checkTaskGoOn:function(params){
		var res = {
			can:true,
			no:0
		}
		if(params.qb_title==""){
			res.no++;
		}
		if(params.qb_fzr==""){
			res.no++;
		}
		if(params.qb_jhwcsj==""){
			res.no++;
		}
		if(params.qb_ms==""){
			res.no++;
		}
		$(".xq_zd").each(function(){
			if($(this).val()==""){
				res.no++;
			}
		});
		return res;
	},
	setRwXuQiu:function(){
		var selected_id = $("#qb_xqzd option:selected").val();
		if(selected_id){
			var ishas=0,select_text=$("#qb_xqzd option:selected").text();
			$(".xq_zd").each(function(){
				if($(this).hasClass("fd_"+selected_id)){
					ishas++;
				}
			});

			if(ishas==0){
				var mht = "";
				mht+='<div class="d-f qu-item" id="xqsec_'+selected_id+'">';
				mht+='<span class="mis-lea-xk">'+select_text+'：</span>';
				mht+='<span class="mis-rb-xks t-tile">';
				mht+='<input type="text"  data-field-id="'+selected_id+'"   class="tks-ps tpols-ck xq_zd fd_'+selected_id+'" name="qb_xqzd[]" value=""  />';
				mht+='</span><span class="dexs" onclick="qb.deXuqiu('+selected_id+')">删除</span>';
				mht+='</div>';	
				$("#qb_xq_zd").append(mht);
			}else{
				layer.msg(select_text+" 字段已经在下面了，不用选了");
			}
		}
	},
	//加载日志
	loadLog:function(page){
		var params     ={};
		params.task_id = $("#hd_task_id").val();
		params.p=page;
		$("#page_zqb").hide();
		$("#logloading").html("加载中....").show();
		$("#rz_sec").hide();
		$.get("/Home/IntelligenceSystem/loadTakLog.html",params,function(data){
			if(data.data.length>0){
				var ht="";
				$.each(data.data,function(i,o){
					var attach_url="";
					if(o.attach_url){
						attach_url='&nbsp;&nbsp;<a href="javascript:void(0);" class="fu-slk" onclick="qb.showAttach(\''+o.attach_url+'\')">附件：'+o.attach_name+'</a>';
					}
					if(o.content.length>20){
						ht+='<div class="d-f rz-items" data-type="2">';
						ht+='<span class="rz-lisa">'+o.real_name+'</span>';
						ht+='<span class="rz-lisr-b">'+o.ctime+'</span>';
						ht+='<div class="d-f rkz-mskls">'+o.content+attach_url+'</div>';
						ht+='</div>';
					}else{
						ht+='<div class="d-f rz-items" data-type="1" >';
						ht+='<span class="rz-lisa" style="color:#666;"><font style="color: #1a72d6">'+o.real_name+'</font> '+o.content+attach_url+'</span>';
						ht+=' <span class="rz-lisr-b">'+o.ctime+'</span>';
						ht+='</div>';
					}
				});

				$("#log_data").html(ht);
				$("#rz_sec").show();
				$("#logloading").hide();
				$("#page_zqb").html(data.page).show();
			}else{
				$("#logloading").html("没有日志显示！");
			}
		});
	},
	showAttach:function(url){
		window.parent.open(url);
	},
	addLogMes:function(){
		var v=$("#msgxx").val();
		if(v==""){
			$("#msgxx").focus();
		}else{
			var params={};
			params.msgxx = v;
			params.task_id = $("#hd_task_id").val();
			params.fileurl=$("#upfj").val();
			var hasimg = 500;
			if(params.fileurl){
				hasimg = 200;
				params.ysfilename = params.fileurl;
				$("#ysfilename").val(params.ysfilename);
			}

			params.hasimg = hasimg;
			$("#hasimg").val(params.hasimg);
			console.log(params);
			$("#lybtn").attr("onclick","").val("操作中...");
			$("#lyanform").submit();


			// $.post("/Home/IntelligenceSystem/addLogMesSer.html",params,function(data){
			// 	$("#lybtn").attr("onclick","qb.addLogMes()").val("留言");
			// 	// qb.loadLog(0);
			// });
		}
	},
	//上传文件
	upAttchFiles:function(){
  //   	var button = $('#upfj');
  //   	var uplayer;
		// new AjaxUpload(button,{
		// 	action: "/Home/IntelligenceSystem/upAjaxAttach.html",
		// 	name: 'files',
		// 	onSubmit:function(){
		// 		uplayer = layer.msg("上传中...",{time:10000});
		// 	},
		// 	onComplete: function(file, response){
		// 		var list=eval("("+response.replace(/<\/?[^>]*>/g,'')+")");
		// 		layer.close(uplayer);
		// 		if(list.status==1){
		// 			$("#hd_attach_url").val(list.data);
		// 		}else{
		// 			layer.msg(data.msg);
		// 		}
		// 	}
		// });
    }
};
$(function(){qb.init();});