/**
 * 
 * @type {Object}
 */
var or={
	showImg:function(){
		var he=$(window).height(),wd=$(window).width(),zswd="761px",zswds="1150px";
		if(he<=760){
			zswd="92%";
		}
		if(wd<=1200){
			zswds="92%";
		}
		layer.open({
		  type: 2,
		  title: '',
		  shadeClose: true,
		  shade: 0.5,
		  area: [zswds, zswd],
		  content: '/OA/OrganizSetting/crateImg.html' //iframe的url
		}); 
	}
};