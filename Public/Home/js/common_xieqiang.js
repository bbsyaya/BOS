$(function(){
	$('body').append('<div class="opennewpage" title="弹出提示" style="text-align: center;word-wrap:break-word;padding:25px;"></div><iframe style="display:none;" id="newpageiframe" name="newpage"></iframe>');
	//对话框批数据导入
			
	//数据导入对话框
	
})
function alertnewpage(id){
	if(id==1){
		$('.opennewpage').html('操作成功');
	}else if(id==0){
		$('.opennewpage').html('操作失败');
	}else{
		$('.opennewpage').html(id);
	}
	$(".opennewpage").dialog({
		autoOpen: false,
		resizable: false,
		width: "450",
		height: "250",
		modal: true,
		show: "scale",
		buttons: {
			"确定":function() {
				$(this).dialog("close");
				window.location.reload();
			}
		},
	});		
	$(".opennewpage").dialog("open");
}
function alertnewpage2(id, go) {
	var thisDialog = $('.opennewpage');
	$('.opennewpage').html(id);
	$(".opennewpage").dialog({
		autoOpen: false,
		resizable: false,
		modal: true,
		buttons: {
			"确定":function() {
				$(this).dialog("close");
				if(go)window.location.href=go;

			}
		},
	});		
	$(".opennewpage").dialog("open");
	return thisDialog;
}

function alertnewpage3(id, go) {
	var thisDialog = $('.opennewpage');
	$('.opennewpage').html(id);
	$(".opennewpage").dialog({
		autoOpen: false,
		resizable: false,
		modal: true,
	});		
	$(".opennewpage").dialog("open");
	setTimeout(function(){
		$(".opennewpage").dialog("close");
	},500);
	return thisDialog;

}