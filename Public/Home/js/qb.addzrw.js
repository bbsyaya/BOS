/**
 * [all description]
 * @type {Object}
 */
var qb={
	init:function(){
		this.initTaskQuQiuZiDuan(1);
		this.init_qb_fzr();
	},
	//加载任务需求字段
	initTaskQuQiuZiDuan:function(type_){
		var params               ={};
		params.fields_data_count = $("#fields_data_count").val();;
		params.task_id           = $("#hd_zrw_id").val();
		var is_query             = $("#is_query").val();
		$.get("/Home/IntelligenceSystem/getTaskQuQiuZiDuan.html",params,function(data){
			var ht='<option value="0">--请选择--</option>';
			var mht="";
			if(isNaN(parseFloat(params.fields_data_count))){
				params.fields_data_count = 0;
			}
			if(data.data!=undefined && data.data.length>0){
				$.each(data.data,function(i,o){
					ht+='<option value="'+o.id+'">'+o.name+'</option>';

					// if(type_>0 && parseFloat(params.fields_data_count)<=0){
					// 	if(i<3){
					// 		mht+='<div class="d-f qu-item" id="xqsec_'+o.id+'">';
					// 		mht+='<span class="mis-lea-xk">'+o.name+'：</span>';
					// 		mht+='<span class="mis-rb-xks t-tile">';
					// 		mht+='<input type="text" data-field-id="'+o.id+'"  class="tks-ps tpols-ck xq_zd  fd_'+o.id+'" name="qb_xqzd[]" value=""  />';
					// 		mht+='</span>';

					// 		if(is_query==2){
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
					mht+='<input type="text" data-field-id="'+o1.field_id+'"   class="tks-ps tpols-ck xq_zd  fd_'+o1.field_id+'" name="qb_xqzd[]" value="'+o1.demand_val+'"  />';
					mht+='</span>';

					if(is_query==2){
						mht+='<span class="dexs" onclick="qb.deXuqiu('+o1.field_id+')">删除</span>';
					}
					mht+='</div>';
				});
			}


			
			$("#qb_xqzd").html(ht);
			$('#qb_xqzd').selectpicker();
			
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
	setRwXuQiu:function(){
		var selected_id = $("#qb_xqzd option:selected").val();
		var ishas=0,select_text=$("#qb_xqzd option:selected").text();

		$(".xq_zd").each(function(){
			if($(this).hasClass("fd_"+selected_id)){
				ishas++;
			}
		});

		if(ishas==0 && selected_id>0){
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
					mht+='</span><span class="dexs" onclick="qb.deXuqiu('+data.id+')">删除</span>';
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
	checkTaskVal:function(params){
		if(params.zrw_title==""){
			layer.msg("子任务标题要填写！");
			return false;
		}
		if(params.qb_fzr=="" || params.qb_fzr==undefined){
			layer.msg("负责人必须选择！");
			return false;
		}
		if(params.qb_jhwcsj=="" || params.qb_jhwcsj==undefined){
			layer.msg("请选择计划完成时间");
			return false;
		}
		return true;
	},
	//继续保存任务
	saveTaskGoOn:function(){
		var params            ={};
		params.zrw_title = $("#zrw_title").val();
		params.qb_fzr         = $("#qb_fzr").val();
		params.qb_jhwcsj      = $("#qb_jhwcsj").val();
		params.parent_task_id = $("#hd_task_id").val();
		params.zrw_id         = $("#hd_zrw_id").val();

		var res = qb.checkTaskVal(params);
		if(res){
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
				qb.baocun_layer_index = layer.confirm("您还有信息没填写，确定要保存吗？",function(){
					layer.close();
					qb.saveTaskGoOn_(params);
					
				});
			}else{
				qb.baocun_layer_index = layer.confirm("您确定要保存吗？",function(){
					layer.close();
					qb.saveTaskGoOn_(params);
				});
			}
		}
		
	},
	baocun_layer_index:0,
	saveTaskGoOn_:function(params){
		layer.close(qb.baocun_layer_index);
		$("#jx_bcbtn").val("保存中...").attr("onclick","");
		$.post("/Home/IntelligenceSystem/saveZrwData.html",params,function(data){
			window.parent.location.reload();
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
	addFlow:function(){
		var task_id = $("#hd_zrw_id").val();
		layer.open({
		  type: 2,
		  title: "跟进记录",
		  shadeClose: true,
		  shade: 0.4,
		  area: ['100%', '80%'],
		  content: '/Home/InteSystem/expand.html?extid='+task_id+"&type_id="+101 //iframe的url
		}); 
	},
};
$(function(){qb.init();});