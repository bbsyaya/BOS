// JavaScript Document
$(function() {
	// 日期控件区间
	$('.entry_DayDemo').datepicker({
		'format': 'yyyy-mm-dd',
		'autoclose': true
	});
	$('.entry_DayDemo').datepair();
    // 日期控件区间
    $('.dayTimeBox .start_rise').datepicker({
        'format': 'yyyy-mm-dd',
        'autoclose': true
    });
    $('.dayTimeBox').datepair();
    // 日期控件区间
    $('.entryCon .start_rise').datepicker({
        'format': 'yyyy-mm-dd',
        'autoclose': true
    });
    $('.entryCon').datepair();
});