
/**
 * [e description]
 * @type {Object}
 */
var e={
	init:function(){
		setTimeout(this.lazySupply(),1000);
		this.initAdver();
		setTimeout(this.lazyProData,1005);
	},
	initAdver:function(){
		var myChart = echarts.init(document.getElementById('container'));
		var params = {};
		params.ggz_name = $("#ggs_name").val();
		// params.cp_name = $("#cp_name").val();
		params.strtime = $("#strtime").val();
		params.endtime = $("#endtime").val();
		params.gys_id = $("#gys_id").val();
		$('#container').show();
		myChart.showLoading({
			text:'统计中，请稍候...',
		    textStyle: { color: '#000' },
		    effectOption: {backgroundColor: 'rgba(0, 0, 0, 0.1)'}
		});
		$.get("/Home/InteSystem/getSupplyOut.html",params,function(res){
			if(res.code==200){
				var option = {
					backgroundColor: 'rgba(255,255,255,0.9)',
					color : ['#5ACD14','#FF8080','#8080FF'],
					legend: {
							show: true,
							data: ['收入','成本','利润'],
						},
				    title : {
				         x: 'center', 
				         textStyle: {
				            fontSize: 16,
				            color: '#1a72d6'
				        },
				        padding: 15, 
				    },
					tooltip: {
						trigger: 'axis',
						title: 'sdlf ',
					},
					calculable: true,
					xAxis: [{
						type: 'category',
						data: res.data.date
					}],
					yAxis: [{
						name:'单位：元',
						type: 'value',
						
					}],
					series: [{
						name: '收入',
						type: 'line',
						data: res.data.in
					},{
						name: '成本',
						type: 'line',
						data: res.data.out
					},{
						name: '利润',
						type: 'line',
						data: res.data.fit
					}]
				};
				myChart.setOption(option);

			}else{
				var ht="<div style='width:100%;text-align:center;line-height:400px;'>该供应商无成本显示</div>";
				$('#container').html(ht);
			}
			myChart.hideLoading();
			
		});
	},
	lazySupply:function(){
		$.get("/Home/InteSystem/getSupplyList",function(data){
			if(data){
				var h="";
				var gys_id = $("#gys_id").val();
				$.each(data,function(i,o){
					var se=gys_id==o.id?"selected='selected'":"";
					h +="<option value='"+o.id+"' "+se+">"+o.name+"</option>";
				});
				$("#ggs_name").html(h);
				$('#ggs_name').selectpicker();
			}
		});
	},
	query:function(){
		$('#query').val("查询中...");
		e.initAdver();
		$('#query').val("查询");
	},
	showTime:function(s_t,e_t,ob){
		$(".ds").removeClass("dayHover");
		$(ob).addClass("dayHover");
		$("#strtime").val(s_t);
		$("#endtime").val(e_t);
		e.initAdver();
		e.lazyProData(1);
	},
	clearBg:function(){
		$(".ds").removeClass("dayHover");
	},
	lazyProData:function(nowPage){
		var params = {};
		params.ggz_name = $("#ggs_name").val();
		params.strtime  = $("#strtime").val();
		params.endtime  = $("#endtime").val();
		params.gys_id   = $("#gys_id").val();
		params.p = nowPage;
		$("#proData").height(456);
		var ld='<tr id="loading"><td colspan="5"><div class="no-datas" id="nodatas">加载中...</div></td></tr>';
		$("#proData").html(ld);
		$.get("/Home/InteSystem/lazyGysProData",params,function(data){
			var h="";
			if(data.code==200){
				if(data.list.length<10) $("#proData").height("auto");
				$.each(data.list,function(i,o){
					h+='<tr>'
						+'<td>'+o.adddate+'</td>'
		        		+'<td>'+o.in_newdata+'</td>'
		            	+'<td>'+o.in_newmoney+'</td>'
		            	+'<td>'+o.out_newmoney+'</td>'
		            	+'<td>'+o.cb_money+'</td>'
	        		+'</tr>';
				});
        		$("#proData").html(h);
        		$("#pagen").html("");
                $("#pagen").html(data.page).show();
			}else{
				$("#nodatas").html("暂无数据！");
			}
		});
	}
};
$(function(){e.init();});