
$(function() {
	if(getHref() == 'it.yandui.com_Home_Advertiser')
	{
		if (getCookie('boss30_on_' + getHref() + '_dataTable1_state') != 'activate' || getCookie('boss30_on_' + getHref() + '_dataTable1_state') == '' || getCookie('boss30_on_' + getHref() + '_dataTable1_state') == null || getCookie('boss30_on_' + getHref() + '_dataTable1_state') == undefined) {
			setCookie('boss30_on_' + getHref() + '_dataTable1', '1100011001', 30);
		}

		//查询字段初始化 2017.10.27
		if (getCookie('boss30_on_' + getHref() + '_t_left_state') != 'activate' || getCookie('boss30_on_' + getHref() + '_t_left_state') == '' || getCookie('boss30_on_' + getHref() + '_t_left_state') == null || getCookie('boss30_on_' + getHref() + '_t_left_state') === undefined) {
			setCookie('boss30_on_' + getHref() + '_t_left', '11110000', 30);
		}

	}else if(getHref() == 'it.yandui.com_Home_Report'){

		if(getCookie('boss30_on_'+getHref()+'_t_left_state')!='activate' || getCookie('boss30_on_'+getHref()+'_t_left_state')=='' || getCookie('boss30_on_'+getHref()+'_t_left_state')==null || getCookie('boss30_on_'+getHref()+'_t_left_state')==undefined) {
			setCookie('boss30_on_'+getHref()+'_t_left','11110000000000',30);
		}

	}else if(getHref() == 'it.yandui.com_Home_RiskCheckDetail'){

		if(getCookie('boss30_on_'+getHref()+'_t_left_state')!='activate' || getCookie('boss30_on_'+getHref()+'_t_left_state')=='' || getCookie('boss30_on_'+getHref()+'_t_left_state')==null || getCookie('boss30_on_'+getHref()+'_t_left_state')==undefined) {
			setCookie('boss30_on_'+getHref()+'_t_left','11110000',30);
		}
	}

	//菜单折叠
	$(".centerCa").click(function(){
		if($(this).hasClass("leftCa_close")){
			$(".leftCa").animate({ width:'200px', },300);
			$(".centerCa").animate({ left:'200px', },300);
			$(".rightCa").animate({ left:'210px', },300);
			$(this).removeClass("leftCa_close");
		}else{
			$(".leftCa").animate({ width:'0px', },300);
			$(".centerCa").animate({ left:'0px', },300);
			$(".rightCa").animate({ left:'10px', },300);
			$(this).addClass("leftCa_close");
		}
	});
	//子菜单
	$(".subMenuType").click(function() {
		$(".subMenuList").hide();
		$(".subMenuType").removeClass("focus");
		$(this).next(".subMenuList").show(300);
		$(this).addClass("focus");
	});
	//面包屑导航
	$(".crumbsNavCn .crumbsNav:last-child").css({
		"background": "none",
		"color": "#bbbbbb"
	});
	//筛选收折
	$(".J_openScreen").click(function() {
		$(this).parents(".screenCn").find(".screenList .expertScreen").toggle()
	});

	//数据表显示初始化
	var alltablelist=$('.dataTableShow_icon');
	alltablelist.each(function(j){
		var thisList=$(this).parents(".advancedTit").find(".dataTable_columnCn");
		var hrefV=getHref();
		var thispagedata=getCookie('boss30_on_'+hrefV+'_'+$(this).parents(".advancedTit").attr("goal"));

		if(thisList.find(".dataTable_columnSingle").length == 0){
			//数据列名填充
			$("."+$(this).parents(".advancedTit").attr("goal")).find("th").each(function(i){

				if($(this).text()=="" || $(this).text()=="操作"){
					thisList.append("<div class=\"dataTable_columnSingle dataTable_columnFocus\" style=\"display:none;\"></div>")
				}else if(thispagedata.substring(i,i+1)=='0'){
					thisList.append("<div class=\"dataTable_columnSingle\">"+$(this).text()+"</div>")
					// $("."+$(".dataTableShow").attr("goal")).find("th").eq(i).width(0);
					$("."+$(".advancedTit").attr("goal")).find("th").eq(i).css({"width":"0px"});
				}else{
					thisList.append("<div class=\"dataTable_columnSingle dataTable_columnFocus\">"+$(this).text()+"</div>")
				}
			})
		}
	});

	$(".dataTableShow_icon").click(function(){
		$(".dataTable_columnCa").toggle();
	});

	$(window).load(function() {

		//字段自定义初始化 2017.10.27
		var field_allTable = $('.field_customize');
		field_allTable.each(function(j){
			var thisList=$(this).parents(".subMainBox").find(".dataTable_columnCn_b");
			var hrefV=getHref();
			var thispagedata=getCookie('boss30_on_'+hrefV+'_'+$(this).parents(".subMainBox").attr("goal"));
			if(thisList.find(".dataTable_columnSingle").length == 0){
				//数据列名填充

				$("."+$(this).parents(".subMainBox").attr("goal")).find(".ziduan").each(function(i){
					if($(this).find("label").text()==""){
						thisList.append("<div class=\"dataTable_columnSingle dataTable_columnFocus\" style=\"display:none;\"></div>")
					}else if(thispagedata.substring(i,i+1)=='0'){
						thisList.append("<div class=\"dataTable_columnSingle\">"+$(this).find("label").text()+"</div>")
						// $("."+$(".dataTableShow").attr("goal")).find("th").eq(i).width(0);
						$("."+$(".subMainBox").attr("goal")).find(".ziduan").eq(i).hide();//css({"width":"0px"})
					}else{
						thisList.append("<div class=\"dataTable_columnSingle dataTable_columnFocus\">"+$(this).find("label").text()+"</div>")
					}
				})
			}
		});
	});

	$(".field_customize").click(function(){
		$(".dataTable_columnCa_b").toggle();
	});

	$(".dataTableShow_close_b").click(function(){
		$(".dataTable_columnCa_b").hide()
	});

	//数据表隐藏列筛选
	$(".dataTableShow_close").click(function(){
		$(".dataTable_columnCa").hide()
	});
	//数据表选中列
	$(".dataTable_columnCn").on({
		click: function() {
			var goalTable=$(this).parents(".advancedTit").attr("goal")
			var thisIndex=$(this).index();
			if($(this).hasClass("dataTable_columnFocus")){
				$(this).removeClass("dataTable_columnFocus");
				$("."+$(this).parents(".advancedTit").attr("goal")).find("th").eq(thisIndex).width(0);
			}else{
				$(this).addClass("dataTable_columnFocus");
				$("."+$(this).parents(".advancedTit").attr("goal")).find("th").eq(thisIndex).width("auto")
			};
			var nowdata=getalldata();
			var hrefV=getHref();
			setCookie('boss30_on_'+hrefV+'_'+$(this).parents(".advancedTit").attr("goal"),nowdata,30);
			setCookie('boss30_on_'+hrefV+'_'+$(this).parents(".advancedTit").attr("goal")+'_state','activate',30);
			$.post('/Ajax/save_list_session',{"url":window.location.href,"str":nowdata},function(){});
		}
	}, ".dataTable_columnSingle");


	//字段自定义点击 2017.10.27
	$(".dataTable_columnCn_b").on({
		click: function() {
			var goalTable=$(this).parents(".subMainBox").attr("goal");
			var thisIndex=$(this).index();

			if($(this).hasClass("dataTable_columnFocus")){
				$(this).removeClass("dataTable_columnFocus");
				//console.log(thisIndex);
				$("."+$(this).parents(".subMainBox").attr("goal")).find(".ziduan").eq(thisIndex).hide();
			}else{

				$(this).addClass("dataTable_columnFocus");
				$("."+$(this).parents(".subMainBox").attr("goal")).find(".ziduan").eq(thisIndex).show();
			};
			var nowdata=getalldata_field();
			//console.log(nowdata);return;
			var hrefV=getHref();
			setCookie('boss30_on_'+hrefV+'_'+$(this).parents(".subMainBox").attr("goal"),nowdata,30);
			setCookie('boss30_on_'+hrefV+'_'+$(this).parents(".subMainBox").attr("goal")+'_state','activate',30);
			$.post('/Ajax/save_list_session',{"url":window.location.href,"str":nowdata},function(){});
		}
	}, ".dataTable_columnSingle");


	//选项卡
	$(".tabCa").each(function(i){
		var thisTabCa=$(this);
		thisTabCa.find(".tab_menuList .tab_menu").click(function(){
			thisTabCa.find(".tab_menuList .tab_menu").removeClass("focus");
			$(this).addClass("focus");

			thisTabCa.find(".tab_contentWrapper .tab_content").hide();
			thisTabCa.find(".tab_contentWrapper .tab_content").eq($(this).index()).show();
		});
	});

	//隔行叉色
	//$(".dataTable tr:odd").addClass("dataTbodyTr_odd");
	//盘旋焦点
	/*var thisIndex;
	$(".dataCn").on({
		mouseover: function() {
			$(this).parent("tr").addClass("dataTbodyTr_focus");
			thisIndex = $(this).index();
			$(".dataTable tr").each(function(i) {
				$(this).find("td").eq(thisIndex).addClass("dataTbodyTr_focus");
			});
		},
		mouseout: function(){
			$(this).parent("tr").removeClass("dataTbodyTr_focus");
			thisIndex = $(this).index();
			$(".dataTable tr").each(function(i) {
				$(this).find("td").eq(thisIndex).removeClass("dataTbodyTr_focus");
			});
		},
	},".dataTable td");*/

	//全选反选
	$(".dataTable thead :checkbox").click(function() {
		if ($(this).is(":checked")) {
			$(".dataTable tbody tr td :checkbox").each(function(i){
				if(!$(this).is(':disabled'))$(this).attr("checked", "checked");
			});
		} else {
			$(".dataTable tbody tr td :checkbox").removeAttr("checked");
		}
	});
	$(".dataCn").on({
		click: function() {
			$(".dataTable tbody :checkbox").each(function(i) {
				if ($(this).is(":checked")) {

					$(".dataTable thead :checkbox").attr("checked", "checked");
				} else {
					$(".dataTable thead :checkbox").removeAttr("checked");
					return false;
				}
			});
		}
	}, ".dataTable tbody :checkbox");


	//弹出框全选反选
	$(".dialogTable_list thead :checkbox").click(function() {
		if ($(this).is(":checked")) {
			$(".dialogTable_list tbody tr td :checkbox").each(function(i){
				if(!$(this).is(':disabled'))$(this).attr("checked", "checked");
			});
		} else {
			$(".dialogTable_list tbody tr td :checkbox").removeAttr("checked");
		}
	});
	$(".dialogTable_list").on({
		click: function() {
			$(".dialogTable_list tbody :checkbox").each(function(i) {
				if ($(this).is(":checked")) {
					$(".dialogTable_list thead :checkbox").attr("checked", "checked");
				} else {
					$(".dialogTable_list thead :checkbox").removeAttr("checked");
					return false;
				}
			});
		}
	}, ":checkbox");


	//删除数据
	$(".dataTable,.dynamicTable").on({
		click: function() {
			$(this).parents("tr").remove()
		}
	}, ".J_deleteData,.dataDelete");

	//日志
    $(".dataTable").on({
        mouseover: function() {
            if($(this).attr('datatype')=='datalog'){
                if($(this).find('ul').html()==''){
                    var thisTd=$(this)
                    $.post('/Ajax/Getdatalog',{'id':$(this).attr('dataid'),'type':$(this).attr('datatype2')},function(res){
                        var ulhtml='';
                        for(var i in res){
                            ulhtml+="<li><span>"+res[i].addtime+"</span><span>"+res[i].username+"</span><span>"+res[i].remark+"</span></li>";
                        }
                        thisTd.find('ul').html(ulhtml);
                        thisTd.find(".logCn").css({
                            "left":(thisTd.offset().left-thisTd.find(".logCn").width())-210+"px",
                        })
                        thisTd.find(".logCn").show();
                    },'json');
                }else{
                    $(this).find(".logCn").css({
                        "left":($(this).offset().left-$(this).find(".logCn").width())-210+"px",
                    })
                    $(this).find(".logCn").show();
                }
            }else{
                $(this).find(".logCn").css({
                    "left":($(this).offset().left-$(this).find(".logCn").width())-210+"px",
                })
                $(this).find(".logCn").show();
            }
        },
        mouseout: function(){
            $(this).find(".logCn").hide();
        }
    },".J_log");



	//普通对话框
	$(".dialog_bottom :button,.dialog_bottom :submit,.dialog_close").click(function(){
		$(this).parents(".dialog_ca").hide();
		$(this).parents(".dialog_ca").css({ marginLeft:'0px', marginTop:'0px'});
		$("#conceal").remove();
	});

	//缩略统计是否展开
	$(".J_statisticsThumbnail").click(function(){
		$(".statisticsThumbnail").show();
		$(".statisticsThumbnail").animate({ left:'0%', },400);
		setCookie('statisticsState_'+window.location.href,'true',30);
	});
	$(".statisticsThumbnail_close").click(function(){
		$(".statisticsThumbnail").animate({ left:'100%', },400);
		setCookie('statisticsState_'+window.location.href,'false',30);
	});
	if(getCookie('statisticsState_'+window.location.href)=="true"){
		$(".statisticsThumbnail").css('left','0%');
	}else{
		$(".statisticsThumbnail").css('left','100%');
	}

	//缩略统计选项卡切换
	$(".singleFiltrate").click(function(){
		$(".singleFiltrate").removeClass("focus");
		$(this).addClass("focus");
	});

	//是否检查
	$(".switchBtn").click(function(){
		if($(this).hasClass("switchBtn_on")){
			$(this).removeClass("switchBtn_on");
		}else{
			$(this).addClass("switchBtn_on");
		}
	});


});