/**
 * [In description]
 * @type {Object}
 */
var In={
	init:function(){
		setTimeout(In.initGetCanY(),1000);
	},
	//加载参与人
	initGetCanY:function(){
		$.get("/Home/IntelligenceSystem/ajaxZqbCanYuRen.html",function(data){
			if(data.length>0){

				var h="",cyr_h="";
				var cyr_list = ed.zqb_cyr_ids.split(",");
				$.each(data,function(i,o){
					var fzr_sec= o.id==ed.zqb_fzr?"selected='selected'":"";
					h+='<option value="'+o.id+'" '+fzr_sec+'>'+o.real_name+'</option>';

					var cyr_sec = In.cyr_isIn(cyr_list,o.id)==true?"selected='selected'":"";;

					cyr_h+='<option value="'+o.id+'" '+cyr_sec+'>'+o.real_name+'</option>';
				});

				$("#zqb_fzr").html(h);
				$('#zqb_fzr').selectpicker();

				$("#zqb_cyr_ids").html(cyr_h);
				$('#zqb_cyr_ids').selectpicker({
					selectAllText: '全选',
			        deselectAllText: '全不选'
				});
			}
		});
	},
	cyr_isIn:function(cyr_list,nowID){
		var a=false;
		$.each(cyr_list,function(i,o){
			if(o==nowID){
				a=true;
			}
		});
		return a;
	},
	createQb:function(){
		var params={};
		params.zqb_name    =$("#zqb_name").val();
		params.zqb_fzr     =$("#zqb_fzr").val();
		params.zqb_jhwcsj  =$("#zqb_jhwcsj").val();
		params.zqb_ms      =$("#zqb_ms").val();
		params.zqb_cyr_ids =$("#zqb_cyr_ids").val();
		params.zqb_id      = ed.zqb_id;
		if(In._check(params)){
			$("#cjbtn").attr("onclick","").val("不要关闭浏览器...");
			$.post("/Home/IntelligenceSystem/ajaxcreateQb.html",params,function(data){
				if(data.id){
					if(parseInt(ed.zqb_id)>0){
						layer.msg("修改成功");
						window.location.reload();
					}else{
						// window.parent.location.href="/Home/IntelligenceSystem/gatherworkflow.html?qbid="+data.id;
						window.parent.location.reload();
					}
					
				}else{
					layer.msg("创建失败，请联系技术！");
				}
				
			});
		}
	},
	_check:function(params){
		var res=true;
		if(params.zqb_name==""){
			$("#zqb_name").focus();
			layer.msg("请输入主情报名称");
			return false;
		}
		if(params.zqb_jhwcsj==""){
			$("#zqb_jhwcsj").focus();
			layer.msg("请选择计划完成时间");
			return false;
		}
		if(params.zqb_ms==""){
			$("#zqb_ms").focus();
			layer.msg("请输入主情报描述");
			return false;
		}
		return res;
	},
	//确认情报
	sureZuQingBao:function(){
		var params={};
		params.issure    =$("input[name='is_fenjie']:checked").val();
		params.zqb_id      = ed.zqb_id;
		$("#cjbtn").attr("onclick","").val("不要关闭浏览器...");
		$.post("/Home/IntelligenceSystem/ajaxSureZuQingBao.html",params,function(data){
			if(data.code==200){
				layer.msg("确认成功",{time:2000});
				setTimeout(function(){
					window.parent.location.href='/Home/IntelligenceSystem/gatherworkflow.html?qbid='+params.zqb_id+"&issure="+params.issure;
				},2000);
			}
				
		});
	}


};
$(function(){In.init();})