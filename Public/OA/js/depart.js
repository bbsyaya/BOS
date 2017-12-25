/**
 * 详细
 * @type {Object}
 */
var D={
	edit:function(id){
		$("#desc_"+id).hide();$("#bj_"+id).show();
		$("#kbj_"+id).hide();
	},
	canceledit:function(id){
		$("#desc_"+id).show();$("#bj_"+id).hide();
		$("#kbj_"+id).show();
	},
	saveDepart:function(id,type,depart_id){
		var params  = {};
		params.txt  = $("#txt_"+id).val();
		params.type = type;
		params.id   = depart_id;
		$("#btn_"+id).val("保存中...").attr("onclick","");
		$.post("/OA/OrganizSetting/saveDepartDesc",params,function(data){
			layer.alert(data.msg);
			window.location.reload();
		});
	},
	initbus:function(){
		$(".b-us").hover(function(){
			$(this).find(".b-hbj").show();
		},function(){
			$(this).find(".b-hbj").hide();
		});
	},
	init:function(){
		// this.initbus();
	}
};
$(function(){D.init();});