/**
 * [M description]
 * @type {Object}
 */
var MaoPao={
	stopmp:function(e){
		var evt = e ;
		e.stopPropagation();
	},
	getEvent:function(event) {
		var ev = event || window.event;
		if (!ev) {
			var c = this.getEvent.caller;
			while (c) {
				ev = c.arguments[0];
				if (ev && (Event == ev.constructor || MouseEvent == ev.constructor)) {
					break;
				}
				c = c.caller;
			}
		}
		return ev;
	}
};

var title_arry = [],data_arry = [];


//模态框数据
var status = '';
var _id = '';
var model_id = ''
var other_arr = {
    type_arr:[],
    childen_list:[],
};


//渲染页面数据
var data_str = ''
var data_id = []
var _model_id = []
var model_arry = []
var newmodel_arry = title_arry;
var _data_str = '';



var M={
	init:function(){
		this.initLoadUserHasAuth();
		
		this.initOldBossJsFun();
	},
	//加载原有的boss-index.js代码
	initOldBossJsFun:function(){


		//右上企业文化
		var mySwiper = new Swiper('.qywh_swiper', {
            autoplay: 5000,//可选选项，自动滑动
            autoplayDisableOnInteraction: false,
            paginationClickable: true,
            loop: true
        });
        $(" .swiper_slide_prev").click(function () {
            mySwiper.slidePrev();
            // console.log(111)
        });
        $(" .swiper_slide_next").click(function () {
            mySwiper.slideNext();
            // console.log(222)
        });
        $(".mask #btn_close").click(function () {
            $(".mask").fadeOut();
            _click_id = ''
            click_id = ''
            $('.go_tihuan').removeClass('boot_click')
        });
        $(".mask").click(function (event) {
            if (event.target == this) {
                $(".mask").fadeOut();
                _click_id = ''
                click_id = ''
                $('.go_tihuan').removeClass('boot_click')
            }

        });

		// 注销
			$("#log_off").mouseenter(function () {
			    $(this).css('background', 'url(/Public/newUIH/images/go_off_a.png)no-repeat center')
			}).mouseleave(function () {
			    $(this).css('background', 'url(/Public/newUIH/images/go_off.png)no-repeat center')
			});



			// 功能模块移入移出/点击事件
			$(".section_do_left").delegate(".delegate_add", "mouseover", function () {
			    $(this).addClass('add')

			}).delegate(".delegate_add", "mousemove", function (e) {
			   if (title_arry.length > 5){
			       var x = e.pageX;
			       var y = e.pageY;

			       var zY = y - $(this).offset().top;
			       var zX = x - $(this).offset().left;
			       // console.log(zX)
			       if (zY <= 50 && zX >= 115) {
			           $(this).find('.section_do_clo').css('display', 'block')
			       } else {
			           $(this).find('.section_do_clo').css('display', 'none')
			       }
			   }

			}).delegate(".delegate_add", "mouseleave", function () {
			    $(this).removeClass('has_section_do_hover')
			     $(this).removeClass('add')

			});

		


			// 待办事项
			$('.section_willdo_list').mouseenter(function () {
			    $(this).addClass('has_boeder_shadow')

			}).mouseleave(function () {
			    $(this).removeClass('has_boeder_shadow')

			});


			// 添加 模态框
			$('.section_do_left').on({
			    click: function (event) {

			        status = '添加'

			        apdate_data();
			        apdata_model_data();
			         $(".section_box").hide();
			        $('.my_model_add').css({'z-index': '99', 'opacity': '1'})
			        $('.my_model_add').find('.model_header_title').html('添加');
			        $('.my_model_add').find('#model_header_title_y').html('INCREASE');
			        $('#model_btn_add').css('display', 'block')
			        // event.stopPropagation();//阻止事件冒泡
			        MaoPao.stopmp(MaoPao.getEvent());
			    }
			}, '#go_model_add');


			// 移入移出 其它
			$('#go_model_other').mouseenter(function () {

			    $(this).addClass('add')
			}).mouseleave(function () {

			    $(this).removeClass('add')
			});
			$('#go_model_other').on("mousedown", function (event) {
			    $(this).addClass('has_section_do_hover')
			}).on("mouseup", function (event) {
			    $(this).removeClass('has_section_do_hover')
			})


			// 其它 点击
			$('.section_do_left').on({
			    click: function (event) {

			        status = '其它';
			        apdate_data();
			        apdata_model_data();
			         $(".section_box").hide();
			        $('.my_model_add').css({'z-index': '99', 'opacity': '1'})
			        $('.my_model_add').find('.model_header_title').html('其它');
			        $('.my_model_add').find('#model_header_title_y').html('OTHER');
			        $('#model_btn_add').css('display', 'none')
			        // event.stopPropagation();//阻止事件冒泡
			        MaoPao.stopmp(MaoPao.getEvent());
			    }
			}, '#go_model_other');


			//关闭模态框
			$('.model_close').click(function () {

			    clearstatus()
			    $(this).parents('.my_model').css({'z-index': '-10', 'opacity': '0'});
			    $(".section_box").show();

			});


			$('.my_model').click(function () {
				MaoPao.stopmp(MaoPao.getEvent());
				    clearstatus()
			        $(this).css({'z-index': '-10', 'opacity': '0'});
			         $(".section_box").show();
			    // if (window.event.target == this) {

			    //     clearstatus()
			    //     $(this).css({'z-index': '-10', 'opacity': '0'})

			    // }

			});





			// 模态框内元素点击事件
			$('.model_text').on({
			    click: function (event) {
			        var go_id =''

			        var vul = $(this).text();
			        if (status == '添加' || status == '替换' || status == '其它') {
			            $('.model_text_type>div').each(function (i, ele) {


			                if ($(ele).text() == vul) {
			                    $(this).addClass('has_hover')
			                    $.each(title_arry, function (idx, obj) {
			                        if (obj.text == vul) {
			                            model_id = obj.id;
			                            go_id = obj.id
			                            // console.log(model_id, 'id')
			                        }
			                    })
			                } else {
			                    $(this).removeClass('has_hover')
			                }
			            })
			            if (status == '其它') {
			                 $.each(title_arry, function (idx, obj) {
			                    if(go_id==obj.id){
			                        var url=obj.url;
			                        window.open('/Home/Index/main?mainurl='+url);
			                    }
			                })
			               
			            }

			        }
			        // event.stopPropagation();//阻止事件冒泡
			        MaoPao.stopmp(MaoPao.getEvent());
			    }
			}, '.model_text_type>div');


			// 模态框确认事件
			$('#model_btn_add').click(function () {
			    if (status == '添加') {

			        // console.log(model_id, '添加的model_id')
			        if (model_id != '') {
			            $.each(title_arry, function (idx, obj) {
			                if (obj.id == model_id) {
			                    var id = obj.id;
			                    var text = obj.text;
			                    var str = '<div class="delegate_add" data-id="'+id+'">'
			                           +'<div class="section_do_clo"></div>'
			                           +'<div class="section_do_img"><img src="'+obj.img+'" onerror="this.src=\'/Public/newUIH/images/cpgli.png\'" alt=""  ></div>'
			                           '<div>'+text+'</div>'
			                       '</div>'


			                    $('.section_do_left').find('#go_model_add').first().before(str);
			                    M.initDeteleClick();
			                    M.initQieHuan();
			                    $('.section_do_left').find('#go_model_add').first().remove();

			                    setTimeout(function () {
			                        clearstatus()
			                        $('.my_model').css({'z-index': '-10', 'opacity': '0'});
			                         $(".section_box").show();
			                    }, 200)

			                    data_arry.push(obj);
			                    data_id.push(obj.id);
			                    $.post('/Home/Ajax/changequerylink',{"addid":obj.id},function(){})//向服务器同步
			                    // console.log(obj, '当前添加的元素')
			                    // console.log(data_arry, 'data_arry')
			                }
			            });

			        }else {
			            clearstatus()
			            $('.my_model').css({'z-index': '-10', 'opacity': '0'});
			        	$(".section_box").show();
			        }
			    } else if (status == '替换') {
			        // console.log(model_id, '替换的model_id')
			        if (model_id != '') {
			            $.each(title_arry, function (idx, obj) {
			                if (obj.id == model_id) {
			                    var id = obj.id;
			                    var text = obj.text;
			                    var str = '<div class="delegate_add" data-id="'+id+'">'
			                           +'<div class="section_do_clo"></div>'
			                           +'<div class="section_do_img"><img src="'+obj.img+'" alt="" onerror="this.src=\'/Public/newUIH/images/cpgli.png\'"  ></div>'
			                           +'<div>'+text+'</div>'
			                           +'</div>'

			                    $(".delegate_add[data-id='" + _id + "']").replaceWith(str);
			                    M.initDeteleClick();
			                    M.initQieHuan();
			                    $.each(data_arry, function (i, ele) {


			                        if (_id==ele.id){
			                            // console.log(ele, '被删除的节点')
			                            // data_arry.remove(ele);

			                            data_arry.splice($.inArray(ele, data_arry), 1);
			                            data_id.splice($.inArray(ele.id, data_id), 1);
			                            data_arry.push(obj);
			                            data_id.push(obj.id);
			                            $.post('/Home/Ajax/changequerylink',{"addid":obj.id,"delid":ele.id},function(){})//向服务器同步
			                            // console.log(data_arry,'data_arry')
			                            // console.log(data_id,'data_id')

			                        }
			                    })
			                    // data_arry.push()
			                    setTimeout(function () {
			                        clearstatus()
			                        $('.my_model').css({'z-index': '-10', 'opacity': '0'});
			                        $(".section_box").show();
			                    }, 200)
			                }
			            })
			        } else {
			            // console.log(_id, '点击确定时的id')
			            var str = '<div class="delegate_add" id="go_model_add">'
			                +'<div class="section_do_img"><img src="/ublic/newUIH/images/shuju.png" alt=""></div>'
			                +'<div>添加</div>'
			                +'</div>'
			            $(".delegate_add[data-id='" + _id + "']").remove();
			            $.each(data_arry, function (i, ele) {

			                if (_id==ele.id){
			                    // console.log(ele, '被删除的节点')
			                    // data_arry.remove(ele);

			                    data_arry.splice($.inArray(ele, data_arry), 1);
			                    data_id.splice($.inArray(ele.id, data_id), 1);
			                    $.post('/Home/Ajax/changequerylink',{"delid":ele.id},function(){})//向服务器同步
			                    // console.log(data_arry,'data_arry')
			                    // console.log(data_id,'data_id')

			                }
			            })

			            $('.section_do_left').find('#go_model_other').first().before(str);
			            M.initQieHuan();
			            clearstatus();
			            $('.my_model').css({'z-index': '-10', 'opacity': '0'})
			            $(".section_box").show();
			            add_kongimg()
			        }
			    }
			})
	},
	//加载切换快捷方式
	initQieHuan:function(){
		$(".section_do_clo").on("click",function(){
			MaoPao.stopmp(MaoPao.getEvent());
			status = '替换';
	        model_id = '';

	        apdate_data();
	        apdata_model_data();
	        _id = $(this).parents('.delegate_add').attr('data-id');
	        $(".section_box").hide();
	        $('.my_model_add').css({'z-index': '99', 'opacity': '1'})
	        // $('.my_model_add').show();
	        $('.my_model_add').find('.model_header_title').html('选择替换');
	        $('.my_model_add').find('#model_header_title_y').html('REPLACE');
	        $('#model_btn_add').css('display', 'block');
	        $('#model_btn_add').show();
		        // event.stopPropagation();//阻止事件冒泡
		       
		});
	},
	//加载用户拥有的权限菜单
	initLoadUserHasAuth:function(){
		$.get("/Home/Index/initLoadUserHasAuth.html",function(data){

			console.log(data,'加载用户拥有的权限菜单')
			if(data.code==200){
				$.each(data.data,function(i,o){
					title_arry.push(o);
				});

				if (data.data.length<=5){
                    $.each(data.data,function(i,o){
                        var one       = {};
                        one.id        = o.id;
                        one.text      = o.text;
                        one.type       = o.btitle;
                        one.img = "/Public/icon/BOS/"+o.id+".png";
                        one.url = o.name;
                        data_arry.push(one);
                    });
                    //循环页面功能渲染值
                    $.each(data_arry, function (idx, obj) {
                            data_str += '<div class="delegate_add" data-id="'+obj.id+'">'
                                +'<div class="section_do_clo"></div>'
                                +'<div class="section_do_img"><img src="'+obj.img+'" onerror="this.src=\'/Public/newUIH/images/cpgli.png\'" alt=""  ></div>'
                                +'<div>'+obj.text+'</div>'
                                +'</div>'
                            data_id.push(obj.id);
                    });
                    var data_kong =  add_kongimg();
                    _data_str = data_str+data_kong;
                    $('.section_do_left').html(_data_str);
                    M.initDeteleClick();
                    M.initQieHuan();

				} else {
                    M.initLoadBossLink();
				}

				
			}else{
				$("#bosloading").html("您还没有配置Boss菜单权限哟，赶紧联系管理员配置一下吧！");
			}
		});
	},
	initLoadBossLink:function(){
		$.get("/Home/Index/initLoadBossLink.html",function(data){

            // console.log(data,'展示的权限菜单')
			if(data.code==200){
				$.each(data.data,function(i,o){
					var one       = {};
					one.id        = o.id;
					one.text      = o.title;
					one.type       = o.btitle;
					one.img = "/Public/icon/BOS/"+o.id+".png";
					one.url = o.name;
					data_arry.push(one);
				});


				//循环页面功能渲染值
			    $.each(data_arry, function (idx, obj) {
			        if(idx<4){
			            data_str += '<div class="delegate_add" data-id="'+obj.id+'">'
			                           +'<div class="section_do_clo"></div>'
			                           +'<div class="section_do_img"><img src="'+obj.img+'" onerror="this.src=\'/Public/newUIH/images/cpgli.png\'" alt=""  ></div>'
			                           +'<div>'+obj.text+'</div>'
			                        +'</div>'
			            data_id.push(obj.id);
			        }
			        
			    });

			    // console.log(title_arry.length,"title_arry.length");
			    //  console.log(data_arry.length,"data_arry.length");
			    if (title_arry.length <= 5) {
			        var data_kong =  add_kongimg();
			        _data_str = data_str+data_kong;

			    } else if (title_arry.length>5&&data_arry.length==4){
			        _data_str = data_str + '<div class="delegate_add" id="go_model_other">'
			                    +'<div class="section_do_img"><img src="/Public/newUIH/images/shuju.png" alt=""></div>'
			                    +'<div>其它</div>'
			                +'</div>';
			    }else if (title_arry.length>5&&data_arry.length<4){
			        var data_add = add_kong()
			        _data_str = data_str+data_add+ '<div class="delegate_add" id="go_model_other">'
			                    +'<div class="section_do_img"><img src="/Public/newUIH/images/shuju.png" alt=""></div>'
			                    +'<div>其它</div>'
			                +'</div>';

			        // console.log(data_add,'data_add')
			    }else if (title_arry.length>5&&data_arry.length>4){
			        var data_add = add_kong();
			        _data_str = data_str+data_add+ '<div class="delegate_add" id="go_model_other">'
			                    +'<div class="section_do_img"><img src="/Public/newUIH/images/shuju.png" alt=""></div>'
			                    +'<div>其它</div>'
			                +'</div>';
			    }

			    $('.section_do_left').html(_data_str);
			    M.initDeteleClick();
			    M.initQieHuan();
			}
		});
	},
	initDeteleClick:function(){
		$(".delegate_add").on("click",function(){
			$(this).addClass('has_section_do_hover')
			   var id = $(this).attr('data-id');
			    $.each(title_arry, function (idx, obj) {
			        if(id==obj.id){
			            var url=obj.url;
			            window.open('/Home/Index/main?mainurl='+url);
			        }
			    });
		});

	}
};
$(function(){M.init();});



