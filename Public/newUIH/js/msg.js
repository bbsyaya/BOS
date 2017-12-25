/**
 * [x description]
 * @type {Object}
 */
var x={
	intoRubbish:function(){
		layer.open({
		  type: 2,
		  title: '',
		  shadeClose: true,
		  shade: 0.8,
		  area: ['1200px', '50%'],
		  content: '/Home/Message/rubbishList.html' //iframe的url
		}); 
	},
	checkAll:function(obj){
		//data-status 1--未选中，2--选中
		var ckstatus = $(obj).attr("data-status");
		if(ckstatus=="1"){
			$(obj).attr("data-status","2");
			$(obj).find(".xx-ck").addClass('xx-ck-on');
			$(".ckitem").addClass('xx-ck-on');
		}else{
			$(obj).attr("data-status","1");
			$(obj).find(".xx-ck").removeClass('xx-ck-on');
			$(".ckitem").removeClass('xx-ck-on');
		}
	},
	changeStatus:function(){
		var st = $("#msgstatus option:selected").val();
		if(st==1){
			x.updateMsg(1);
		}
	},
	choseItem:function(id){
		var ck=$("#ck_"+id);
		if(ck.hasClass("xx-ck-on")){
			ck.removeClass('xx-ck-on');
		}else{
			ck.addClass('xx-ck-on');
		}
		x.checkIsChoseAll();
	},
	checkIsChoseAll:function(){
		var c=0;
		$(".ckitem").each(function(){
			if(!$(this).hasClass("xx-ck-on")){
				c--;
			}
		});
		if(c<0){
			$("#choseAll").removeClass("xx-ck-on");
		}else{
			$("#choseAll").addClass("xx-ck-on");
		}
	},
	isHasChose:function(type){
		var ret={
			isNull:false,
			ids:[]
		};
		var c=0,ids=[];
		$(".ckitem").each(function(){
			if($(this).hasClass("xx-ck-on")){
				//删除只能删除已读
				var status = $(this).attr('data-status');
				if(type==2 && status==1){
					c++;
					var id=$(this).attr('data-msgid');
					ids.push(id);
				}

				//标记已读
				if(type==1){
					c++;
					var id=$(this).attr('data-msgid');
					ids.push(id);
				}

				//标记已办理
				if(type==3){
					c++;
					var id=$(this).attr('data-msgid');
					ids.push(id);
				}
			}
		});
		if(c>0){
			var ret={
				isNull:true,
				ids:ids
			};
		}
		return ret;
	},
	deleteMsg:function(){
		//只能删除已读
		x.updateMsg(2);
	},
	isreadMsg:function(){
		x.updateMsg(1);
	},
	isuserMsg:function(){
		x.updateMsg(3);
	},
	updateMsg:function(type){
		var ret = x.isHasChose(type);
		if(ret.isNull){
			layer.msg("操作中...");
			//提交后台
			$.post("/Home/Message/updateStatus.html",{"status":type,"ids":ret.ids},function(data){
				if(data.code==200){
					layer.msg("操作成功");
					window.location.reload();
				}else{
					layer.msg("系统异常，请联系工程师");
				}
			});
		}else{
			var msg = type==2?"注意：只能删除已读信息":"您未选中任何消息";
			layer.msg(msg);
		}
	},
	searchMsg:function(){
		var content=$("#content").val();
		$("#hd_content").val(content);
		$("#sform").submit();
	},
	init:function(){
		x.listenEnter();
	},
	listenEnter:function(){
		$('#content').bind('keyup', function(event) {
		　　if (event.keyCode == "13") {
		　　　　//回车执行查询
		　　　　x.searchMsg();
		　　}
		});
	},
	backStatus:function(){
		x.updateMsg(1);
	}
}
$(function(){x.init();});