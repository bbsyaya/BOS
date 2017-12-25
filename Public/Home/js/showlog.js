/**
 * [log description]
 * @type {Object}
 */
var log={
	showLog:function(lid){
		var obj=$("#log_"+lid);
		obj.find("a").html("加载中....");
		var loghtml = $("#show_log_"+lid).html();
		if(loghtml=="" ||  loghtml==undefined){
			loghtml = '<div  id="show_log_'+lid+'" ><div class="logCn pa"style="display:block;"><ul>%logtxt%</ul></div></div>';
			$.post('/Ajax/Getdatalog',{'id':obj.attr('dataid'),'type':obj.attr('datatype2')},function(res){
	            var ulhtml='';
	            for(var i in res){
	                ulhtml+="<li><span>"+res[i].addtime+"</span><span>"+res[i].username+"</span><span>"+res[i].remark+"</span></li>";
	            }
	            loghtml = loghtml.replace("%logtxt%",ulhtml);
	            $("body").append(loghtml);
	            obj.find("a").html("操作日志");
	            layer.open({
				  title:"查看日志",
				  type: 1,
				  skin: 'layui-layer-rim', //加上边框
				  area: ['30%', '40%'], //宽高
				  content: loghtml
				});
	        },'json');
		}else{
			layer.open({
			  title:"查看日志",
			  type: 1,
			  skin: 'layui-layer-rim', //加上边框
			  area: ['30%', '40%'], //宽高
			  content: loghtml
			});
			obj.find("a").html("操作日志");
		}
		
	}
}