/**
 * [z description]
 * @type {Object}
 */
var z={
	init:function(){
		setTimeout(this.getBumenOrFuzeren(),500);
	},
	//读取部门或者负责人
	getBumenOrFuzeren:function(){
		$.get("/Home/IntelligenceSystem/getBumenOrFuzeren.html",{"is_super":500},function(data){
			var ht='<option value="">--全部--</option>';
			$.each(data.data,function(i,o){
				var se=d.fzr==o.id?"selected='selected'":"";
				ht+='<option value="'+o.id+'" '+se+'>'+o.name+'</option>';
			});
			$("#fzr").html(ht);
			$('#fzr').selectpicker();
		});
	},
	
};
$(function(){z.init();});