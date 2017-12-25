/**
 * [In description]
 * @type {Object}
 */
var In={
	init:function(){
		this.isNeedSureTask();
		this.getBumenOrFuzeren();
		  setTimeout(In.initQinBao_jxz_1(1),1000);
		  setTimeout(In.initQinBao_all_qb(),5000);

	},
	isNeedSureTask:function(){
		if(cs.isNeedSure==500){
			window.location.href="/Home/IntelligenceSystem/gatherworkflow.html?qbid="+cs.zqb_id;
		}	
	},
	initQinBao_jxz_5:function(index_){
		var qb_tj_1 = $("#qb_tj_5"),qb_html=qb_tj_1.html();
		var show_btn_ = $("#show_btn_"+index_);
		$(".bqn-btn").removeClass("btnon");
		show_btn_.addClass("btnon");
		if(qb_html=="" || qb_html==undefined){
			$(".datali").hide();
			qb_tj_1.parent().show();
			var myChart = echarts.init(document.getElementById('qb_tj_5'));
			myChart.showLoading({
				text:'统计中，请稍候...',
			    textStyle: { color: '#000' },
			    effectOption: {backgroundColor: '#e2e2e2'}
			});
			var params      ={};
			params.is_super =is_super;
			params.type     =3;
			$.get("/Home/IntelligenceSystem/getQinBaoData.html",params,function(data){
				if(data.data!=false){
					var option = {
						title: {
					        text: '情报任务状态统计',
					        x: 'center',
					        y:"bottom",
					        textStyle:{
					        	color:"#1a72d6",
					        	fontSize:12
					        }
					    },
					    tooltip : {
					        trigger: 'item',
					        formatter: "{c} ({d}%)"
					    },
					    series : [
					        {
					            type: 'pie',
					            radius : '55%',
					            center: ['50%', '60%'],
					            data:data.data
					        }
					    ]
					};

					myChart.setOption(option);
					myChart.hideLoading();
				}else{
					In.show_nodatas("#qb_tj_5",5);
				}
			});


		}else{
			$(".datali").hide();
			qb_tj_1.parent().show();
			
		}
		
				
	},
	initQinBao_jxz_3:function(index_){
		var qb_tj_1 = $("#qb_tj_3"),qb_html=qb_tj_1.html();
		var show_btn_ = $("#show_btn_"+index_);
		$(".bqn-btn").removeClass("btnon");
		show_btn_.addClass("btnon");
		if(qb_html=="" || qb_html==undefined){
			$(".datali").hide();
			qb_tj_1.parent().show();
			var myChart = echarts.init(document.getElementById('qb_tj_3'));
			myChart.showLoading({
				text:'统计中，请稍候...',
			    textStyle: { color: '#000' },
			    effectOption: {backgroundColor: '#e2e2e2'}
			});
			var params      ={};
			params.is_super =is_super;
			params.type     =1;
			params.status   =1;

			$.get("/Home/IntelligenceSystem/getQinBaoData.html",params,function(data){
				if(data.data!=false){
					var option = {
						    title: {
						        text: '进行中的情报任务',
						        x: 'center',
						        y:"bottom",
						        textStyle:{
						        	color:"#1a72d6",
						        	fontSize:12
						        }
						    },
						    tooltip: {
						        trigger: 'item',
						        formatter: '{a} <br/>{b} : {c}'
						    },
						    xAxis: {
						        type: 'category',
						        name: '时间',
						        splitLine: {show: false},
						        data: data.data.ctime
						    },
						    grid: {
						        left: '0%',
						        right: '0%',
						        bottom: '0%',
						        containLabel: true
						    },
						    yAxis: {
						        type: 'log',
						        name: '数量'
						    },
						    series: [
						        {
						            name: '进行中的情报任务',
						            type: 'line',
						            data: data.data.no
						        }
						    ]
						};
						myChart.setOption(option);
						myChart.hideLoading();
				}else{
					In.show_nodatas("#qb_tj_3",3);
				}
				
			});
		}else{
			$(".datali").hide();
			qb_tj_1.parent().show();
		}
	},
	initQinBao_jxz_1:function(index_){
		var qb_tj_1 = $("#qb_tj_1"),qb_html=qb_tj_1.html();
		var show_btn_ = $("#show_btn_"+index_);
		$(".bqn-btn").removeClass("btnon");
		show_btn_.addClass("btnon");
		if(qb_html=="" || qb_html==undefined){
				$(".datali").hide();
				qb_tj_1.parent().show();
				var myChart = echarts.init(document.getElementById('qb_tj_1'));
				myChart.showLoading({
					text:'统计中，请稍候...',
				    textStyle: { color: '#000' },
				    effectOption: {backgroundColor: '#e2e2e2'}
				});
				var params      ={};
				params.is_super =is_super;
				params.type     =1;
				params.status=0;
				// console.log(1);
				$.get("/Home/IntelligenceSystem/getQinBaoData.html",params,function(data){

					if(data.data!=false){
						var option = {
							    title: {
							        text: '待处理的情报任务',
							        x: 'center',
							        y:"bottom",
							        textStyle:{
							        	color:"#1a72d6",
							        	fontSize:12
							        }
							    },
							    tooltip: {
							        trigger: 'item',
							        formatter: '{a} <br/>{b} : {c}'
							    },
							    xAxis: {
							        type: 'category',
							        name: '时间',
							        splitLine: {show: false},
							        data: data.data.ctime
							    },
							   
							    yAxis: {
							        type: 'log',
							        name: '数量'
							    },
							    series: [
							        {
							            name: '待处理的情报任务',
							            type: 'line',
							            data: data.data.no
							        }
							    ]
							};
							myChart.setOption(option);
							myChart.hideLoading();
					}else{
						In.show_nodatas("#qb_tj_1",1);
					}
				});
		}else{
			$(".datali").hide();
			qb_tj_1.parent().show();
		}
		
		
		
	},
	initQinBao_jxz_2:function(index_){
		var qb_tj_1 = $("#qb_tj_2"),qb_html=qb_tj_1.html();
		var show_btn_ = $("#show_btn_"+index_);
		$(".bqn-btn").removeClass("btnon");
		show_btn_.addClass("btnon");
		if(qb_html=="" || qb_html==undefined){
				$(".datali").hide();
				qb_tj_1.parent().show();
				var myChart = echarts.init(document.getElementById('qb_tj_2'));
				myChart.showLoading({
					text:'统计中，请稍候...',
				    textStyle: { color: '#000' },
				    effectOption: {backgroundColor: '#e2e2e2'}
				});
				var params      ={};
				params.is_super =is_super;
				params.type     =2;
				params.status   =1;

				$.get("/Home/IntelligenceSystem/getQinBaoData.html",params,function(data){
					if(data.data!=false){
						var option = {
						    title: {
						        text: '采集中的情报任务',
						        x: 'center',
						        y:"bottom",
						          textStyle:{
							        	color:"#1a72d6",
							        	fontSize:12
							        }
						    },
						    tooltip: {
						        trigger: 'item',
						        formatter: '{a} <br/>{b} : {c}'
						    },
						    xAxis: {
						        type: 'category',
						        name: '时间',
						        splitLine: {show: false},
						        data: data.data.ctime
						    },
						    grid: {
						        left: '3%',
						        right: '4%',
						        bottom: '3%',
						        containLabel: true
						    },
						    yAxis: {
						        type: 'log',
						        name: '数量'
						    },
						    series: [
						        {
						            name: '采集中的情报任务',
						            type: 'line',
						            data: data.data.no
						        }
						    ]
						};
						myChart.setOption(option);
						myChart.hideLoading();
					}else{
						In.show_nodatas("#qb_tj_2",2);
					}
					
				});
		}else{
			$(".datali").hide();
			qb_tj_1.parent().show();
		}

// end
	},
	initQinBao_jxz_4:function(index_){
		var qb_tj_1 = $("#qb_tj_4"),qb_html=qb_tj_1.html();
		var show_btn_ = $("#show_btn_"+index_);
		$(".bqn-btn").removeClass("btnon");
		show_btn_.addClass("btnon");
		if(qb_html=="" || qb_html==undefined){
			$(".datali").hide();
			qb_tj_1.parent().show();
			var myChart = echarts.init(document.getElementById('qb_tj_4'));
			myChart.showLoading({
				text:'统计中，请稍候...',
			    textStyle: { color: '#000' },
			    effectOption: {backgroundColor: '#e2e2e2'}
			});
			var params      ={};
			params.is_super =is_super;
			params.type     =1;
			params.status   =2;

			$.get("/Home/IntelligenceSystem/getQinBaoData.html",params,function(data){
				if(data.data!=false){
					var option = {
					    title: {
					        text: '已完成的情报任务',
					         x: 'center',
					        y:"bottom",
					        textStyle:{
					        	color:"#1a72d6",
					        	fontSize:12
					        }
					    },
					    tooltip: {
					        trigger: 'item',
					        formatter: '{a} <br/>{b} : {c}'
					    },
					    xAxis: {
					        type: 'category',
					        name: '时间',
					        splitLine: {show: false},
					        data: data.data.ctime
					    },
					    grid: {
					        left: '0%',
					        right: '0%',
					        bottom: '0%',
					        containLabel: true
					    },
					    yAxis: {
					        type: 'log',
					        name: '数量'
					    },
					    series: [
					        {
					            name: '已完成的情报任务',
					            type: 'line',
					            data: data.data.no
					        }
					    ]
					};
					myChart.setOption(option);
					myChart.hideLoading();
				}else{
					In.show_nodatas("#qb_tj_4",4);
				}
				
			});
		}else{
			$(".datali").hide();
			qb_tj_1.parent().show();
			
		}
		
	},
	show_nodatas:function(id,type_){
		var str="";
		switch(type_){
			case 1:str="待处理7天数据";break;
			case 2:str="采集中7天数据";break;
			case 3:str="进行中7天数据";break;
			case 4:str="已完成7天数据";break;
			case 5:str="情报任务状态数据";break;
			case 6:str="情报任务数据";break;
		}
		var background_ = type_==6?"":"background:#eee;"; 
		var ht='<div class="" style="height:200px;width:100%;text-align:center;line-height:200px;'+background_+'">暂无'+str+'，无法统计！</div>';
		$(id).html(ht);
	},
	initQinBao_all_qb:function(){
		var myChart = echarts.init(document.getElementById('all_qb'));
        myChart.showLoading({
			text:'统计中，请稍候...',
			textStyle: { color: '#000' },
			effectOption: {backgroundColor: 'rgba(0, 0, 0, 0)'}
		});
        var params      ={};
		params.is_super =is_super;
		params.type     =4;
		params.query_month=$("#strtime").val();
		$.get("/Home/IntelligenceSystem/getQinBaoData.html",params,function(data){
			if(data.data.code==200){
					// test
			        var option = {
			         		title: {
						        text: '情报库统计',
						        x: 'center',
						        textStyle:{
						        	color:"#1a72d6",
						        	fontSize:16
						        }
						    },
						     legend: {
					                data:['情报数','情报任务数','平均周期(天)'],
					                x:'center',
					                y:"bottom"

				            },
						    tooltip: {
						        trigger: 'axis',
						        axisPointer: {
						            type: 'cross',
						            crossStyle: {
						                color: '#999'
						            }
						        }
						    },
						    toolbox: {
						        feature: {
						            saveAsImage: {show: true}
						        }
						    },
						    xAxis: [
						        {
						            type: 'category',
						            data: data.data.week_list,
						            axisPointer: {
						                type: 'shadow'
						            }
						        }
						    ],
						    yAxis: [
						        {
						            type: 'value',
						            name: '数量',
						            axisLabel: {
						                formatter: '{value}'
						            }
						        },
						        {
						            type: 'value',
						            name: '平均周期(天)',
						            axisLabel: {
						                formatter: '{value}'
						            }
						        }
						    ],
						    series: [
						        {
						            name:'情报数',
						            type:'bar',
						            data:data.data.zqb_list
						        },
						        {
						            name:'情报任务数',
						            type:'bar',
						            data:data.data.zqb_item_list
						        },
						        {
						            name:'平均周期(天)',
						            type:'line',
						            yAxisIndex: 1,
						            data:data.data.zqb_average
						        }
						    ]
						};
				        myChart.setOption(option);
						myChart.hideLoading();
			}else{
				In.show_nodatas("#all_qb",6)
			}
        
		});
	},
	showMy:function(){
		 layer.open({
          type: 2,
          title: '我的情报',
          shadeClose: true,
          shade: 0.8,
          area: ['80%', '70%'],
          content: '/Home/IntelligenceSystem/myItem.html?' //iframe的url
        }); 
	},
	isLoadUser:0,//是否加载
	//添加主情报
	showAddQb:function(){
		
		layer.open({
			title:"新建情报",
		  type: 2,
		  skin: 'layui-layer-rim', //加上边框
		  area: ['500px', '500px'], //宽高
		  content: "/Home/IntelligenceSystem/createZqb.html"
		});
	},
	//加载参与人
	initGetCanY:function(){
		$.get("/Home/IntelligenceSystem/ajaxZqbCanYuRen.html",function(data){
			if(data.length>0){

				var h='<option value="">--全部--</option>';

				$.each(data,function(i,o){
					h+='<option value="'+o.id+'">'+o.real_name+'</option>';
				});

				$("#zqb_fzr").html(h);
				$('#zqb_fzr').selectpicker();

				$("#zqb_cyr_ids").html(h);
				$('#zqb_cyr_ids').selectpicker();
			}
		});
	},
	//读取我负责的，参与的
	getQbList:function(page,type){
		var params = {};
		params.qb_name = $("#qb_name").val();
		if(is_super==200){
			params.qb_bmname = $("#qb_bmname").val();
		}else{
			params.qb_fzrname = $("#qb_fzrname").val();
		}
		params.qb_xmstatus = $("#qb_xmstatus").val();
		params.p = page;
		var a='<tr><td colspan="3" style="text-align:center;">加载中...</td></tr>';
		$("#qb_list").html(a);
		$("#page_zqb").hide();
		$.get("/Home/IntelligenceSystem/ajaxQinBaoList.html",params,function(data){
			if(data.code==200){
				var ht="";
				$.each(data.data,function(i,o){
					ht+='<tr>';
					if(o.is_my_pri==200 && o.is_dec==0){
						ht+='<td><a href="javascript:void(0);" onclick="In.suerQingBao('+o.id+')" title="确认主情报">'+o.title+'</a></td>';
					}else{
						ht+='<td><a href="/Home/IntelligenceSystem/gatherworkflow.html?qbid='+o.id+'">'+o.title+'</a></td>';
					}
                    
                    ht+='<td>'+o.username+'</td>';
                    ht+='<td>'+o.status_+'</td>';
                    ht+='</tr>';
				});
				$("#qb_list").html(ht);
				$("#page_zqb").html(data.page).show();
			}else{
				var a='<tr><td colspan="3" style="text-align:center;">暂无数据！</td></tr>';
				$("#qb_list").html(a);
			}
			if(type==1){
				$("#querybtn").attr("onclick","In.queryZuQinBao()").val("查询");
			}
		});
	},
	suerQingBao:function(zqb_id){
		layer.open({
			title:"确认情报",
			type: 2,
			skin: 'layui-layer-rim', //加上边框
			area: ['500px', '500px'], //宽高
			content: "/Home/IntelligenceSystem/createZqb.html?zqb_id="+zqb_id+"&issure=200"
		});
	},
	//读取部门或者负责人
	getBumenOrFuzeren:function(){
		$.get("/Home/IntelligenceSystem/getBumenOrFuzeren.html",{"is_super":is_super},function(data){
			var ht='<option value="">--全部--</option>';
			$.each(data.data,function(i,o){
				ht+='<option value="'+o.id+'">'+o.name+'</option>';
			});
			if(is_super==200){
				$("#qb_bmname").html(ht);
				$('#qb_bmname').selectpicker();
			}else{
				$("#qb_fzrname").html(ht);
				$('#qb_fzrname').selectpicker();
			}

			In.getQbList(1);
		});
	},
	qinBao_pageIndex:1,
	queryZuQinBao:function(){
		// In.qinBao_pageIndex++;
		$("#querybtn").attr("onclick","").val("查询中...");
		In.getQbList(1,1);

		
	},
	showNo:function(){
		$("#no_tj").show();
		$("#zt_tj").hide();
		$(".com-btn").removeClass("btnon");
		$("#showNo").addClass("btnon");
	},
	showStatus:function(){
		$("#no_tj").hide();
		$("#zt_tj").show();
		$(".com-btn").removeClass("btnon");
		$("#showStatus").addClass("btnon");
	},


};
$(function(){In.init();})