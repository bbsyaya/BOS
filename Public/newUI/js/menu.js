/**
 * [title_arry description]
 * @type {Object}
 */
var title_arry = [], read_arry = [];
var testmenu = 1;
var data_arr = {
    type_arr: [],
    childen_list: []
};
var item_can_edit = true;//跳转
var each_item_data_id = "";
var M = {
    init: function () {
        this.initHasMens();
        this.initGetOALink();
        this.initClick();
        this.initTextOldFun();

        // this.read_data();//读取用户设置的快捷菜单
        // this.updata_dialog_data();
        // this.will_width();
        // this.read_dia_data();
    },
    //读取当前用户已有的数据权限
    initHasMens: function () {
        $.get("/OA/Index/getMyHasMenus.html", function (data) {
            if (data.data) {
                $.each(data.data, function (i, o) {
                    var one = {};
                    one.id = o.id;
                    one.type = o.type;
                    one.url = "/OA/Index/main.html?mainurl=" + o.url;
                    if (o.mid) {
                        one.img_click = "/Public/icon/OA/on/" + o.mid + ".png";
                        one.img = "/Public/icon/OA/auto/" + o.mid + ".png";
                    } else {
                        one.img_click = "/Public/newUIH/images/cpgli.png";
                        one.img = "/Public/newUIH/images/cpgli.png";
                    }

                    one.text = o.name;
                    title_arry.push(one);
                });
                // console.log(title_arry);
                M.apdate_data();
            }
        });
    },
    //读取当前用户设置的快捷连接
    initGetOALink: function () {
        $.get("/OA/Index/getMyOALink.html", function (data) {
            if (data.data) {
                $.each(data.data, function (i, o) {
                    var one = {};
                    one.id = o.id;
                    one.type = o.type;
                    one.pid = o.pid;
                    one.url = "/OA/Index/main.html?mainurl=" + o.url;
                    if (o.mid) {
                        one.img_click = "/Public/icon/OA/on/" + o.mid + ".png";
                        one.img = "/Public/icon/OA/auto/" + o.mid + ".png";
                    } else {
                        one.img_click = "/Public/newUIH/images/cpgli.png";
                        one.img = "/Public/newUIH/images/cpgli.png";
                    }
                    one.text = o.name;
                    read_arry.push(one);
                });
                // console.log(title_arry);
                // M.apdate_data();
                M.read_data();
            }
        });
    },
    initClick: function () {
        $('#aaabbbccc').click(function () {
            $.post('/OA/Index/aabbcc', {}, function (data) {
            });
        });
    },
    //加载原text.js的初始化函数
    initTextOldFun: function () {
        var mySwiper = new Swiper('.qywh_swiper', {
            autoplay: 5000,//可选选项，自动滑动
            autoplayDisableOnInteraction: false,
            paginationClickable: true,
            loop: true
        });
        $(" .swiper_slide_prev").click(function () {
            mySwiper.slidePrev();
            console.log(111)
        });
        $(" .swiper_slide_next").click(function () {
            mySwiper.slideNext();
            console.log(222)
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
        //公告栏

        /*$(".gg_swiper .gg_swiper_boc").click(function () {
            var id = $(this).attr('data-id');
            $.post('/OA/Index/find_notify', {id:id}, function (data) {

            });
            $(".notice").fadeIn();
            // 公告栏模态框
            var noticeSwiper = new Swiper('.lunbo_box', {
                autoplay: 3500,//可选选项，自动滑动
                autoplayDisableOnInteraction: false,
                paginationClickable: true,
                loop: true
            });
            $(".notice .msk_left").click(function () {
                // console.log(11111)
                noticeSwiper.slidePrev();
            });
            $(".notice .msk_right").click(function () {
                // console.log(2222)
                noticeSwiper.slideNext();
            });
            $(".notice .notice_img").click(function () {

                $(".notice").fadeOut();

            });
            $('.lunbo_box .swiper_box>div').on({
                mouseover: function () {
                    // console.log(11)
                    noticeSwiper.stopAutoplay()
                },
                mouseout: function () {
                    // console.log(22)
                    noticeSwiper.startAutoplay()
                }
            });

        });*/
        $('.gg_swiper .gg_swiper_boc').on({
            mouseover: function () {
                // console.log(11)
                ggaoSwiper.stopAutoplay()
            },
            mouseout: function () {
                // console.log(22)
                ggaoSwiper.startAutoplay()
            }
        });

        $(".notice").click(function (e) {
            if (e.target==this){
                $(this).fadeOut();
            }


        })
        /*var mySwiper = new Swiper('.swiper-container', {

            autoplay: 5000,//可选选项，自动滑动
            loop: true,
            paginationClickable: true,
            autoplayDisableOnInteraction: false
        });
        $(".swiper_slide_prev").click(function () {
            mySwiper.slidePrev();
        });
        $(".swiper_slide_next").click(function () {
            mySwiper.slideNext();
        });
        $(".mask #btn_close").click(function () {
            $(".mask").fadeOut();
            _click_id = '';
            click_id = '';
            $('.go_tihuan').removeClass('boot_click');
            item_can_edit = true;
        });*/
        $(".mask").click(function (event) {
            if (event.target == this) {
                $(".mask").fadeOut();
                _click_id = '';
                click_id = '';
                $('.go_tihuan').removeClass('boot_click');
                item_can_edit = true;
            }

        });

        $("#more_entry").click(function () {
            $(".mask").fadeToggle();
            $('.dia_list').find('.each_item').css('text-align', 'center');
            $('.dia_list').css('height', '80%');
            $('.selected_box').css('display', 'none');
            $('.bott_box').css('display', 'none');
            $('.dialog_content_edit').css('display', 'block');
            $('.dialog_content_edit').text('编辑快捷入口');
            M.read_dia_data();
            M.updata_dialog_data();
            M.will_width();
        });

        //+++++++++++++++++++++++++++++++part 2
        $(".flex_itemBox").delegate(".will_item", "mouseover", function () {
            var data_id = $(this).attr('data-id');
            var that = $(this).find('img');
            $.each(read_arry, function (i, e) {
                if (e.id == data_id) {
                    that.attr('src', e.img_click);
                }
            });


        }).delegate(".will_item", "mouseleave", function (e) {
            var data_id = $(this).attr('data-id');
            var that = $(this).find('img');
            $.each(read_arry, function (i, e) {
                if (e.id == data_id) {
                    that.attr('src', e.img);
                }
            })

        })

        //点击到替换页开关
        $(".dialog_content_edit").click(function () {
            item_can_edit = !item_can_edit;

            if (item_can_edit == true) {//跳转
                $(".dialog_content .each_item").removeClass("canEdit");
                $('.dia_list').find('.each_item').css('text-align', 'center');
                $('.dia_list').css('height', '80%');
                $('.selected_box').css('display', 'none');
                $('.bott_box').css('display', 'none');
                $('.dialog_content_edit').text('编辑快捷入口');

            } else {//替换
                $(".dialog_content .each_item").addClass("canEdit");
                $('.dia_list').find('.each_item').css('text-align', 'left');
                $('.dia_list').css('height', '62%');
                $('.selected_box').css('display', 'table');
                $('.bott_box').css('display', 'block');
                $('.dialog_content_edit').text('快捷入口');
            }


        });

        //+++++++++++++++++++part3
        //替换功能模块元素移入移出点击事件

        var click_id = '';
        var data_id = '';
        $(".selected_box").delegate(".sel_box", "mouseover", function () {
            data_id = $(this).attr('data-id');
            var _wight = $(this).width();


            if (data_id == click_id) {
                $(this).find('.sel_text_d').css('display', 'block');
                $(this).find('.sel_text_h').css('display', 'none');
            } else {
                $(this).find('.sel_text_d').css('display', 'none');
                $(this).find('.sel_text_h').css('display', 'block');
                $(this).css('background-color', '#5B74F5');
                $(this).width(_wight);
            }

        }).delegate(".sel_box", "mouseout", function () {
            data_id = '';
            $(this).find('.sel_text_d').css('display', 'block');
            $(this).find('.sel_text_h').css('display', 'none');
            $(this).css('background-color', 'white');
        }).delegate(".sel_box", "click", function () {

            click_id = $(this).attr('data-id');

            $(this).find('.sel_text_d').css('display', 'block');
            $(this).find('.sel_text_h').css('display', 'none');
            $(this).addClass('sel_text_c');
            $(this).siblings().removeClass('sel_text_c');

        });


        var _click_id = '';
        var _data_id = '';
        //弹窗内元素移入移出点击事件  item_can_edit为true 时为跳转页面
        $(".dia_list").delegate(".each_item", "mouseover", function () {
            _data_id = $(this).attr('data-id');
            if (item_can_edit) {

                $(this).addClass('each_item_c');
            } else {


                $(this).find('.chcbox').removeClass('each_item_icon_d');
                $(this).find('.chcbox').addClass('each_item_icon');
                $(this).find('.innertext').css('color', '#5B74F5');

            }

        }).delegate(".each_item", "mouseout", function () {

            if (item_can_edit) {//跳转
                $(this).removeClass('each_item_c');
            } else {
                if (_click_id == _data_id) {
                    return;

                } else {
                    $(this).find('.chcbox').removeClass('each_item_icon');
                    $(this).find('.chcbox').addClass('each_item_icon_d');
                    $(this).find('.innertext').css('color', '');
                    _data_id = '';
                }


            }

        });

        //++++++++++++++++++++++++++++++++++++part 4
        $('.go_tihuan').on("click", function () {
            _click_id = each_item_data_id;
            // console.log("1-"+_click_id);
            // console.log("1-"+click_id);
            if (_click_id != '' && click_id != '') {  //替换  _click_id为其它
                var dele_ele = '';
                // console.log(_click_id, click_id)
                $.each(read_arry, function (i, e) {
                    if (click_id == e.id) {

                        dele_ele = e;


                    }
                })
                // console.log(dele_ele, '要删除的id')

                $.post('/OA/Ajax/index_changealink', {'fromid': click_id, 'toid': _click_id}, function (data) {
                    if (data.type == 2) {//报错
                        layer.alert(data.msg)
                        $(".mask").fadeOut();
                        _click_id = '';
                        click_id = '';
                        $('.go_tihuan').removeClass('boot_click')
                    } else {
                        read_arry.splice(jQuery.inArray(dele_ele, read_arry), 1);
                        $.each(title_arry, function (i, e) {
                            if (_click_id == e.id) {
                                read_arry.push(e);
                                // console.log(e.id, '添加的id')
                            }
                        })
                        $('.flex_itemBox').find('.will_item').remove()
                        M.read_data()
                        $(".mask").fadeOut();
                        _click_id = '';
                        click_id = '';
                        $('.go_tihuan').removeClass('boot_click');
                    }
                }, 'json');
                item_can_edit = true;
                // console.log(read_arry, '删除后的数据')


            }
        });
    },
    itemClick: function () {
        var _click_id = "", click_id = "";
        $(".each_item").on("click", function () {
            each_item_data_id = _click_id = $(this).attr('data-id');
            console.log(item_can_edit);
            if (item_can_edit) { // 跳转
                $.each(title_arry, function (i, e) {
                    if (e.id == _click_id) {
                        window.open(e.url);
                    }
                })
            } else {
                $(this).find('.chcbox').removeClass('each_item_icon_d');
                $(this).find('.chcbox').addClass('each_item_icon');
                // $(this).find('.innertext').css('color', '#5B74F5');
                $(this).find('.innertext').css({'color': '#5B74F5'});
                $(this).css({"border": "1px solid red"});

                $(this).parents('.renli_box').siblings().find('.chcbox').removeClass('each_item_icon');
                $(this).parents('.renli_box').siblings().find('.chcbox').addClass('each_item_icon_d');
                $(this).parents('.renli_box').siblings().find('.innertext').css('color', '');

                $(this).siblings().find('.chcbox').removeClass('each_item_icon');
                $(this).siblings().find('.chcbox').addClass('each_item_icon_d');
                // $(this).siblings().find('.innertext').css('color', '');
                $(this).siblings().find('.innertext').css({'color': ''});
                $(this).siblings().css({"border": "1px solid #ddd"});
                if (each_item_data_id != '' && click_id != '') {  //替换  _click_id为其它
                    $('.go_tihuan').addClass('boot_click');
                } else {
                    $('.go_tihuan').removeClass('boot_click');
                }
            }
        });
    },
    //更新模态框数据
    apdate_data: function () {
        // console.log(title_arry,"title_arry");
        $.each(title_arry, function (idx, ele) {
            var type = ele.type;
            var new_data = {};
            new_data.type = type;
            new_data.childen = [];
            new_data.childen.push(ele);


            if (isInArray(type, data_arr.type_arr)) {

                $.each(data_arr.childen_list, function (i, e) {
                    if (e.type === type) {
                        e.childen.push(ele);
                    }
                });

            } else {

                data_arr.childen_list.push(new_data);
                data_arr.type_arr.push(type);
            }

        });
        M.updata_dialog_data();
        // console.log(data_arr);
    },
    //初始化页面--读取用户设置的快捷菜单
    read_data: function () {
        var text_type = '';
        var img = '', data_id = '', data_text = '',pid='';
        $.each(read_arry, function (idx, ele) {
            img = ele.img;
            // console.log(img);
            data_id = ele.id;
            data_text = ele.text;
            pid = ele.pid;

            text_type += ' <li class="flex_item will_item" data-id="' + data_id + '"><a href="' + ele.url +'&pid='+pid +'" target="_blank"><span class="div_item "><img onerror="this.src=\'/Public/newUIH/images/cpgli.png\'" src="' + img + '"></span>';

            text_type += ' <p>' + data_text + '</p></a></li>';

        });
        // console.log(text_type, 'text_type')
        $('#more_entry').parent('.flex_item').before(text_type);

        $("#menuLoading").hide();
        $("#menuList").show();
    },
    //更新弹框数据
    updata_dialog_data: function () {
        var text_type = '';
        var list_type = '';
        $.each(data_arr.childen_list, function (idx, ele) {
            list_type = ele.type;

            text_type += '<p class="dialog_content_title"><span>' + list_type + '</span><span>Human Resoure</span></p> <div class="clearfix renli_box ">'

            $.each(ele.childen, function (i, e) {
                text_type += ' <div class="each_item" data-id="' + e.id + '"><div class="chcbox each_item_icon_d"></div><span class="innertext">' + e.text + '</span></div>'
            })
            text_type += '</div>';
        })
        // console.log(text_type, 'text_type')
        $('.dia_list').html(text_type);
        M.will_width();
        M.itemClick();
        M.read_dia_data();
    },
    will_width: function () {
        var width_arr = [];
        $('.each_item').each(function (i, ele) {
            width_arr.push($(this).width());
        })

        var max = width_arr[0];
        for (var i = 1; i < width_arr.length; i++) {
            if (max < width_arr[i]) {
                max = width_arr[i];
            }
        }

        $('.each_item').each(function (i, ele) {
            $(this).width(max + 40);
            $(this).css('text-align', 'center');
        })

    },
    read_dia_data: function () {
        var text_type = '';
        var data_id = '', data_text = '';
        $.each(read_arry, function (idx, ele) {
            data_id = ele.id;
            data_text = ele.text;
            text_type += '<div class="sel_box " data-id="' + data_id + '"><div class="sel_text"><span class="sel_text_d">' + data_text + '</span><span class="sel_text_h">替换</span></div></div>';
        })
        // console.log(text_type, 'text_type')
        $('.selected_box').html(text_type);
    }

};
$(function () {
    M.init();
});

//判断数组对象是否存在
function isInArray(data, array) {
    for (var i = 0; i < array.length; i++) {
        var item = array[i];
        if (data === item) {
            return true;
        }
    }
    return false;
}

//动态渲染模态框
function apdata_model_data() {
    var text_type = '';
    var img = '', data_id = '', data_type = '';
    $.each(data_arr.childen_list, function (idx, ele) {

        $.each(data_arr, function (i, e) {
            if (ele.type == e.type) {
                img = e.img;
                data_id = e.id;
                data_type = e.type;
            }
        })
        text_type += '';

        $.each(ele.childen, function (i, e) {
            text_type += '';
        })
        text_type += '';
    })
    // console.log(text_type,'text_type')

}

function stopPropagation(e) {
    e = e || window.event;
    if (e.stopPropagation) { //W3C阻止冒泡方法
        e.stopPropagation();
    } else {
        e.cancelBubble = true; //IE阻止冒泡方法
    }
}