//动态渲染模态框
function apdata_model_data() {
    var text_type=''
    $.each(other_arr.childen_list, function (idx, ele) {
        text_type += '<div class="model_text_list">';
        text_type += '<div class="model_text_title">'+ele.type+'</div><div class="model_text_type" >';
        $.each(ele.childen,function (i, e) {
            text_type += '<div>'+e.text+'</div>';
        })
        text_type += '</div></div>';
    });
    // console.log(text_type,'text_type')
    $('.model_text').html(text_type);
}


//更新模态框数据
function apdate_data() {
    //其它

     _model_id = []
     model_arry = []
    other_arr = {
        type_arr:[],
        childen_list:[],
    };

    $.each(newmodel_arry, function (idx, obj) {

        _model_id.push(obj.id)
    })
    $.each(data_id, function (idx, ele) {
        $.each(_model_id, function (i, e) {
            if (e == ele) {
                _model_id.splice($.inArray(e, _model_id), 1);
            }
        })

    })

    $.each(newmodel_arry, function (idx, ele) {
        $.each(_model_id, function (i, e) {
            if (e == ele.id) {
                model_arry.push(ele);
            }
        })

    })
    // console.log(model_arry, '其它')
    $.each(model_arry, function (idx, ele) {
        var type = ele.type;
        var new_data = {};
        new_data.type = type;
        new_data.childen = [];
        new_data.childen.push(ele)


        if (isInArray(type, other_arr.type_arr)){

            $.each(other_arr.childen_list, function (i, e){
                if (e.type ===type ){
                    e.childen.push(ele)
                }
            })

        }else {

            other_arr.childen_list.push(new_data)
            other_arr.type_arr.push(type)
        }

    })

}

