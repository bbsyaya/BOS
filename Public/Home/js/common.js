function setCookie(c_name, value, expiredays) {
	var exdate = new Date();
	exdate.setDate(exdate.getDate() + expiredays);
	document.cookie = c_name + "=" + escape(value) + ((expiredays == null) ? "" : ";expires=" + exdate.toGMTString()) + ";path=/;"; //domain=localhost	　　
}

function getCookie(c_name) {
	//先查询cookie是否为空，为空就return ""
	if(document.cookie.length > 0) {
		//通过String对象的indexOf()来检查这个cookie是否存在，不存在就为 -1
		c_start = document.cookie.indexOf(c_name + "=")
		if(c_start != -1) {
			//最后这个+1其实就是表示"="号啦，这样就获取到了cookie值的开始位置
			c_start = c_start + c_name.length + 1
			//其实我刚看见indexOf()第二个参数的时候猛然有点晕，后来想起来表示指定的开始索引的位置...这句是为了得到值的结束位置。因为需要考虑是否是最后一项，所以通过";"号是否存在来判断
			c_end = document.cookie.indexOf(";", c_start)
			if(c_end == -1) c_end = document.cookie.length
			//通过substring()得到了值。想了解unescape()得先知道escape()是做什么的，都是很重要的基础，想了解的可以搜索下，在文章结尾处也会进行讲解cookie编码细节
			return unescape(document.cookie.substring(c_start, c_end))
		}
	}
	return "";
}

//获取数据表显示状态
function getalldata(){
	var datastr='';
	$(".dataTable_columnCn").find('.dataTable_columnSingle').each(function(){
		if($(this).hasClass("dataTable_columnFocus")) datastr+='1';
		else datastr+='0';
	})
	return datastr;
}

function getalldata_field(){
	var datastr='';
	$(".dataTable_columnCn_b").find('.dataTable_columnSingle').each(function(){
		if($(this).hasClass("dataTable_columnFocus")) datastr+='1';
		else datastr+='0';
	})
	return datastr;
}

//获取地址
function getHref(){
	var hrefV=window.location.href.toString();
	var cookieV="";
	hrefV=hrefV.replace("?","/");
	hrefV=hrefV.replace(".html","");
	hrefV=hrefV.split("//");	
	hrefV=hrefV[1].split("/");
	for(i=0;i<3;i++){
		cookieV+=hrefV[i]+"_";
	}
	cookieV=cookieV.substring(0,cookieV.length-1);

	return cookieV;
}
