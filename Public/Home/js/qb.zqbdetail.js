/**
 * [z description]
 * @type {Object}
 */
var z={
	init:function(){
		// setTimeout(this.loadTaskList(),500);
		// setTimeout(this.loadGenJin(),500);
		setTimeout(this.getBumenOrFuzeren(),500);
	},
	//读取部门或者负责人
	getBumenOrFuzeren:function(){
		$.get("/Home/IntelligenceSystem/getBumenOrFuzeren.html",{"is_super":500},function(data){
			var ht='<option value="">--全部--</option>';
			$.each(data.data,function(i,o){
				ht+='<option value="'+o.id+'">'+o.name+'</option>';
			});
			$("#fzr").html(ht);
			$('#fzr').selectpicker();
		});
	},
	loadTaskList:function(){
		$.get("/Home/IntelligenceSystem/getTaskList.html",_de,function(data){
			if(data.list.length>0){
				var ht="";
				$.each(data.list,function(i,o){
						ht+='<div class="d-f qb-xxins-item">';
						ht+='<div class="d-f qb-titls-linea">';
						ht+='<span class="qb-kitks qb-item-inde " style="margin-right:0px;">'+(i+1)+'、情报任务：'+o.head_title+'</span>';
						// ht+='<span class="qb-kitks d-f"></span>';
						ht+='<span class="qb-kitks" style="margin-left:17.5%;">负责人：'+o.real_name+'</span>';
						ht+='</div>';

						ht+='<div class="d-f qb-titls-linea">';

						var zd_list_1 = "";
						if(o.zd_list.length>0){
							$.each(o.zd_list,function(zi1,zo1){
								var mg=zi1==0?'style="margin-left:20px;"':"";
								zd_list_1+='<div class="d-f" style="padding-bottom:10px;"><span class="qb-kitks" '+mg+'>'+zo1.name+'：'+zo1.demand_val+'</span></div>';
							});
						}
						ht +=zd_list_1;

						//子任务
						var zrw_html="";
						if(o.zrw_list.length>0){
							$.each(o.zrw_list,function(zrw_i,zrw_o){
								zrw_html+='<div style="" class="zrw-secls d-f">';
								zrw_html+='<div class="zwr-list d-f">';
								zrw_html+='<div class="d-f qb-titls-linea ">';
								zrw_html+='<span class="qb-kitks qb-item-inde zwr-cl" style="margin-right:0px;">('+(zrw_i+1)+')、</span>';
								zrw_html+='<span class="qb-kitks zwr-cl">子情报任务：--</span>';
								zrw_html+='<span class="qb-kitks zwr-cl">负责人：'+zrw_o.real_name+'</span>';
								zrw_html+='</div>';
								zrw_html+='<div class="d-f qb-titls-linea" style="padding-bottom:10px;">';

								var zd_list_2 = "";
								if(zrw_o.zd_list.length>0){
									$.each(zrw_o.zd_list,function(zi2,zo2){
										var mg1=zi2==0?'style="margin-left:20px;"':"";
										zd_list_2+='<span class="qb-kitks" '+mg1+'>'+zo2.name+'：'+zo2.demand_val+'</span>';
									});
								}
								zrw_html +=zd_list_2;

								zrw_html +='</div>';
								zrw_html +='</div>';
								zrw_html +='</div>';


								
							});
						}
						
						ht+=zrw_html;
						ht+='</div>';
						ht+='</div>';
				});

				$("#zqb_taskList").html(ht);
			}else{
				$("#loadTask").html("暂无任务列表！");
			}
		});
	},
	loadGenJin:function(){
		$.get("/Home/IntelligenceSystem/getGengJin.html",_de,function(data){
			if(data.list.length>0){
				var ht="";
				$.each(data.list,function(i,o){
					var remark = "";
					if(o.remark){
						remark = ' <font style="color:#999;">(备注：'+o.remark+')</font>';
					}
					ht+='<div class="d-f qblijls"><span class="qb-listjsl">'+o.visit_time+'     '+o.real_name+'跟进   情况：'+o.result+remark+'  </span></div>';
				});
				$("#zqb_gj").html(ht);
			}else{
				$("#loadjd").html("暂无跟进！");
			}
		});
	},
	saveSum:function(){
		var params={};
		params.int_sum =$("#int_sum").val();
		params.zqb_id=_de.zqb_id;
		if(params.int_sum){
			$("#qr_sum").attr("onclick","").val("操作中...");
			$.post("/Home/IntelligenceSystem/saveSumDo.html",params,function(data){
				layer.msg(data.msg);
				window.location.reload();
			});
		}else{
			layer.msg("输入点总结才能保存！");
		}
	},
	zprint:function(){
		var url='/Home/IntelligenceSystem/taskdetail.html?zqb_id=15&print=yes';
		window.parent.open(url);
		// var headContent = $('head').html();  
		// var style_='<style>.titz{display:none;}</style>';
		// var printStr = "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
		// 	printStr +="<script src=\"/Public/Home/module/jquery-2.1.1.min.js\" type=\"text/javascript\"></script></head><body style='width:1200px;margin:0px auto;'>";  
		// var content  = "<script>function zzdy(){$(\".zzdy\").hide();window.print();$(\".zzdy\").show();}</script>";  
		
		// var str      = $("#myPrintArea").html();     //获取需要打印的页面元素 ，page1元素设置样式page-break-after:always，意思是从下一行开始分割。  
		// content      = content + str;  
		// printStr     = printStr+style_+content+"</body></html>";  
		// printStr = printStr.replace("onclick=\"z.zprint()\"","onclick='zzdy()'");                                              
		// var pwin     =window.open("/Home/IntelligenceSystem/wprint.htm","print"); //如果是本地测试，需要先新建Print.htm，如果是在域中使用，则不需要  
		// pwin.document.write(printStr);  
		// pwin.document.close();                   //这句很重要，没有就无法实现    
		// pwin.print();  
	},
	showEdit:function(){
		$("#int_sum").show();
		$("#bj_body").hide();
		$("#cancle_sum").show();
		$("#qr_sum").show();
	},
	cancleSum:function(){
		$("#int_sum").hide();
		$("#bj_body").show();
		$("#cancle_sum").hide();
		$("#qr_sum").hide();
	},
	saveSumStatus:function(){
		var sum = $("#sum_status option:selected");
		var sum_status = sum.val(),sum_txt=sum.text();
		if(sum_status!=""){
			var lindex = layer.confirm("您确定这是一个"+sum_txt,function(){
				layer.close(lindex);
				var msgindex = layer.msg("评定中...",{time:100000});
				var params={};
				params.sum_status=sum_status;
				params.zqb_id=_de.zqb_id;
				$.post("/Home/IntelligenceSystem/saveSumStatusDo.html",params,function(data){
					layer.close(msgindex);
					layer.msg(data.msg,{time:2000});
					setTimeout(function(){window.location.reload();},2000);
				});
			});
		}
		
	}
};
$(function(){z.init();});