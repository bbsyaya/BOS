/*弹窗Js  显示和隐藏*/
function PopupBase() {
    this.show = function(btn, con){
        $(btn).on('click',function(){
            $(con).fadeIn();
        });
    };
    this.hide = function PopupHide(btn, con) {
        $(btn).on('click',function(){
            $(con).fadeOut();
        })
    };
}
/*金额转为大写*/
function DX(n){
    if(n=='' || isNaN(n) || n==0 || n=='0')return '零';
    var f='';
    if(n[0]=='-'){
        f='负';
        n=n.replace('-','');
    }
    if (!/^(0|[1-9]\d*)(\.\d+)?$/.test(n))
    return "数据非法";
    var unit = "仟佰拾亿仟佰拾万仟佰拾元角分", str = "";
        n += "00";
    var p = n.indexOf('.');
    if (p >= 0)
        n = n.substring(0, p) + n.substr(p+1, 2);
        unit = unit.substr(unit.length - n.length);
    for (var i=0; i < n.length; i++)
        str += '零壹贰叁肆伍陆柒捌玖'.charAt(n.charAt(i)) + unit.charAt(i);
    if(f!='')str=f+str;
    return str.replace(/零(仟|佰|拾|角)/g, "零").replace(/(零)+/g, "零").replace(/零(万|亿|元)/g, "$1").replace(/(亿)万/g, "$1$2").replace(/^元零?|零分/g, "").replace(/元$/g, "元整");
}
//in_array()
function in_array(stringToSearch, arrayToSearch) {
 for (s = 0; s < arrayToSearch.length; s++) {
  thisEntry = arrayToSearch[s].toString();
  if (thisEntry == stringToSearch) {
   return true;
  }
 }
 return false;
}
//转时间戳
function strtotime(stringTime){
    if(stringTime.length==13)stringTime+=':00:00';
    var timestamp2 = Date.parse(new Date(stringTime));
    timestamp2 = timestamp2 / 1000;
    return timestamp2;
}
//判断开始结束时间
function checktime(a,b,obj,dp){
    if(obj.name==a){
        if(strtotime(dp.cal.getNewDateStr())>strtotime($('input[name="'+b+'"]').val())){
            layer.msg('友情提醒：开始时间大于结束时间')
        }
    }else{
        if(strtotime($('input[name="'+a+'"]').val())>strtotime(dp.cal.getNewDateStr())){
            layer.msg('友情提醒：开始时间大于结束时间')
        }
    }
    
}
$(function(){
    //全选
    $('.check-all').click(function(){
        $('.ids').prop('checked',this.checked);
    });
    $('.ids').click(function(){
        var option = $('.ids');
        option.each(function(i) {
            if(!this.checked){
                $('.check-all').prop('checked',false);
                return false;
            }else{
                $('.check-all').prop('checked',true);
            }
        });
    });
    //删除
    $(".Js_tableCon").on({
        click:function(){
            $(this).parents("tr").remove();
        }
    },".Js_details");
});
