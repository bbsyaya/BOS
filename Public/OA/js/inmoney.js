/**
 * [In description]
 * @type {Object}
//  */
var In={
	pageType: "",
	init:function(){
		this.pageSwitch();
	},
	 pageSwitch: function() {
        if ("undefined" != typeof pType) {
            In.pageType = pType
        }

        switch (In.pageType) {
	        case "inmoneyCheckTotal":
                In.getData("inmoneyCheckTotal");
	        break;
	        case "invoiceCheck":
	        	setTimeout(this.lazyOutId,1000);
	        break;
	         case "inmoneyCheck":
	        	setTimeout(this.lazyOutId,1000);
	        break;
	        case "outmoneyCheckTotal":
	        	In.getData("outmoneyCheckTotal");
	        break
	        case "outmoneyCheck":
	        	setTimeout(this.lazyOutId,1000);
	        	setTimeout(this.lazyTotalOut,2000);
	        break;
        }
    },
    lazyTotalOut:function(){
    	var params = {};
		params.boss_sdate = $("#boss_sdate").val();
		params.boss_edate = $("#boss_edate").val();
		params.lineid     = $("#lineid").val();
		params.supname    = $("#supname").val();
    	$.get("/Home/Voucher/ajaxGetOutDetaiTotal",params,function(data){
    		if(data.status==200){
    			$("#total_boss_jiesuan_money").attr("title",data.total_boss_jiesuan_money).html(data.total_boss_jiesuan_money);
    			$("#total_yongyong").attr("title",data.total_yongyong).html(data.total_yongyong);
    			$("#total_bank").attr("title",data.total_bank).html(data.total_bank);
    		}else{
    			$("#total_boss_jiesuan_money").attr("title",data.msg).html(data.msg);
    			$("#total_yongyong").attr("title",data.msg).html(data.msg);
    			$("#total_bank").attr("title",data.msg).html(data.msg);
    		}
    	});
    },
    lazyOutId:function(){
 		$.get("/Home/ChargingLogo/lazyOutId.html",function(data){
            var ht='<option value="">全部</option>';
            if(data){
                $.each(data,function(i,o){
                    var ckd= ck==o.id?"selected='selected'":"";
                    ht+='<option value="'+o.id+'" '+ckd+'>'+o.name+'</option>';
                });
            }
            $("#lineid").html(ht);
        });
 	},
	getData:function(type){
		$("#loadedindex").val(0);
		var i=0;
		$(".month").each(function(){
			var t        =$(this),params={};
			params.start = t.attr("data-start");
			params.end   = t.attr("data-end");
			params.month = t.attr("data-index");
			params.type  = type;
			
			// if(i==0){
			// 	In.doSetTimeout(params);
			// 	i++;
			// }
			In.doSetTimeout(params);
		});
	},
	doSetTimeout:function(params){
		setTimeout(function() {  In.ajaxGet(params); }, 3000 * params.month);
	},
	ajaxGet:function(params){
		var ajax_url = "/Home/Voucher/getInMoneyData.html";
		if(params.type=="outmoneyCheckTotal"){
			ajax_url = "/Home/Voucher/getOutMoneyData.html";
		}
		$.get(ajax_url,params,function(data){
			var ht="";
			if(parseFloat(data.totalIncom)>=0){
				var n1 = new Number(data.totalIncom);
				var qfw = In.commafy(n1.toFixed(2));
				ht='<a href="/Home/ShowDataImg/profit.html" class="clin" title="查看可视化报表" target="_blank">'+qfw+'</a>';
				$("#zsr_"+params.month).html(ht);
				$("#zsr_"+params.month).attr("data-money",data.totalIncom);
			}
			if(parseFloat(data.sureIncom)>=0){
				var inurl = "inmoneyCheck";
				if(params.type=="outmoneyCheckTotal"){
					inurl = "outmoneyCheck";
				}
				var n2 = new Number(data.sureIncom);
				var qfw1 = In.commafy(n2.toFixed(2));
				ht='<a href="/Home/RiskCheckStatistics/'+inurl+'.html?boss_sdate='+params.start+'&boss_edate='+params.end+'" class="clin" title="查看当月明细">'+qfw1+'</a>';
				$("#qzsr_"+params.month).html(ht);
				$("#qzsr_"+params.month).attr("data-money",data.sureIncom);
			}
			if(parseFloat(data.yy_finaTotal)>=0){ 
				var n3 = new Number(data.yy_finaTotal);
				var qfw2 = In.commafy(n3.toFixed(2));
				$("#yyzsr_"+params.month).html(qfw2); 
				$("#yyzsr_"+params.month).attr("data-money",data.yy_finaTotal);
			}
			if(parseFloat(data.bankPay)>=0){ 
				var n4 = new Number(data.bankPay);
				var qfw3 = In.commafy(n4.toFixed(2));
				$("#bankzsr_"+params.month).html(qfw3);
				$("#bankzsr_"+params.month).attr("data-money",data.bankPay);
			}
			
			if(parseFloat(data.totalIncom)>=0 && parseFloat(data.sureIncom)>=0 && parseFloat(data.yy_finaTotal)>=0 && parseFloat(data.bankPay)>=0){
				var loadedindex = $("#loadedindex").val();
				loadedindex = parseFloat(loadedindex);
				loadedindex++;
				$("#loadedindex").val(loadedindex);
				var mlen = $(".month").length;
				if(loadedindex==mlen){
					//计算总和
					In.getTotal(params.month);
				}
			}
			
		});
	},
	getTotal:function(month){
		var zsr_total = 0,qzsr_total=0,yyzsr_total=0,bankzsr_total=0;
		for (var i = 1; i <=month; i++) {
			var ht_zsr = $("#zsr_"+i).attr("data-money");
			zsr_total +=parseFloat(ht_zsr);
			var ht_qzsr = $("#qzsr_"+i).attr("data-money");
			qzsr_total +=parseFloat(ht_qzsr);
			var ht_yyzsr = $("#yyzsr_"+i).attr("data-money");
			yyzsr_total +=parseFloat(ht_yyzsr);
			var ht_bankzsr = $("#bankzsr_"+i).attr("data-money");
			bankzsr_total +=parseFloat(ht_bankzsr);
		};
		var n1 = new Number(zsr_total);
		$("#zsr_total").html(In.commafy(n1.toFixed(2)));
		var n2 = new Number(qzsr_total);
		$("#qzsr_total").html(In.commafy(n2.toFixed(2)));
		var n3 = new Number(yyzsr_total);
		$("#yyzsr_total").html(In.commafy(n3.toFixed(2)));
		var n4 = new Number(bankzsr_total);
		$("#bankzsr_total").html(In.commafy(n4.toFixed(2)));
	},
	loadMoreInvince:function(invoinceNo,obj){
		if(invoinceNo==undefined || invoinceNo==""){return false;};
		var url = '/Home/Voucher/getMoreInvoinceData.html';
		var o = $(obj);
		o.html("加载中...").attr("onclick","");
		$.get(url,{invoinceNo:invoinceNo},function(data){
			if(data.code=="0"){
				var ht="";
				$.each(data.data,function(i,ov){
					var cs= i%2==0 ? "zk-pfls_js" : "zk-pfls_os";
					ht+='<tr class="'+cs+'">'
						+'<td>'+ov.csign_ino_id+'</td>'
						+'<td title="'+ov.itemName+'">'+ov.itemName+'</td>'
						+'<td title="'+ov.cusName+'">'+ov.cusName+'</td>'
						+'<td>'+ov.money+'</td>'
						+'<td>'+ov.dDate+'</td>'
						+'</tr>';
				});
				$("#table_pz_"+invoinceNo).html(ht);
			}else{
				layer.alert(data.msg);
			}
		});
	},
	commafy:function(num){
		if(num==""){
			return 0;
		}
		if(isNaN(num)){
			return 0;
		}
		num = num+"";
		if(/^.*\..*$/.test(num)){
			var pointIndex =num.lastIndexOf(".");
			var intPart = num.substring(0,pointIndex);
			var pointPart =num.substring(pointIndex+1,num.length);
			intPart = intPart +"";
			var re =/(-?\d+)(\d{3})/
			while(re.test(intPart)){
				intPart =intPart.replace(re,"$1,$2")
			}
			num = intPart+"."+pointPart;
		}else{
			num = num +"";
			var re =/(-?\d+)(\d{3})/
			while(re.test(num)){
				num =num.replace(re,"$1,$2")
			}
		}
		return num;
	}
};
$(function(){In.init();});