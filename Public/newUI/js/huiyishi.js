
var M = {
stopmp: function (e) {
    var evt = e;
    e.stopPropagation();
},
getEvent: function (event) {
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



function TimeDifference(time1, time2) {
    //定义两个变量time1,time2分别保存开始和结束时间


    //判断开始时间是否大于结束日期
    if (time1 > time2) {
        alert(time1 + '<>' + time2);
        alert("开始时间不能大于结束时间！");
        return false;
    }

    //截取字符串，得到日期部分"2009-12-02",用split把字符串分隔成数组
    var begin1 = time1.substr(0, 10).split("-");
    var end1 = time2.substr(0, 10).split("-");

    //将拆分的数组重新组合，并实例成化新的日期对象
    var date1 = new Date(begin1[1] + -+begin1[2] + -+begin1[0]);
    var date2 = new Date(end1[1] + -+end1[2] + -+end1[0]);

    //得到两个日期之间的差值m，以分钟为单位
    //Math.abs(date2-date1)计算出以毫秒为单位的差值
    //Math.abs(date2-date1)/1000得到以秒为单位的差值
    //Math.abs(date2-date1)/1000/60得到以分钟为单位的差值
    var m = parseInt(Math.abs(date2 - date1) / 1000 / 60);

    //小时数和分钟数相加得到总的分钟数
    //time1.substr(11,2)截取字符串得到时间的小时数
    //parseInt(time1.substr(11,2))*60把小时数转化成为分钟
    var min1 = parseInt(time1.substr(11, 2)) * 60 + parseInt(time1.substr(14, 2));
    var min2 = parseInt(time2.substr(11, 2)) * 60 + parseInt(time2.substr(14, 2));

    //两个分钟数相减得到时间部分的差值，以分钟为单位
    var n = min2 - min1;

    //将日期和时间两个部分计算出来的差值相加，即得到两个时间相减后的分钟数
    // console.log("m:"+m);
    // console.log("n:"+n);
    m = isNaN(m)?0:m;
    n = isNaN(n)?0:n;
    var re = parseFloat(m) + parseFloat(n);
    // console.log("re:"+re);
    return re;
}

Date.prototype.Format = function (fmt) { //author: meizz
    var o = {
        "M+": this.getMonth() + 1, //月份 
        "d+": this.getDate(), //日 
        "h+": this.getHours(), //小时 
        "m+": this.getMinutes(), //分 
        "s+": this.getSeconds(), //秒 
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度 
        "S": this.getMilliseconds() //毫秒 
    };
    if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
    if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
}

function checkTime(i) {
    if (i < 10) {
        i = "0" + i
    }
    return i
}

function showdate() {
    var myDate = new Date();
    var weekday = new Array(7)
    weekday[0] = "星期日"
    weekday[1] = "星期一"
    weekday[2] = "星期二"
    weekday[3] = "星期三"
    weekday[4] = "星期四"
    weekday[5] = "星期五"
    weekday[6] = "星期六"
    $('.font_we').html(weekday[myDate.getDay()])
    var h = myDate.getHours()
    var m = myDate.getMinutes()
    // add a zero in front of numbers<10
    m = checkTime(m)
    $('.font_time').find('b').html(h + ":" + m)
    if (h <= 12) $('.font_ap').html('AM');
    else $('.font_ap').html('PM');
    $('.font_nowday').html(myDate.Format('yyyy-MM-dd'))
    t = setTimeout('showdate()', 500)
}

function openda(id, address, data, is_edit,time_arr) {
    $('.mask').show();
    $('body').addClass('over_flow')
    // $('.alldo_bg').show();
    // $('#checkhuiyishi').css('margin-top', ($(window).height() - $('#checkhuiyishi').height()) / 2);

// console.log(is_edit,'is_edit')
//     已预订信息

    if (is_edit) {
        $('.mask').find('.dia_list').find('.madle_watch_box').show();
        $('.mask').find('.model_close_img_e').show();
        $('.mask').find('.dia_list').find('.madle_creat_box').hide();
        $('.bott_box').hide();

        var the_date=data.res[0].the_date.split('-');
        var this_date=the_date[1]+'月'+the_date[2]+'日';


        var now_time=Date.parse(new Date());

        var start_time=thisday+data.res[0].start_time;
            start_time=parseInt(get_unix_time(start_time))-1800000;

        if(start_time>=now_time&&data.res[0].uid==uid){
            room_id=data.res[0].id;
            $('.mask').find('.dia_list').find('.myself_box').show();
            $('.mask').find('.dia_list').find('.myself_both').hide();

        }else {
            room_id='';
            $('.mask').find('.dia_list').find('.myself_box').hide();
            $('.mask').find('.dia_list').find('.myself_both').show();
        }

        $('.hy_title').html('已预订会议信息');
        $('.mask').find('.dia_list').find('.room').html(data.res[0].meetroom_name);
        $('.mask').find('.dia_list').find('.date').html(this_date);
        $('.mask').find('.dia_list').find('.start_time').html(data.res[0].start_time);
        $('.mask').find('.dia_list').find('.end_time').html(data.res[0].end_time);
        $('.mask').find('.dia_list').find('.name').html(data.res[0].create_id);
        $('.mask').find('.dia_list').find('.title').html(data.res[0].title);
        $('.mask').find('.dia_list').find('.room_info').html(data.res[0].content==''?"无":data.res[0].content);

        var user =data.res[0].username;

        if(user!=null)$('.mask').find('.dia_list').find('.add_name').html(user);



        // $('input[name="is_tx"]').attr('checked', true);
        // if (data.res[0].whether == '1') {
        //     $('input[name="is_xz"]').prop('checked', true);
        // }else if (data.res[0].whether == '12') {
        //     $('input[name="is_xz"]').prop('checked', true);
        // }

    } else {
        $('.mask').find('.dia_list').find('.madle_watch_box').hide();
        $('.mask').find('.model_close_img_e').hide();
        $('.mask').find('.dia_list').find('.madle_creat_box').show();



        $('.hy_title').html('请填写预订信息');
        $('input[name="is_xz"]').prop('checked', false);
        $('input[name="is_tx"]').prop('checked', true);
        $('.selectpicker').selectpicker('val', '');
        $('.mask').find('.dia_list').find('.room').html(address);
        var this_date =$('#nowday_input').val();
        $('.mask').find('.dia_list').find('.date').html(this_date);
        $('.mask').find('.dia_list').find('.start_time').html(time_arr.start_time);
        $('.mask').find('.dia_list').find('.end_time').html(time_arr.end_time);
        $('.mask').find('.dia_list').find('.name').html(data.real_name);



        $('input[name="start_time"]').val(time_arr.start_time);
        $('input[name="end_time"]').val(time_arr.end_time);
        $('.mask').find('.do_div_content_user').find('span').html(data.real_name);

        $('.bott_box').show();

    }
    $('#' + id).show();
}

function _init() {

    // console.log(initdata);
    for (o in initdata) {
        var this_time = thisday + '09:00', a_str = '', this_str = '', bool = false, b_str = '';


        // console.log(initdata, 'initdata')
        // console.log(this_time, 'this_time')


        if (initdata[o].length > 0) {
            //上个节点结束时间，上午html，当前正在操作的html，是否正在操作下午html,下午html


            for (i in initdata[o]) {
                var str_time=initdata[o][i].strtime.substr(11, 2);
                var colm=0;
                if(str_time>= '13'){
                    colm=18;

                }else {
                    colm=12;

                }
                // 下午
                if (str_time >= '13' && !bool) {
                    if (this_time != thisday + '12:00') {
                        //剩余的上午

                        alls = TimeDifference(this_time, thisday + '12:00');
                        var wd = parseInt(alls / 15);

                        this_str += "<div class='yd_yes'  style='width:" + (wd/12)*100 + "%"+";' data-colm=" +wd+" data-start='"+this_time+"'></div>";
                    }
                    a_str = this_str;
                    this_str = '';
                    this_time = thisday + "13:30";
                    bool = true;
                }
                if (initdata[o][i].strtime != this_time) {

                    alls = TimeDifference(this_time, initdata[o][i].strtime);
                    var wd = parseInt(alls / 15 );
                    // console.log(alls);
                    this_str += "<div class='yd_yes'  style='width:" + (wd/colm)*100 + "%"+";' data-colm=" +wd+" data-start='"+this_time+"'></div>";


                    alls = TimeDifference(initdata[o][i].strtime, initdata[o][i].endtime);
                     // console.log("11no---"+alls);
                     wd = parseInt(alls / 15 );
                    this_str += "<div class='yd_no' style='width:" + (wd/colm)*100 + "%" +"'" + 'data=' + initdata[o][i].id +  "><div class='no_left'></div>" + initdata[o][i].name + "<div class='no_right'></div></div>";
                    this_time = initdata[o][i].endtime;
                } else {

                    alls = TimeDifference(initdata[o][i].strtime, initdata[o][i].endtime);

                    var wd = parseInt(alls / 15 );
                    this_str += "<div class='yd_no' style='width:" + (wd/colm)*100 + "%" +"'"+  'data=' + initdata[o][i].id + "><div class='no_left'></div>" + initdata[o][i].name + "<div class='no_right'></div></div>";
                    this_time = initdata[o][i].endtime;
                }
            }

            // 已记录最后一个时间节点
            if (this_time != thisday + '18:00') {
                if (bool) {
                    if(str_time>= '13'){
                        colm=18;

                    }else {
                        colm=12;

                    }
                    //开始时间到整个上午
                    alls = TimeDifference(this_time, thisday + '18:00');
                    var wd = parseInt(alls / 15 );
                    this_str += "<div class='yd_yes'  style='width:" + (wd/colm)*100 + "%"+";' data-colm=" +wd+ " data-start='"+this_time+"'></div>";
                    b_str = this_str;
                } else {

                    if (this_time != thisday + '12:00') {
                        //开始时间到整个下午
                        alls = TimeDifference(this_time, thisday + '12:00');
                        var wd = parseInt(alls / 15 );
                        this_str += "<div class='yd_yes'  style='width:" + (wd/colm)*100 + "%"+";' data-colm=" +wd+ " data-start='"+this_time+"'></div>";
                    }
                    a_str = this_str;
                }
            }else{
                b_str = this_str;
            }
        }
        if (a_str == '') a_str = "<div class='yd_yes' data-colm='12' data-start='09:00' style='width:100%' ></div>";
        if (b_str == '') b_str = "<div class='yd_yes' data-colm='18' data-start='13:30' style='width:100%'></div>";
        $('.' + o).find('.top').html(a_str);
        $('.' + o).find('.bottom').html(b_str);
        // console.log(o,'o')
        // console.log(b_str,'b_str')
        // console.log(a_str,'a_str')
    }
}

function getalldata(date) {
    date=date.replace(' ','');
    $.get('/OA/MeetRoom/checkhuiyishi', {"date": date}, function (data) {
        for (this_i in data.res) {
            data.res[this_i].strtime = data.res[this_i].the_date + ' ' + data.res[this_i].start_time;
            data.res[this_i].endtime = data.res[this_i].the_date + ' ' + data.res[this_i].end_time;
            data.res[this_i].name = data.res[this_i].create_id;
            roomname = data.res[this_i].meetroom_name;
            if (roomname == '卧龙岗') {
                initdata.wolonggang[initdata.wolonggang.length] = data.res[this_i];
            } else if (roomname == '桃园') {
                initdata.taoyuan[initdata.taoyuan.length] = data.res[this_i];
            } else if (roomname == '赤壁') {
                initdata.chibi[initdata.chibi.length] = data.res[this_i];
            } else {
                initdata.guandu[initdata.guandu.length] = data.res[this_i];
            }
        }
        // console.log(initdata);

        _init();
    }, 'json')
}

var morn_time=['09:00','09:15','09:30','09:45','10:00','10:15','10:30','10:45','11:00','11:15','11:30','11:45','12:00'];
var after_time=['13:30','13:45','14:00','14:15','14:30','14:45','15:00','15:15','15:30','15:45','16:00','16:15','16:30','16:45','17:00','17:15','17:30','17:45','18:00'];

// 通过节点转换开始/结束时间
function data_time(type,first,last) {

    // console.log(first,last)
    var time_arr={
        start_time:'',
        end_time:''
    }
    if(type=='top'){
        time_arr.start_time=morn_time[first];
        time_arr.end_time=morn_time[last];
    }else if(type=='bottom'){
        time_arr.start_time=after_time[first];
        time_arr.end_time=after_time[last];
    }
    return time_arr;

}

// 通过开始/结束时间转换节点
function time_index(type,time) {
    var index=0;
    if(type=='top'){
        for(i in morn_time){
            if (time==morn_time[i]){
                index=i;
            }
        }
    }else if(type=='bottom'){
        for(i in after_time){
            if (time==after_time[i]){
                index=i;
            }
        }
    }
    return parseInt(index);

}
// 转化时间戳
function get_unix_time(dateStr) {
    var newstr = dateStr.replace(/-/g,'/');
    var date =  new Date(newstr);
    var time_str = date.getTime().toString();
    return time_str.substr(0, 13);
}