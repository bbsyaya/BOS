function makepagehtml(){
	var page_allpage=Math.ceil(page_allnum/page_pagenum);
	if(page_allpage<=1)return;
	var page_lastpage=page_nowpage-1;
	var page_nextpage=page_nowpage+1;
	var page_html='<div class="dataPage_cn"> ';
	if(page_nowpage>4){
		page_html+='<a class="end dataPage_next last_page" href="'+page_url+page_lastpage+'#table_content"><</a>'
		page_html+='<a class="num dataPage_number" href="'+page_url+'1#table_content">1</a>';
		page_html+='<a class="next dataPage_next more" href="">...</a>';
	}
	var str_page=page_nowpage-2;
	if(str_page<1)str_page=1;
	var end_page=page_nowpage+2;
	if(end_page>page_allpage)end_page=page_allpage;
	for(var page_i=str_page;page_i<=end_page;page_i++){
		if(page_i==page_nowpage){
			page_html+='<span class="dataPage_number dataPage_numberFocus">'+page_i+'</span>';
		}else{
			page_html+='<a class="num dataPage_number" href="'+page_url+page_i+'#table_content">'+page_i+'</a>';
		}
	}
	if(page_allpage-page_nowpage>4){
		page_html+='<a class="next dataPage_next more" href="">...</a>';
		page_html+='<a class="num dataPage_number" href="'+page_url+page_i+'#table_content">'+page_allpage+'</a>';
		page_html+='<a class="end dataPage_next last_page" href="'+page_url+page_nextpage+'#table_content">></a>'
	}
	page_html+='<span class="dataPage_number">跳转到 <input type="text" id="pageto"/> 页</span>';
	page_html+='</div>';
	$('#page_div').html(page_html);
	$('#pageto').bind('keydown',function (e) {
		var key =e.which;
		if(key==13){
			e.preventDefault();
			var page_tonum = $(this).val();
			window.location=page_url+page_tonum+'#table_content';
		}
	})
	if($('#page_div').attr('data')){
		//ajax调用
		$('#page_div').find('a').each(function(){
			$(this).attr('data',$(this).attr('href'));
			$(this).attr('href','javascript:;');
		})
		$('#page_div').find('a').click(function(){
			$.get($(this).attr('data'),{},function(data){
				$('#table_content').html(data.table_html);
			},'json')
		})
	}
}
$(function(){
	if($('#page_div').length>=1)makepagehtml();
})