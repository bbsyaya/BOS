
/**
 * [In description]
 * @type {Object}
 */
var In={
	init:function(){
		this.getBumenOrFuzeren();
	},
	//读取部门或者负责人
	getBumenOrFuzeren:function(){
		$.get("/Home/IntelligenceSystem/getBumenOrFuzeren.html",function(data){
			var ht='<option value="">--全部--</option>';
			$.each(data.data,function(i,o){
				var se = de.rw_fze==o.id?"selected='selected'":"";
				ht+='<option value="'+o.id+'" '+se+'>'+o.name+'</option>';
			});
			$("#rw_fze").html(ht);
			$('#rw_fze').selectpicker();
		});
	},
	saveRw:function(type_){
		var params = {};
		params.id          =$("#hd_task_id").val();
		params.mr_id       =$("#hd_mr_id").val();
		params.rw_yj_title =$("#rw_yj_title").val();
		params.rw_fze      =$("#rw_fze").val();
		params.isnext      = type_;
		if(In._check(params)){
			var crobj = type_==1?$("#cj_rw_1"):$("#cj_rw");
			crobj.val("保存中...").attr("onclick","");
			
			$.post("/Home/IntelligenceSystem/saveRwDo.html",params,function(data){
				var href=window.parent.location.href;
				href=href.replace("issure","issure123");
				window.parent.location.href=href;
			});
		}
	},
	_check:function(params){
		var res = true;
		if(params.rw_yj_title==""){
			$("#rw_yj_title").focus();
			layer.msg("请输入任务标题");
			return false;
		}
		if(params.rw_fze==""){
			layer.msg("请选择部门负责人");
			return false;
		}
		return res;
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
	//直接进入下一步
	gonext:function(){
		var params = {};
		params.id          =$("#hd_task_id").val();
		params.mr_id       =$("#hd_mr_id").val();
		params.rw_yj_title =$("#rw_yj_title").val();
		params.rw_fze      =$("#rw_fze").val();
		if(In._check(params)){
			$("#cj_rw_1").val("保存中...").attr("onclick","");
			$.post("/Home/IntelligenceSystem/saveGoNext.html",params,function(data){
				var href=window.parent.location.href;
				href=href.replace("issure","issure123");
				window.parent.location.href=href;
			});
		}
	}
	
};
$(function(){In.init();});