//判断数组对象是否存在
function isInArray(data,array){
    for(var i=0;i<array.length;i++){
        var item=array[i];
        if(data===item){
            return true;
        }
    }
    return false;
}

//暂无该功能图片站位
function add_kongimg() {
    var lenght =data_arry.length;
    // console.log(lenght)
    var _lenght = 5 - lenght;
    var kong_img_str = ''
    for (var i = 0; i < _lenght; i++) {

        kong_img_str +='<div class="delegate_kong ">'
                                  +'<div class="section_do_img"></div>'
                                  +'<div>暂无功能</div>'
                             +'</div>'
    }
   return kong_img_str;
}

//添加
function add_kong() {
    var lenght =data_arry.length;
    // console.log(lenght)
    var _lenght = 4 - lenght;
    var kong_img_str = ''
    for (var i = 0; i < _lenght; i++) {

        kong_img_str +='<div class="delegate_add" id="go_model_add">'
                +'<div class="section_do_img"><img src="/Public/newUIH/images/shuju.png" alt=""></div>'
                +'<div>添加</div>'
                +'</div>'
    }
    return kong_img_str;
}

//清除选项块的效果/状态/临时储存数据
function clearstatus() {
    status = '';
    _id = '';
    model_id = '';

    $('.model_text_type>div').each(function (i, ele) {
        $(this).removeClass('has_hover')
    })
}

