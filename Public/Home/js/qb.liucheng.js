/**
 * [Ga description]
 * @type {Object}
 */
var Ga={
	init:function(){
		this.initHt();
		// this.initClzClick();
		setTimeout(Ga.initLoadDcl(0),500);
		setTimeout(Ga.initLoadDcl(1),600);
		setTimeout(Ga.initLoadDcl(2),700);
		this.queryIsSure();
	},
	//是分解的时候默认弹出弹框
	queryIsSure:function(){
		if(_default.issure==1){
			setTimeout(Ga.addTask(),5000);
		}
	},
	addTask:function(){
		var hindex=layer.open({
		  type: 2,
		  title: "创建情报任务",
		  shadeClose: true,
		  shade: 0.4,
		  area: ['500px', '280px'],
		  content: '/Home/IntelligenceSystem/ShowDclTask.html?zqb_id='+_default.zqb_id //iframe的url
		});
		var lht=$("#layui-layer"+hindex).height();
		$("#layui-layer-iframe"+hindex).css({"height":lht+"px"});
	},
	canaddtask:function(){
		$("#add_task").hide();
		$("#add_btn").val("添加情报任务").attr("onclick","Ga.addTask()");
	},
	initHt:function(){
		var top=$("#lc_sec").offset().top;
		var ht=$(window).height();
		var dht=parseFloat(ht)-parseFloat(top)-60;
		$(".lc-mainsec").height(dht);
		// dht = parseFloat(dht)-40;
		// $(".lc_list").height(dht);
	},
	initClzClick:function(){
		$(".clz_sec").on("click",function(){
			var d = $(this).attr("data-id");
			var t=$(this).attr("data-title");
			var status_ = $(this).attr("data-status");
			layer.open({
			  type: 2,
			  title: t,
			  shadeClose: true,
			  shade: 0.4,
			  area: ['650px', '80%'],
			  // area: ['100%', '100%'],
			  content: '/Home/IntelligenceSystem/allotTask.html?task_id='+d+"&rw_status="+status_ //iframe的url
			}); 

		});
	},
	initYwc:function(){
		$(".ywc_qb").on("click",function(){
			var d = $(this).attr("data-id");
			// window.open("");
		});
	},
	//加载待处理
	initLoadDcl:function(status_){
		if(_default.zqb_id!=""){
			switch(status_){
				case 0:
				var ht='<div class="qb-ngkks-a d-f" id="loading" style="height:200px;line-height:200px;text-align:center;color#666;">加载中....</div>';
				$("#dcl_lc_list").html(ht);
				break;
				case 1:
				var ht='<div class="qb-ngkks-a d-f" id="loading1" style="height:200px;line-height:200px;text-align:center;color#666;">加载中....</div>';
				$("#clz_lc_list").html(ht);
				break;
				case 2:
				var ht='<div class="qb-ngkks-a d-f" id="loading2" style="height:200px;line-height:200px;text-align:center;color#666;">加载中....</div>';
				$("#ywc_lc_list").html(ht);
				break;
			}
			$.get("/Home/IntelligenceSystem/getZqbChildTaskList.html",{"zqb_id":_default.zqb_id,"status":status_},function(data){
				Ga.showLiuchengHtml(status_,data);
			});
		}
		
	},
	updateTaskStatus:function(task_id,status_){
		M.stopmp(M.getEvent());
		var laycIndex = layer.confirm("您确定吗？",function(){
			var params={};
			params.id=task_id;
			params.status=status_;
			layer.close(laycIndex);
			$("#zh_"+status_).attr("onclick","").val("操作中...");
			$.post("/Home/IntelligenceSystem/updateTaskStatusDo.html",params,function(data){
				window.location.reload();
			});
		});
	},
	showLiuchengHtml:function(type_,data){
		switch(type_){
			case 0:
				if(data.data.length != undefined && data.data.length>0){
					var ht="";
					$.each(data.data,function(i,o){
						ht+='<div class="qb-ngkks-a d-f" onclick="Ga.ShowDclTask('+o.id+')" id="dcl_'+o.id+'" data-status="0" data-title="'+o.head_title+'">';
		                ht+='<div class="d-f qb-tisl0s">';
		                ht+=o.head_title;
		                ht+='<span class="fze-nam" title="负责人：'+o.real_name+'">'+o.real_name+'</span></div>';
		                ht+='<div class="d-f qb-fze" style="border-bottom: 1px dashed #eee;">';
		                ht+='<span class="qb-fhzkd qb-fteks-s">创建时间：'+o.ctime+'</span>';
		                ht+='</div>';
		                ht+='<div class="d-f qb-fze" style="padding-bottom:5px;">';
		                if(o.is_same_pri==2){
		                	 ht+='<span class="qb-fhzkd qb-fteks-s"><input type="button" id="zh_1" value="转为处理中" class="tx-zi" onclick="Ga.updateTaskStatus('+o.id+',1)" /></span>';
		                }
		               
		                ht+='</div>';
		                ht+='</div>';
					});
					$("#dcl_lc_list").html(ht);
				}else{
					$("#loading").html("暂无待处理的任务！").hide();
				}
			break;
			case 1:
				if(data.data.length != undefined && data.data.length>0){
					var ht="";
					$.each(data.data,function(i,o){

						ht+='<div class="qb-ngkks-a d-f clz_sec" onclick="Ga.showTaskDetail(this)" id="dcl_'+o.id+'" data-id="'+o.id+'" data-title="'+o.head_title+'" data-status="0">';
						var real_name = "";
						if(o.real_name!=undefined){
							real_name = o.real_name;
						}
						ht+='<div class="d-f qb-tisl0s add-seti" title="点击">'+o.head_title+'<span class="fze-nam" title="负责人：'+real_name+'">'+real_name+'</span>';
						ht+='</div>';
						ht+='<div class="d-f qb-qitls">';
						var fields_name = "";
						if(o.fields_name!=undefined){
							fields_name = o.fields_name;
						}
						ht+='<font>'+fields_name+'</font>';
						ht+='</div>';
						ht+='<div class="d-f qb-fze">';
						ht+='<span class="qb-fhzkd ">创建时间：'+o.ctime+'</span>';
						if(o.exp_end_time){
							ht+='<span class="qb-fhzkd qb-fteks-s">计划完成时间：'+o.exp_end_time+'</span>';
						}
						
						ht+='</div>';
					    ht+='<div class="d-f qb-fze" style="padding-bottom:5px;">';
				    	if(o.is_same_pri==2){
		                	ht+='<span class="qb-fhzkd qb-fteks-s"><input type="button"  id="zh_2"  value="转为已完成" class="tx-zi" onclick="Ga.updateTaskStatus('+o.id+',2)" /></span>';
		            	}	
		                ht+='</div>';
						ht+='</div>';
					});
					$("#clz_lc_list").html(ht);
					// setTimeout(Ga.initClzClick(),1000);
				}else{
					$("#loading1").html("暂无处理中的任务！").hide();
				}
				
			break;
			case 2:
				if(data.data.length != undefined && data.data.length>0){
					var ht="";
					$.each(data.data,function(i,o){
						ht+='<div class="qb-ngkks-a d-f clz_sec" onclick="Ga.showTaskDetail(this)"  id="dcl_'+o.id+'" data-id="'+o.id+'" data-title="'+o.head_title+'" data-status="3">';
						ht+='<div class="d-f qb-tisl0s add-seti" title="点击">'+o.head_title+'<span class="fze-nam" title="负责人：'+o.real_name+'">'+o.real_name+'</span>';
						ht+='</div>';
						ht+='<div class="d-f qb-qitls">';
						var fields_name = "";
						if(o.fields_name!=undefined){
							fields_name = o.fields_name;
						}
						ht+='<font>'+fields_name+'</font>';
						ht+='</div>';
						ht+='<div class="d-f qb-fze">';
						ht+='<span class="qb-fhzkd ">创建时间：'+o.ctime+'</span>';
						if(o.exp_end_time){
							ht+='<span class="qb-fhzkd qb-fteks-s">计划完成时间：'+o.exp_end_time+'</span>';
						}
						if(o.fact_end_time){
							ht+='<span class="qb-fhzkd">实际完成时间：'+o.fact_end_time+'</span>';
						}
						
						ht+='</div>';
						ht+='</div>';
					});
					$("#ywc_lc_list").html(ht);
					// Ga.initClzClick();
				}else{
					$("#loading2").html("暂无已完成的任务！").hide();
				}
			break;
		}
	},
	showTaskDetail:function(boj){
		var d = $(boj).attr("data-id");
		var t=$(boj).attr("data-title");
		var status_ = $(boj).attr("data-status");
		layer.open({
		  type: 2,
		  title: t,
		  shadeClose: true,
		  shade: 0.4,
		  area: ['650px', '80%'],
		  // area: ['100%', '100%'],
		  content: '/Home/IntelligenceSystem/allotTask.html?task_id='+d+"&rw_status="+status_ //iframe的url
		}); 

	},
	//修改主情报
	getChangZqb:function(){

	},
	//修改待处理任务
	ShowDclTask:function(id){
		var t=$("#dcl_"+id).attr("data-title");
		layer.open({
		  type: 2,
		  title: t,
		  shadeClose: true,
		  shade: 0.4,
		  area: ['500px', '280px'],
		  content: '/Home/IntelligenceSystem/ShowDclTask.html?id='+id+"&zqb_id="+_default.zqb_id //iframe的url
		});
	},
	editZqb:function(){
		//编辑主情报
		layer.open({
			title:"编辑情报",
		  type: 2,
		  skin: 'layui-layer-rim', //加上边框
		  area: ['500px', '500px'], //宽高
		  content: "/Home/IntelligenceSystem/createZqb.html?zqb_id="+_default.zqb_id
		});
	},
	zqbRuku:function(){
		layer.confirm("您确定要入库吗？",function(){
			//检查是否满足入库条件
			var params={};
			params.zqb_id=_default.zqb_id;
			$.get("/Home/IntelligenceSystem/canRuKu.html",params,function(data){
				if(data.no>0){
					var lindex = layer.confirm("情报还有未完成的任务，是否确认入库？",function(){
						params.type="500";
						layer.close(lindex);
						$("#zqbrukbtn").attr("onclick","").val("入库中...");
						$.post("/Home/IntelligenceSystem/goRuKu.html",params,function(data){
							//跳转到入库中
							window.location.href="/Home/IntelligenceSystem/intelLibrary.html";
						});
					});
				}else{
					var lindex = layer.confirm("情报已完成所有的任务，完成后入库？",function(){
						params.type="200";
						layer.close(lindex);
						$("#zqbrukbtn").attr("onclick","").val("入库中...");
						$.post("/Home/IntelligenceSystem/goRuKu.html",params,function(data){
							//跳转到入库中
							window.location.href="/Home/IntelligenceSystem/intelLibrary.html";
						});
					});
				}
				
			});
		});
	}
	
};
var M={
	stopmp:function(e){
		var evt = e ;
		e.stopPropagation();
	},
	getEvent:function(event) {
		var ev = event || window.event;
		if (!ev) {
			var c = this.getEvent.caller;
			while (c) {
				ev = c.arguments[0];
				if (ev && (Event == ev.constructor || MouseEvent == ev.constructor)) { 
					break;
				}
				c = c.caller;
			}
		}
		return ev;
	}
};
$(function(){Ga.init();});