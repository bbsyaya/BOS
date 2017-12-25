/**
 * [In description]
 * @type {Object}
 */
var In={
	init:function(){
		this.initQinBao_jxz();
		this.initQinBao_jxz_1();
		this.initQinBao_jxz_2();
		this.initQinBao_jxz_3();
		this.initQinBao_all_qb();
	},
	initQinBao_jxz:function(){
		var myChart = echarts.init(document.getElementById('qb_tj_0'));
		var option = {
		    title: {
		        text: '',
		        left: 'center'
		    },
		    tooltip: {
		        trigger: 'item',
		        formatter: '{a} <br/>{b} : {c}'
		    },
		    legend: {
		        left: 'left',
		        data: ['进行中的情报任务']
		    },
		    xAxis: {
		        type: 'category',
		        name: '时间',
		        splitLine: {show: false},
		        data: ['一', '二', '三', '四', '五', '六', '七', '八', '九']
		    },
		    grid: {
		        left: '3%',
		        right: '4%',
		        bottom: '3%',
		        containLabel: true
		    },
		    yAxis: {
		        type: 'log',
		        name: '数量'
		    },
		    series: [
		        {
		            name: '进行中的情报任务',
		            type: 'line',
		            data: [1, 3, 9, 27, 81]
		        }
		    ]
		};
		myChart.setOption(option);

	},
	initQinBao_jxz_1:function(){
		var myChart = echarts.init(document.getElementById('qb_tj_1'));
		var option = {
		    title: {
		        text: '',
		        left: 'center'
		    },
		    tooltip: {
		        trigger: 'item',
		        formatter: '{a} <br/>{b} : {c}'
		    },
		    legend: {
		        left: 'left',
		        data: ['待处理的情报任务']
		    },
		    xAxis: {
		        type: 'category',
		        name: '时间',
		        splitLine: {show: false},
		        data: ['一', '二', '三', '四', '五', '六', '七', '八', '九']
		    },
		    grid: {
		        left: '3%',
		        right: '4%',
		        bottom: '3%',
		        containLabel: true
		    },
		    yAxis: {
		        type: 'log',
		        name: '数量'
		    },
		    series: [
		        {
		            name: '待处理的情报任务',
		            type: 'line',
		            data: [1, 3, 9, 27, 81]
		        }
		    ]
		};
		myChart.setOption(option);
	},
	initQinBao_jxz_2:function(){
		var myChart = echarts.init(document.getElementById('qb_tj_2'));
		var option = {
		    title: {
		        text: '',
		        left: 'center'
		    },
		    tooltip: {
		        trigger: 'item',
		        formatter: '{a} <br/>{b} : {c}'
		    },
		    legend: {
		        left: 'left',
		        data: ['已采集的情报任务']
		    },
		    xAxis: {
		        type: 'category',
		        name: '时间',
		        splitLine: {show: false},
		        data: ['一', '二', '三', '四', '五', '六', '七', '八', '九']
		    },
		    grid: {
		        left: '3%',
		        right: '4%',
		        bottom: '3%',
		        containLabel: true
		    },
		    yAxis: {
		        type: 'log',
		        name: '数量'
		    },
		    series: [
		        {
		            name: '已采集的情报任务',
		            type: 'line',
		            data: [1, 3, 9, 27, 81]
		        }
		    ]
		};
		myChart.setOption(option);
	},
	initQinBao_jxz_3:function(){
		var myChart = echarts.init(document.getElementById('qb_tj_3'));
		var option = {
		    title: {
		        text: '',
		        left: 'center'
		    },
		    tooltip: {
		        trigger: 'item',
		        formatter: '{a} <br/>{b} : {c}'
		    },
		    legend: {
		        left: 'left',
		        data: ['已完成的情报任务']
		    },
		    xAxis: {
		        type: 'category',
		        name: '时间',
		        splitLine: {show: false},
		        data: ['一', '二', '三', '四', '五', '六', '七', '八', '九']
		    },
		    grid: {
		        left: '3%',
		        right: '4%',
		        bottom: '3%',
		        containLabel: true
		    },
		    yAxis: {
		        type: 'log',
		        name: '数量'
		    },
		    series: [
		        {
		            name: '已完成的情报任务',
		            type: 'line',
		            data: [1, 3, 9, 27, 81]
		        }
		    ]
		};
		myChart.setOption(option);
	},
	initQinBao_all_qb:function(){
		var myChart = echarts.init(document.getElementById('all_qb'));
		var dataAxis = ['9.15', '9.16', '9.17', '9.18', '9.19', '9.20', '9.21', '9.22', '9.23', '9.24'];
        var data1 = [10, 52, 70, 98, 36, 77, 89,84,32,65];
        var data2 = [10, 52, 100, 150, 200, 250, 300,350,400,430];
        option = {
            color: ['#3398DB'],
            tooltip : {
                trigger: 'axis',
                axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                    type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                }
            },
            legend: {
                data:['数量','平均周期(H)'],
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            calculable : true,
            xAxis : [
                {
                    type : 'category',
                    data : dataAxis,
                    axisTick: {
                        alignWithLabel: true
                    }
                }
            ],
            yAxis : [
                {
                         type: 'value',
                         name: '数量',
                         position: 'left',
                            axisLabel: {
                                formatter: '{value} '
                            }
                },
                {
                    type: 'value',
                    name: '平均周期(H)',
                    min: 0,
                    max: 500,
                    position: 'right',
                    axisLabel: {
                        formatter: '{value}'
                    }
                },
            ],
            series : [
                {
                    name:'时间',
                    type:'bar',
                    barWidth: '20%',
                    yAxis: 1, 
                    itemStyle:{normal:{color:'#d14a61'}},
                    data:data1
                },
                {
                        name:'时间',
                        type:'line',
                         yAxisIndex: 1,
                        data:data2
                }
                
            ]
        };
		myChart.setOption(option);
	},

};
$(function(){In.init();})